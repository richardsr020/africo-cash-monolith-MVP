<?php

declare(strict_types=1);

final class AppController extends BaseController
{
    private Account $accounts;

    private Ledger $ledger;

    private LinkedAccount $linkedAccounts;

    private TrustScore $trustScore;

    private UserRating $userRating;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->accounts = new Account($db);
        $this->ledger = new Ledger($db);
        $this->linkedAccounts = new LinkedAccount($db);
        $this->trustScore = new TrustScore($db);
        $this->userRating = new UserRating($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = substr($path, strlen('/api/app'));

        if ($route === '/summary') {
            $this->requireMethod($method, 'GET');
            $this->summary();
            return true;
        }

        if ($route === '/wallet') {
            $this->requireMethod($method, 'GET');
            $this->wallet();
            return true;
        }

        if ($route === '/mobile-money') {
            $this->requireMethod($method, 'GET');
            $this->mobileMoney();
            return true;
        }

        if ($route === '/mobile-money/link') {
            if ($method === 'POST') {
                $this->linkMobileMoney();
                return true;
            }
            if ($method === 'DELETE') {
                $this->unlinkMobileMoney();
                return true;
            }
        }

        if ($route === '/mobile-money/send') {
            $this->requireMethod($method, 'POST');
            $this->sendMobileMoney();
            return true;
        }

        if ($route === '/transfer') {
            $this->requireMethod($method, 'POST');
            $this->transfer();
            return true;
        }

        if ($route === '/trust-score') {
            $this->requireMethod($method, 'GET');
            $this->trustScore();
            return true;
        }

        if ($route === '/ratings') {
            if ($method === 'POST') {
                $this->rateUser();
                return true;
            }
            if ($method === 'GET') {
                $this->myRatings();
                return true;
            }
        }

        if ($route === '/admin/overview') {
            $this->requireMethod($method, 'GET');
            $this->adminOverview();
            return true;
        }

        return false;
    }

    private function summary(): void
    {
        $accounts = $this->accounts->listByUser((int) $this->user['id']);
        $totals = $this->ledger->totals((int) $this->user['id']);
        $recent = $this->ledger->recentByUser((int) $this->user['id'], 6);

        $metrics = [];
        foreach ($accounts as $account) {
            $currency = $account['currency'];
            $balance = (int) $account['balance'];
            $currencyTotals = $totals[$currency] ?? ['income' => 0, 'outcome' => 0, 'total_count' => 0];

            $metrics[$currency] = [
                'balance' => $balance,
                'income' => $currencyTotals['income'],
                'outcome' => $currencyTotals['outcome'],
                'savings_rate' => $balance > 0 ? max(0, min(99, (int) round(($balance - $currencyTotals['outcome']) / max($balance, 1) * 100))) : 0,
                'total_count' => $currencyTotals['total_count'],
            ];
        }

        json_response(['success' => true, 'data' => [
            'user' => $this->user,
            'accounts' => $accounts,
            'metrics' => $metrics,
            'chart' => $this->chartFromAccounts($accounts, $totals),
            'recent_transactions' => $recent,
        ]]);
    }

    private function wallet(): void
    {
        json_response(['success' => true, 'data' => [
            'accounts' => $this->accounts->listByUser((int) $this->user['id']),
            'movements' => $this->ledger->recentByUser((int) $this->user['id'], 8),
        ]]);
    }

    private function mobileMoney(): void
    {
        json_response(['success' => true, 'data' => [
            'providers' => [
                ['key' => 'vodacom', 'name' => 'Vodacom', 'status' => 'available'],
                ['key' => 'airtel', 'name' => 'Airtel Money', 'status' => 'available'],
                ['key' => 'orange', 'name' => 'Orange Money', 'status' => 'available'],
                ['key' => 'afrimoney', 'name' => 'Afrimoney', 'status' => 'available'],
            ],
            'linked_accounts' => $this->linkedAccounts->listByUser((int) $this->user['id'], 'mobile_money'),
        ]]);
    }

