<?php

declare(strict_types=1);

final class BillController extends BaseController
{
    private Account $accounts;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->accounts = new Account($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = substr($path, strlen('/api/app'));

        if ($route === '/bills/verify') {
            $this->requireMethod($method, 'POST');
            $this->verifyBill();
            return true;
        }

        if ($route === '/bills/pay') {
            $this->requireMethod($method, 'POST');
            $this->payBill();
            return true;
        }

        if ($route === '/bills/auto-pay') {
            if ($method === 'GET') {
                $this->listAutoPayments();
                return true;
            }
            if ($method === 'POST') {
                $this->createAutoPayment();
                return true;
            }
        }

        if (preg_match('#^/bills/auto-pay/(\d+)$#', $route, $matches)) {
            $autoPayId = (int) $matches[1];
            if ($method === 'DELETE') {
                $this->deleteAutoPayment($autoPayId);
                return true;
            }
            if ($method === 'POST') {
                $this->toggleAutoPayment($autoPayId);
                return true;
            }
        }

        return false;
    }

    private function verifyBill(): void
    {
        $payload = request_json_body();
        $reference = strtoupper(trim((string) ($payload['reference'] ?? '')));
        $service = trim((string) ($payload['service'] ?? 'Électricité'));
        $amount = random_int(18, 95) * 1000;

        if ($reference === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Référence facture requise.']], 422);
        }

        $dueDate = date('Y-m-d', strtotime('+' . random_int(5, 30) . ' days'));

        json_response(['success' => true, 'data' => [
            'bill' => [
                'reference' => $reference,
                'service' => $service,
                'customer_name' => $this->user['full_name'],
                'amount' => $amount,
                'currency' => 'CDF',
                'status' => 'verified',
                'due_date' => $dueDate,
            ],
        ]]);
    }

    private function payBill(): void
    {
        $payload = request_json_body();
        $reference = strtoupper(trim((string) ($payload['reference'] ?? '')));
        $service = trim((string) ($payload['service'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));
        $customerNumber = trim((string) ($payload['customer_number'] ?? ''));

        if ($reference === '' || $service === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Référence et service requis.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        if ($amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Montant invalide.']], 422);
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        if ($customerNumber === '') {
            $customerNumber = $this->user['africo_number'];
        }

        $userId = (int) $this->user['id'];

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant.']], 422);
        }

        $this->db->beginTransaction();

        try {
            $txnReference = 'BIL-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $txnStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, provider_name, metadata, created_at, completed_at) '
                . 'VALUES (:ik, :ref, :uid, :type, :amount, :currency, 0, :amount, :status, :rtype, :rname, :raccount, :provider, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $txnStmt->execute([
                ':ik' => bin2hex(random_bytes(16)),
                ':ref' => $txnReference,
                ':uid' => $userId,
                ':type' => 'bill',
                ':amount' => $amount,
                ':currency' => $currency,
                ':status' => 'completed',
                ':rtype' => 'provider',
                ':rname' => $service,
                ':raccount' => $reference,
                ':provider' => $service,
                ':meta' => json_encode([
                    'service' => $service,
                    'bill_reference' => $reference,
                    'customer_number' => $customerNumber,
                ]),
            ]);

            $txnId = (int) $this->db->lastInsertId();

            $billStmt = $this->db->prepare(
                'INSERT INTO bill_payments (user_id, transaction_id, provider, customer_number, service_type, amount, status, created_at, updated_at) '
                . 'VALUES (:uid, :tid, :provider, :customer, :service, :amount, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $billStmt->execute([
                ':uid' => $userId,
                ':tid' => $txnId,
                ':provider' => $service,
                ':customer' => $customerNumber,
                ':service' => $service,
                ':amount' => $amount,
                ':status' => 'completed',
            ]);

            $this->accounts->move($userId, $currency, -$amount);

            $this->db->commit();

            json_response(['success' => true, 'data' => [
                'reference' => $txnReference,
                'bill_reference' => $reference,
                'service' => $service,
                'amount' => $amount,
                'currency' => $currency,
                'customer_number' => $customerNumber,
                'status' => 'completed',
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du paiement.']], 500);
        }
    }

    private function listAutoPayments(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare(
            'SELECT * FROM auto_payments WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);

        json_response(['success' => true, 'data' => [
            'auto_payments' => $stmt->fetchAll(),
        ]]);
    }

    private function createAutoPayment(): void
    {
        $payload = request_json_body();
        $service = trim((string) ($payload['service'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? ''));
        $amount = $payload['amount'] !== null ? (int) $payload['amount'] : null;
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $frequency = trim((string) ($payload['frequency'] ?? 'monthly'));
        $dayOfMonth = (int) ($payload['day_of_month'] ?? 1);
        $maxAmount = $payload['max_amount'] !== null ? (int) $payload['max_amount'] : null;

        if ($service === '' || $reference === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Service et référence requis.']], 422);
        }

        if (!in_array($frequency, ['weekly', 'monthly', 'quarterly', 'yearly'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Fréquence invalide.']], 422);
        }

        if ($dayOfMonth < 1 || $dayOfMonth > 28) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le jour doit être entre 1 et 28.']], 422);
        }

        $userId = (int) $this->user['id'];

        $nextPayAt = $this->calculateNextPayDate($frequency, $dayOfMonth);

        $stmt = $this->db->prepare(
            'INSERT INTO auto_payments (user_id, service_type, customer_reference, amount, currency, frequency, day_of_month, max_amount, is_active, next_pay_at, created_at, updated_at) '
            . 'VALUES (:uid, :service, :ref, :amount, :currency, :freq, :day, :maxamt, 1, :next, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':service' => $service,
            ':ref' => $reference,
            ':amount' => $amount,
            ':currency' => $currency,
            ':freq' => $frequency,
            ':day' => $dayOfMonth,
            ':maxamt' => $maxAmount,
            ':next' => $nextPayAt,
        ]);

        $id = (int) $this->db->lastInsertId();

        json_response(['success' => true, 'data' => [
            'id' => $id,
            'service' => $service,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'frequency' => $frequency,
            'day_of_month' => $dayOfMonth,
            'max_amount' => $maxAmount,
            'next_pay_at' => $nextPayAt,
        ]], 201);
    }

    private function deleteAutoPayment(int $id): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('DELETE FROM auto_payments WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Prélèvement automatique introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['message' => 'Prélèvement automatique supprimé.']]);
    }

    private function toggleAutoPayment(int $id): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('SELECT is_active FROM auto_payments WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Prélèvement automatique introuvable.']], 404);
        }

        $newStatus = $row['is_active'] ? 0 : 1;
        $updateStmt = $this->db->prepare('UPDATE auto_payments SET is_active = :active, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute([':active' => $newStatus, ':id' => $id]);

        json_response(['success' => true, 'data' => ['is_active' => (bool) $newStatus]]);
    }
}
