<?php

declare(strict_types=1);

final class WithdrawController extends BaseController
{
    private Account $accounts;

    private Ledger $ledger;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->accounts = new Account($db);
        $this->ledger = new Ledger($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = substr($path, strlen('/api/app'));

        if ($route === '/atm/withdraw') {
            $this->requireMethod($method, 'POST');
            $this->atmWithdraw();
            return true;
        }

        if ($route === '/agent/withdraw') {
            $this->requireMethod($method, 'POST');
            $this->agentWithdraw();
            return true;
        }

        if ($route === '/dab/authorize') {
            $this->requireMethod($method, 'POST');
            $this->dabAuthorize();
            return true;
        }

        if ($route === '/dab/confirm') {
            $this->requireMethod($method, 'POST');
            $this->dabConfirm();
            return true;
        }

        return false;
    }

    private function atmWithdraw(): void
    {
        $payload = request_json_body();
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        $minAmount = $currency === 'CDF' ? 1000 : 1;
        if ($amount < $minAmount) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => "Le montant minimum est de {$minAmount} {$currency}."]], 422);
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        $maxAmount = $currency === 'CDF' ? 1000000 : 10000;
        if ($amount > $maxAmount) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => "Le montant maximum est de {$maxAmount} {$currency}."]], 422);
        }

        $amount = $amount * 100; // Convert to centimes
        $userId = (int) $this->user['id'];

        $fees = 0;
        $totalAmount = $amount + $fees;
        $atmCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        try {
            $transaction = $this->ledger->record($userId, [
                'type' => 'withdraw',
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'recipient_type' => 'atm',
                'recipient_name' => 'Retrait ATM',
                'atm_code' => $atmCode,
                'metadata' => [
                    'atm_pin' => $pin,
                    'expires_at' => $expiresAt,
                    'method' => 'atm',
                ],
            ]);

            json_response(['success' => true, 'data' => [
                'atm_code' => $atmCode,
                'atm_pin' => $pin,
                'expires_at' => $expiresAt,
                'transaction' => $transaction,
            ]]);
        } catch (Exception $e) {
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du retrait ATM.']], 500);
        }
    }

    private function agentWithdraw(): void
    {
        $payload = request_json_body();
        $agentCode = preg_replace('/\D+/', '', (string) ($payload['agent_code'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        $minAmount = $currency === 'CDF' ? 100 : 1;
        if ($amount < $minAmount) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => "Le montant minimum est de {$minAmount} {$currency}."]], 422);
        }

        $maxAmount = $currency === 'CDF' ? 1000000 : 10000;
        if ($amount > $maxAmount) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => "Le montant maximum est de {$maxAmount} {$currency}."]], 422);
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        if (strlen($agentCode) !== 8) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le code agent doit contenir 8 chiffres.']], 422);
        }

        if ($agentCode === $this->user['africo_number']) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Vous ne pouvez pas effectuer un retrait vers votre propre compte.']], 422);
        }

        $agentStmt = $this->db->prepare(
            'SELECT id, afric_number, full_name FROM users WHERE afric_number = :number AND role = :role AND is_active = 1 LIMIT 1'
        );
        $agentStmt->execute([':number' => $agentCode, ':role' => 'agent']);
        $agent = $agentStmt->fetch();

        if (!$agent) {
            json_response(['success' => false, 'error' => ['code' => 'agent_not_found', 'message' => 'Agent introuvable. Vérifiez le numéro.']], 404);
        }

        $amount = $amount * 100; // Convert to centimes
        $userId = (int) $this->user['id'];

        $agentAccountStmt = $this->db->prepare(
            'SELECT balance FROM accounts WHERE user_id = :user_id AND currency = :currency LIMIT 1'
        );
        $agentAccountStmt->execute([':user_id' => $agent['id'], ':currency' => $currency]);
        if (!$agentAccountStmt->fetch()) {
            json_response(['success' => false, 'error' => ['code' => 'agent_no_account', 'message' => "L'agent n'a pas de compte {$currency}."]], 422);
        }

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant.']], 422);
        }

        $fees = 0;
        $totalAmount = $amount + $fees;

        $this->db->beginTransaction();

        try {
            $reference = 'AG-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $userStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $userStmt->execute([
                ':idempotency_key' => bin2hex(random_bytes(16)),
                ':reference' => $reference,
                ':user_id' => $userId,
                ':type' => 'withdraw',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => $fees,
                ':total_amount' => $totalAmount,
                ':status' => 'completed',
                ':recipient_type' => 'agent',
                ':recipient_name' => $agent['full_name'],
                ':recipient_account' => $agentCode,
                ':metadata' => json_encode([
                    'method' => 'agent',
                    'agent_id' => $agent['id'],
                ]),
            ]);

            $agentTxnStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $agentTxnStmt->execute([
                ':idempotency_key' => bin2hex(random_bytes(16)),
                ':reference' => $reference . '-A',
                ':user_id' => $agent['id'],
                ':type' => 'deposit',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => 0,
                ':total_amount' => $amount,
                ':status' => 'completed',
                ':recipient_type' => 'customer',
                ':recipient_name' => $this->user['full_name'],
                ':recipient_account' => $this->user['africo_number'],
                ':metadata' => json_encode([
                    'method' => 'agent',
                    'parent_reference' => $reference,
                ]),
            ]);

            $this->accounts->move($userId, $currency, -$totalAmount);
            $this->accounts->move((int) $agent['id'], $currency, $amount);

            $this->db->commit();

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'total_amount' => $totalAmount,
                'agent_name' => $agent['full_name'],
                'agent_code' => $agentCode,
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du retrait agent.']], 500);
        }
    }

    private function dabAuthorize(): void
    {
        $payload = request_json_body();
        $code = preg_replace('/\D+/', '', (string) ($payload['code'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = preg_replace('/\D+/', '', (string) ($payload['pin'] ?? ''));

        if (strlen($code) !== 6 || strlen($pin) !== 4) {
            json_response(['success' => false, 'message' => 'Code ou PIN invalide.'], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'message' => 'Devise invalide.'], 422);
        }

        if ($amount <= 0) {
            json_response(['success' => false, 'message' => 'Le montant doit être supérieur à 0.'], 422);
        }

        $maxAmount = $currency === 'CDF' ? 1000000 : 10000;
        if ($amount > $maxAmount) {
            json_response(['success' => false, 'message' => "Le montant maximum est de {$maxAmount} {$currency}."], 422);
        }

        $amount = $amount * 100; // Convert to centimes
        $transaction = $this->ledger->findByAtmCode($code);

        if (!$transaction) {
            json_response(['success' => false, 'message' => 'Code de retrait introuvable.'], 404);
        }

        $metadata = json_decode($transaction['metadata'] ?: '{}', true);
        $storedPin = (string) ($metadata['atm_pin'] ?? '');

        if ($storedPin !== $pin) {
            json_response(['success' => false, 'message' => 'PIN incorrect.'], 422);
        }

        if ((int) $transaction['amount'] !== $amount) {
            json_response(['success' => false, 'message' => 'Le montant ne correspond pas au code de retrait.'], 422);
        }

        if (strtoupper($transaction['currency']) !== $currency) {
            json_response(['success' => false, 'message' => 'La devise ne correspond pas.'], 422);
        }

        $createdAt = strtotime($transaction['created_at']);
        if ($createdAt === false || (time() - $createdAt) > 600) {
            json_response(['success' => false, 'message' => 'Ce code de retrait a expiré (10 min).'], 410);
        }

        $status = $transaction['status'] ?? '';
        if ($status === 'completed') {
            json_response(['success' => false, 'message' => 'Ce code de retrait a déjà été utilisé.'], 409);
        }
        if ($status === 'succeeded') {
            json_response(['success' => false, 'message' => 'Ce code est déjà en cours d\'utilisation sur un autre DAB.'], 409);
        }
        if ($status !== 'pending') {
            json_response(['success' => false, 'message' => 'Ce code de retrait n\'est plus valide.'], 409);
        }

        if (!empty($metadata['dab_withdrawn_at'])) {
            json_response(['success' => false, 'message' => 'Ce code a déjà été utilisé.'], 409);
        }

        $userId = (int) $transaction['user_id'];

        if ($userId !== (int) $this->user['id']) {
            json_response(['success' => false, 'message' => 'Ce code de retrait ne vous appartient pas.'], 403);
        }

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'message' => 'Solde insuffisant pour effectuer le retrait.'], 422);
        }

        $this->ledger->updateStatus((int) $transaction['id'], 'succeeded');

        json_response([
            'success' => true,
            'message' => 'Retrait autorisé. Distribution des billets en cours.',
            'data' => [
                'reference' => (string) $transaction['transaction_reference'],
                'amount' => $amount,
                'currency' => $currency,
            ],
        ]);
    }

    private function dabConfirm(): void
    {
        $payload = request_json_body();
        $code = preg_replace('/\D+/', '', (string) ($payload['code'] ?? ''));

        if (strlen($code) !== 6) {
            json_response(['success' => false, 'message' => 'Code de retrait invalide.'], 422);
        }

        $transaction = $this->ledger->findByAtmCode($code);

        if (!$transaction) {
            json_response(['success' => false, 'message' => 'Code de retrait introuvable.'], 404);
        }

        $metadata = json_decode($transaction['metadata'] ?: '{}', true);

        if (($transaction['status'] ?? '') !== 'succeeded') {
            json_response(['success' => false, 'message' => 'Ce retrait n\'a pas été autorisé ou a expiré.'], 409);
        }

        if (!empty($metadata['dab_withdrawn_at'])) {
            json_response(['success' => false, 'message' => 'Ce code a déjà été utilisé.'], 409);
        }

        $createdAt = strtotime($transaction['created_at']);
        if ($createdAt === false || (time() - $createdAt) > 600) {
            $this->ledger->updateStatus((int) $transaction['id'], 'failed');
            json_response(['success' => false, 'message' => 'Ce code de retrait a expiré pendant la distribution.'], 410);
        }

        $userId = (int) $transaction['user_id'];

        if ($userId !== (int) $this->user['id']) {
            json_response(['success' => false, 'message' => 'Ce code de retrait ne vous appartient pas.'], 403);
        }

        $amount = (int) $transaction['amount'];
        $currency = strtoupper((string) $transaction['currency']);
        $fees = 0;
        $totalAmount = $amount + $fees;

        try {
            $this->accounts->ensureBalance($userId, $currency, $totalAmount);
        } catch (RuntimeException $e) {
            $this->ledger->updateStatus((int) $transaction['id'], 'failed');
            json_response(['success' => false, 'message' => 'Solde insuffisant pour finaliser le retrait.'], 422);
        }

        $this->db->beginTransaction();

        try {
            $this->accounts->move($userId, $currency, -$totalAmount);
            $this->ledger->updateStatus((int) $transaction['id'], 'completed');
            $this->ledger->markAtmWithdrawn((int) $transaction['id']);
            $this->db->commit();
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'message' => 'Erreur lors de la finalisation du retrait.'], 500);
        }

        $remainingBalance = $this->accounts->listByUser($userId);
        $remaining = 0;
        foreach ($remainingBalance as $acc) {
            if ($acc['currency'] === $currency) {
                $remaining = $acc['balance'];
                break;
            }
        }

        json_response([
            'success' => true,
            'message' => 'Retrait effectué avec succès.',
            'data' => [
                'transaction' => [
                    'reference' => (string) $transaction['transaction_reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'fees' => $fees,
                    'total_amount' => $totalAmount,
                    'remaining_balance' => $remaining,
                ],
            ],
        ]);
    }
}