    private function linkMobileMoney(): void
    {
        $payload = request_json_body();
        $provider = trim((string) ($payload['provider'] ?? 'Airtel Money'));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $this->user['notification_phone']));

        if ($provider === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Opérateur et téléphone requis.']], 422);
        }

        $linked = $this->linkedAccounts->link((int) $this->user['id'], 'mobile_money', $provider, $provider . ' principal', $phone);
        json_response(['success' => true, 'data' => ['linked_account' => $linked]]);
    }

    private function unlinkMobileMoney(): void
    {
        $payload = request_json_body();
        $provider = trim((string) ($payload['provider'] ?? ''));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));

        if ($provider === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Opérateur et téléphone requis.']], 422);
        }

        $deleted = $this->linkedAccounts->unlink((int) $this->user['id'], 'mobile_money', $provider, $phone);

        if (!$deleted) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Compte mobile money introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['message' => 'Compte mobile money déconnecté.']]);
    }

    private function transfer(): void
    {
        $payload = request_json_body();
        $recipient = preg_replace('/\D+/', '', (string) ($payload['recipient'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));

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

        $amount = $amount * 100; // Convert to centimes

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        if (!preg_match('/^\d{8}$/', $recipient)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le numéro du destinataire doit contenir 8 chiffres.']], 422);
        }

        $userId = (int) $this->user['id'];

        if ($recipient === $this->user['africo_number']) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Vous ne pouvez pas vous envoyer de l\'argent à vous-même.']], 422);
        }

        $recipientStmt = $this->db->prepare(
            'SELECT id, full_name FROM users WHERE afric_number = :number AND is_active = 1 LIMIT 1'
        );
        $recipientStmt->execute([':number' => $recipient]);
        $recipientUser = $recipientStmt->fetch();

        if (!$recipientUser) {
            json_response(['success' => false, 'error' => ['code' => 'recipient_not_found', 'message' => 'Destinataire introuvable. Vérifiez le numéro.']], 404);
        }

        $recipientId = (int) $recipientUser['id'];

        $recipientAccountStmt = $this->db->prepare(
            'SELECT balance FROM accounts WHERE user_id = :user_id AND currency = :currency LIMIT 1'
        );
        $recipientAccountStmt->execute([':user_id' => $recipientId, ':currency' => $currency]);
        if (!$recipientAccountStmt->fetch()) {
            json_response(['success' => false, 'error' => ['code' => 'recipient_no_account', 'message' => "Le destinataire n'a pas de compte {$currency}."]], 422);
        }

        $pinStmt = $this->db->prepare(
            'SELECT security_pin_hash FROM user_onboarding WHERE user_id = :uid'
        );
        $pinStmt->execute([':uid' => $userId]);
        $pinRow = $pinStmt->fetch();
        if (!$pinRow || !$pinRow['security_pin_hash'] || !password_verify($pin, $pinRow['security_pin_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin', 'message' => 'PIN incorrect.']], 422);
        }

        if ($idempotencyKey !== '') {
            $existingStmt = $this->db->prepare(
                'SELECT id FROM transactions WHERE idempotency_key = :key LIMIT 1'
            );
            $existingStmt->execute([':key' => $idempotencyKey]);
            if ($existingStmt->fetch()) {
                json_response(['success' => false, 'error' => ['code' => 'duplicate_transaction', 'message' => 'Transaction déjà traitée.']], 409);
            }
        }

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant.']], 422);
        }

        $this->db->beginTransaction();

        try {
            $idempotencyKey = $idempotencyKey !== '' ? $idempotencyKey : bin2hex(random_bytes(16));
            $reference = 'TR-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $senderTxnStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $senderTxnStmt->execute([
                ':idempotency_key' => $idempotencyKey,
                ':reference' => $reference,
                ':user_id' => $userId,
                ':type' => 'send',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => 0,
                ':total_amount' => $amount,
                ':status' => 'completed',
                ':recipient_type' => 'user',
                ':recipient_name' => $recipientUser['full_name'],
                ':recipient_account' => $recipient,
                ':metadata' => json_encode(['description' => $description]),
            ]);

            $recipientTxnStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $recipientTxnStmt->execute([
                ':idempotency_key' => bin2hex(random_bytes(16)),
                ':reference' => $reference . '-R',
                ':user_id' => $recipientId,
                ':type' => 'deposit',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => 0,
                ':total_amount' => $amount,
                ':status' => 'completed',
                ':recipient_type' => 'user',
                ':recipient_name' => $this->user['full_name'],
                ':recipient_account' => $this->user['africo_number'],
                ':metadata' => json_encode(['description' => $description, 'parent_reference' => $reference]),
            ]);

            $this->accounts->move($userId, $currency, -$amount);
            $this->accounts->move($recipientId, $currency, $amount);

            $this->db->commit();

            $this->trustScore->recalculate($userId);
            $this->trustScore->recalculate($recipientId);

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'total_amount' => $amount,
                'recipient_name' => $recipientUser['full_name'],
                'recipient_account' => $recipient,
                'description' => $description,
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du transfert.']], 500);
        }
    }

    private function trustScore(): void
    {
        $userId = (int) $this->user['id'];
        $score = $this->trustScore->getForUser($userId);
        json_response(['success' => true, 'data' => $score]);
    }

    private function rateUser(): void
    {
        $payload = request_json_body();
        $recipientNumber = preg_replace('/\D+/', '', (string) ($payload['recipient'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? ''));
        $rating = (int) ($payload['rating'] ?? 0);
        $comment = trim((string) ($payload['comment'] ?? ''));
        $userId = (int) $this->user['id'];

        if ($rating < 1 || $rating > 5) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'La note doit être entre 1 et 5.']], 422);
        }

        if ($reference === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Référence de transaction requise.']], 422);
        }

        $txnStmt = $this->db->prepare(
            "SELECT id, user_id, type, status, recipient_account FROM transactions "
            . "WHERE transaction_reference = :ref AND user_id = :uid AND type IN ('send', 'send_mobile_money') "
            . "AND status = 'completed' LIMIT 1"
        );
        $txnStmt->execute([':ref' => $reference, ':uid' => $userId]);
        $senderTxn = $txnStmt->fetch();

        if (!$senderTxn) {
            json_response(['success' => false, 'error' => ['code' => 'transaction_not_found', 'message' => 'Transaction introuvable ou non éligible.']], 404);
        }

        $recipientNumber = $senderTxn['recipient_account'];
        $recipientStmt = $this->db->prepare('SELECT id FROM users WHERE afric_number = :num AND is_active = 1 LIMIT 1');
        $recipientStmt->execute([':num' => $recipientNumber]);
        $recipientUser = $recipientStmt->fetch();

        if (!$recipientUser) {
            json_response(['success' => false, 'error' => ['code' => 'recipient_not_found', 'message' => 'Destinataire introuvable.']], 404);
        }

        $ratedUserId = (int) $recipientUser['id'];

        if ($this->userRating->hasRated($userId, $reference)) {
            json_response(['success' => false, 'error' => ['code' => 'already_rated', 'message' => 'Vous avez déjà évalué cette transaction.']], 409);
        }

        try {
            $this->userRating->rate($userId, $ratedUserId, $reference, $rating, $comment ?: null);
            $this->trustScore->recalculate($ratedUserId);
        } catch (Exception $e) {
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors de l\'évaluation.']], 500);
        }

        json_response(['success' => true, 'data' => ['message' => 'Évaluation enregistrée. Merci !']]);
    }

    private function myRatings(): void
    {
        $userId = (int) $this->user['id'];
        $ratings = $this->userRating->getForUser($userId);
        json_response(['success' => true, 'data' => ['ratings' => $ratings]]);
    }

    private function sendMobileMoney(): void
    {
        $payload = request_json_body();
        $provider = trim((string) ($payload['provider'] ?? ''));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));

        if ($provider === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Opérateur et téléphone requis.']], 422);
        }

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

        $amount = $amount * 100;

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        $userId = (int) $this->user['id'];

        $linkedAccounts = $this->linkedAccounts->listByUser($userId, 'mobile_money');
        $isLinked = false;
        foreach ($linkedAccounts as $acc) {
            if ($acc['provider'] === $provider && $acc['account_reference'] === $phone) {
                $isLinked = true;
                break;
            }
        }
        if (!$isLinked) {
            json_response(['success' => false, 'error' => ['code' => 'not_linked', 'message' => 'Ce compte mobile money n\'est pas lié à votre compte.']], 422);
        }

        $pinStmt = $this->db->prepare('SELECT security_pin_hash FROM user_onboarding WHERE user_id = :uid');
        $pinStmt->execute([':uid' => $userId]);
        $pinRow = $pinStmt->fetch();
        if (!$pinRow || !$pinRow['security_pin_hash'] || !password_verify($pin, $pinRow['security_pin_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin', 'message' => 'PIN incorrect.']], 422);
        }

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant.']], 422);
        }

        $this->db->beginTransaction();
        try {
            $reference = 'MM-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $stmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, provider_name, metadata, created_at, completed_at) '
                . 'VALUES (:ik, :ref, :uid, :type, :amount, :currency, :fees, :total, :status, :rtype, :rname, :raccount, :provider, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':ik' => bin2hex(random_bytes(16)),
                ':ref' => $reference,
                ':uid' => $userId,
                ':type' => 'send_mobile_money',
                ':amount' => $amount,
                ':currency' => $currency,
                ':fees' => 0,
                ':total' => $amount,
                ':status' => 'completed',
                ':rtype' => 'mobile_money',
                ':rname' => $provider,
                ':raccount' => $phone,
                ':provider' => $provider,
                ':meta' => json_encode([
                    'provider' => $provider,
                    'phone' => $phone,
                ]),
            ]);

            $this->accounts->move($userId, $currency, -$amount);
            $this->db->commit();

            $this->trustScore->recalculate($userId);

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'provider' => $provider,
                'recipient_account' => $phone,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'total_amount' => $amount,
            ]]);
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors du transfert mobile money.']], 500);
        }
    }

    private function adminOverview(): void
    {
        $users = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $agents = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'")->fetchColumn();
        $transactions = (int) $this->db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $volume = (int) $this->db->query('SELECT COALESCE(SUM(total_amount), 0) FROM transactions')->fetchColumn();

        $silverCount = (int) $this->db->query("SELECT COUNT(*) FROM user_trust_scores WHERE badge = 'silver'")->fetchColumn();
        $goldCount = (int) $this->db->query("SELECT COUNT(*) FROM user_trust_scores WHERE badge = 'gold'")->fetchColumn();
        $badgeData = $this->trustScore->getAllWithBadge();

        json_response(['success' => true, 'data' => compact('users', 'agents', 'transactions', 'volume', 'silverCount', 'goldCount', 'badgeData')]);
    }
}
