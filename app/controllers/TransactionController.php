<?php

require_once __DIR__ . '/../models/Transaction.php';

class TransactionController {
    private $transactionModel;
    
    public function __construct($db) {
        $this->transactionModel = new Transaction($db);
    }
    
    public function index() {
        $activeTab = $_GET['tab'] ?? 'send';
        $balance = $this->transactionModel->checkBalance($_SESSION['user_id']);
        
        // Données pour l'historique
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'type' => $_GET['type'] ?? null,
            'status' => $_GET['status'] ?? null
        ];
        
        $transactions = $this->transactionModel->getUserTransactions(
            $_SESSION['user_id'],
            $limit,
            $offset,
            array_filter($filters)
        );
        
        $stmt = $this->transactionModel->db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $total = $stmt->fetchColumn();
        $totalPages = ceil($total / $limit);
        
        $viewData = [
            'activeTab' => $activeTab,
            'balance' => $balance,
            'transactions' => $transactions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'errors' => $_SESSION['errors'] ?? [],
            'error' => $_SESSION['error'] ?? '',
            'success' => $_SESSION['success'] ?? ''
        ];
        
        // Clear session messages
        unset($_SESSION['errors']);
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $this->renderView('transactions/index', $viewData);
    }
    
    public function send() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /transactions?tab=send");
            exit;
        }
        
        $recipient = trim($_POST['recipient'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'CDF'));
        $description = trim($_POST['description'] ?? '');
        
        $errors = [];
        
        if (!in_array($currency, ['CDF', 'USD'], true)) {
            $errors[] = "Devise invalide.";
        }
        
        if (empty($recipient)) {
            $errors[] = "Le numéro du destinataire est requis";
        } elseif (!preg_match('/^\d{8}$/', $recipient)) {
            $errors[] = "Le numéro Africo doit contenir 8 chiffres";
        }
        
        $minAmount = $currency === 'CDF' ? 100 : 1;
        if ($amount < $minAmount) {
            $errors[] = "Le montant minimum est de {$minAmount} {$currency}";
        } elseif ($amount > 1000000) {
            $errors[] = "Le montant maximum est de 1.000.000 {$currency}";
        }
        
        $recipientUser = $this->transactionModel->findUserByAfricoNumber($recipient);
        if (!$recipientUser) {
            $errors[] = "Numéro Africo Cash invalide";
        }
        
        if ($recipientUser && (int) $recipientUser['id'] === (int) $_SESSION['user_id']) {
            $errors[] = "Vous ne pouvez pas vous envoyer de l'argent à vous-même.";
        }
        
        if ($recipientUser) {
            $recipientAccountStmt = $this->transactionModel->db->prepare(
                'SELECT balance FROM accounts WHERE user_id = :uid AND currency = :currency LIMIT 1'
            );
            $recipientAccountStmt->execute([':uid' => $recipientUser['id'], ':currency' => $currency]);
            if (!$recipientAccountStmt->fetch()) {
                $errors[] = "Le destinataire n'a pas de compte {$currency}.";
            }
        }
        
        $fees = 0;
        $totalAmount = $amount + $fees;
        
        $balance = $this->transactionModel->checkBalance($_SESSION['user_id'], $currency);
        if ($balance < $totalAmount) {
            $errors[] = "Solde insuffisant. Votre solde {$currency} est de " . number_format($balance, 0, ',', ' ') . " {$currency}";
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: /transactions?tab=send");
            exit;
        }
        
        $this->transactionModel->db->beginTransaction();
        
        try {
            $reference = 'TR-' . date('ymdHis') . '-' . random_int(1000, 9999);
            
            $senderData = [
                'idempotency_key' => $this->transactionModel->generateIdempotencyKey(),
                'transaction_reference' => $reference,
                'user_id' => $_SESSION['user_id'],
                'type' => Transaction::TYPE_SEND,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'total_amount' => $totalAmount,
                'status' => Transaction::STATUS_PENDING,
                'recipient_name' => $recipientUser['full_name'],
                'recipient_account' => $recipient,
                'metadata' => ['description' => $description],
            ];
            
            $senderStmt = $this->transactionModel->db->prepare('SELECT full_name, afric_number FROM users WHERE id = :id LIMIT 1');
            $senderStmt->execute([':id' => $_SESSION['user_id']]);
            $senderInfo = $senderStmt->fetch();

            $recipientData = [
                'idempotency_key' => $this->transactionModel->generateIdempotencyKey(),
                'transaction_reference' => $reference . '-R',
                'user_id' => $recipientUser['id'],
                'type' => Transaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'total_amount' => $amount,
                'status' => Transaction::STATUS_PENDING,
                'recipient_name' => $senderInfo ? $senderInfo['full_name'] : 'Inconnu',
                'recipient_account' => $senderInfo ? $senderInfo['afric_number'] : '',
                'metadata' => ['description' => $description, 'parent_reference' => $reference],
            ];
            
            $this->transactionModel->create($senderData);
            $this->transactionModel->create($recipientData);
            $this->transactionModel->updateBalance($_SESSION['user_id'], $totalAmount, 'debit', $currency);
            $this->transactionModel->updateBalance($recipientUser['id'], $amount, 'credit', $currency);
            $this->transactionModel->updateStatus($reference, Transaction::STATUS_COMPLETED);
            $this->transactionModel->updateStatus($reference . '-R', Transaction::STATUS_COMPLETED);
            
            $this->transactionModel->db->commit();
            
            $_SESSION['receipt'] = array_merge($senderData, [
                'created_at' => date('Y-m-d H:i:s'),
                'transaction_reference' => $reference,
            ]);
            header("Location: /transactions?tab=receipt");
            exit;
            
        } catch (Exception $e) {
            $this->transactionModel->db->rollBack();
            $_SESSION['error'] = "Erreur lors du transfert: " . $e->getMessage();
            header("Location: /transactions?tab=send");
            exit;
        }
    }
    
    public function receipt() {
        $reference = $_GET['ref'] ?? null;
        if (!$reference) {
            header("Location: /transactions");
            exit;
        }
        
        $transaction = $this->transactionModel->getByReference($reference);
        if (!$transaction || $transaction['user_id'] != $_SESSION['user_id']) {
            header("Location: /transactions");
            exit;
        }
        
        $_SESSION['receipt'] = $transaction;
        header("Location: /transactions?tab=receipt");
        exit;
    }
    

}