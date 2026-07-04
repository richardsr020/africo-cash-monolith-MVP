<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/AdminSettings.php';

final class BankingController extends BaseController
{
    private Account $accounts;

    private AdminSettings $adminSettings;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->accounts = new Account($db);
        $this->adminSettings = new AdminSettings($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = substr($path, strlen('/api/app'));

        if ($route === '/banking/partner-transfer') {
            $this->requireMethod($method, 'POST');
            $this->partnerBankTransfer();
            return true;
        }

        if ($route === '/banking/external-transfer') {
            $this->requireMethod($method, 'POST');
            $this->externalBankTransfer();
            return true;
        }

        if ($route === '/banking/beneficiaries') {
            if ($method === 'GET') {
                $this->listBeneficiaries();
                return true;
            }
            if ($method === 'POST') {
                $this->createBeneficiary();
                return true;
            }
        }

        if (preg_match('#^/banking/beneficiaries/(\d+)$#', $route, $matches)) {
            $benefId = (int) $matches[1];
            if ($method === 'DELETE') {
                $this->deleteBeneficiary($benefId);
                return true;
            }
        }

        if ($route === '/banking/history') {
            $this->requireMethod($method, 'GET');
            $this->bankingHistory();
            return true;
        }

        return false;
    }

    private function partnerBankTransfer(): void
    {
        $payload = request_json_body();
        $bank = trim((string) ($payload['bank'] ?? ''));
        $account = trim((string) ($payload['account'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $holder = trim((string) ($payload['holder'] ?? $this->user['full_name']));

        if ($bank === '' || $account === '' || $amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Banque, compte et montant requis.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        $amount = $amount * 100; // Convert to centimes
        $fees = $this->adminSettings->calculateFee('bank_transfer', $amount);
        $totalAmount = $amount + $fees;
        $userId = (int) $this->user['id'];

        try {
            $this->accounts->ensureBalance($userId, $currency, $totalAmount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant pour le virement et les frais.']], 422);
        }

        $this->db->beginTransaction();
        try {
            $reference = 'BNK-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $stmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, provider_name, metadata, created_at, completed_at) '
                . 'VALUES (:ik, :ref, :uid, :type, :amount, :currency, :fees, :total, :status, :rtype, :rname, :raccount, :provider, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':ik' => bin2hex(random_bytes(16)),
                ':ref' => $reference,
                ':uid' => $userId,
                ':type' => 'send_bank',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => $fees,
                ':total' => $totalAmount,
                ':status' => 'completed',
                ':rtype' => 'bank',
                ':rname' => $holder,
                ':raccount' => $account,
                ':provider' => $bank,
                ':meta' => json_encode([
                    'bank' => $bank,
                    'account' => $account,
                    'holder' => $holder,
                    'type' => 'partner',
                ]),
            ]);

            $this->accounts->move($userId, $currency, -$totalAmount);
            $this->db->commit();

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'bank' => $bank,
                'account' => $account,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'total_amount' => $totalAmount,
                'holder' => $holder,
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du virement.']], 500);
        }
    }

    private function externalBankTransfer(): void
    {
        $payload = request_json_body();
        $bank = trim((string) ($payload['bank'] ?? ''));
        $account = trim((string) ($payload['account'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $holder = trim((string) ($payload['holder'] ?? ''));
        $swift = strtoupper(trim((string) ($payload['swift'] ?? '')));

        if ($bank === '' || $account === '' || $holder === '' || $amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Banque, titulaire, compte et montant requis.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        $amount = $amount * 100; // Convert to centimes
        $fees = $this->adminSettings->calculateFee('bank_transfer', $amount);
        $totalAmount = $amount + $fees;
        $userId = (int) $this->user['id'];

        try {
            $this->accounts->ensureBalance($userId, $currency, $totalAmount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant pour le virement et les frais.']], 422);
        }

        $this->db->beginTransaction();
        try {
            $reference = 'EXT-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $stmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, provider_name, metadata, created_at, completed_at) '
                . 'VALUES (:ik, :ref, :uid, :type, :amount, :currency, :fees, :total, :status, :rtype, :rname, :raccount, :provider, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':ik' => bin2hex(random_bytes(16)),
                ':ref' => $reference,
                ':uid' => $userId,
                ':type' => 'send_bank',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => $fees,
                ':total' => $totalAmount,
                ':status' => 'completed',
                ':rtype' => 'bank',
                ':rname' => $holder,
                ':raccount' => $account,
                ':provider' => $bank,
                ':meta' => json_encode([
                    'bank' => $bank,
                    'account' => $account,
                    'holder' => $holder,
                    'swift' => $swift,
                    'type' => 'external',
                ]),
            ]);

            $this->accounts->move($userId, $currency, -$totalAmount);
            $this->db->commit();

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'bank' => $bank,
                'account' => $account,
                'holder' => $holder,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'total_amount' => $totalAmount,
                'swift' => $swift,
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du virement externe.']], 500);
        }
    }

    private function listBeneficiaries(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('SELECT * FROM beneficiaries WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);
        json_response(['success' => true, 'data' => ['beneficiaries' => $stmt->fetchAll()]]);
    }

    private function createBeneficiary(): void
    {
        $payload = request_json_body();
        $bankName = trim((string) ($payload['bank_name'] ?? ''));
        $accountNumber = trim((string) ($payload['account_number'] ?? ''));
        $accountHolder = trim((string) ($payload['account_holder'] ?? ''));
        $swiftCode = strtoupper(trim((string) ($payload['swift_code'] ?? '')));
        $isPartner = !empty($payload['is_partner']);

        if ($bankName === '' || $accountNumber === '' || $accountHolder === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Banque, numéro de compte et titulaire requis.']], 422);
        }

        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare(
            'INSERT INTO beneficiaries (user_id, type, bank_name, account_number, account_holder, swift_code, is_partner) '
            . 'VALUES (:uid, :type, :bank, :account, :holder, :swift, :partner)'
        );

        try {
            $stmt->execute([
                ':uid' => $userId,
                ':type' => 'bank',
                ':bank' => $bankName,
                ':account' => $accountNumber,
                ':holder' => $accountHolder,
                ':swift' => $swiftCode,
                ':partner' => $isPartner ? 1 : 0,
            ]);
            $id = (int) $this->db->lastInsertId();
            json_response(['success' => true, 'data' => ['id' => $id, 'bank_name' => $bankName, 'account_number' => $accountNumber, 'account_holder' => $accountHolder]], 201);
        } catch (Exception $e) {
            json_response(['success' => false, 'error' => ['code' => 'duplicate', 'message' => 'Ce bénéficiaire existe déjà.']], 409);
        }
    }

    private function deleteBeneficiary(int $id): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('DELETE FROM beneficiaries WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Bénéficiaire introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['message' => 'Bénéficiaire supprimé.']]);
    }

    private function bankingHistory(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare(
            "SELECT transaction_reference, type, amount, currency, fees, total_amount, status, provider_name, recipient_name, recipient_account, metadata, created_at, completed_at "
            . "FROM transactions WHERE user_id = :uid AND type = 'send_bank' ORDER BY created_at DESC LIMIT 20"
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $rows = array_map(static function (array $row): array {
            if ($row['metadata']) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            return $row;
        }, $rows);

        json_response(['success' => true, 'data' => ['transactions' => $rows]]);
    }
}
