<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/TrustScore.php';
require_once __DIR__ . '/../models/UserRating.php';
require_once __DIR__ . '/../models/SavingsConfig.php';
require_once __DIR__ . '/../models/PaymentLink.php';

final class AppController extends BaseController
{
    private Account $accounts;

    private Ledger $ledger;

    private LinkedAccount $linkedAccounts;

    private TrustScore $trustScore;

    private UserRating $userRating;

    private SavingsConfig $savingsConfig;

    private PaymentLink $paymentLinks;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->accounts = new Account($db);
        $this->ledger = new Ledger($db);
        $this->linkedAccounts = new LinkedAccount($db);
        $this->trustScore = new TrustScore($db);
        $this->userRating = new UserRating($db);
        $this->savingsConfig = new SavingsConfig($db);
        $this->paymentLinks = new PaymentLink($db);
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

        if ($route === '/wallet/transfer-to-savings') {
            $this->requireMethod($method, 'POST');
            $this->transferToSavings();
            return true;
        }

        if ($route === '/wallet/transfer-from-savings') {
            $this->requireMethod($method, 'POST');
            $this->transferFromSavings();
            return true;
        }

        if ($route === '/wallet/savings-config') {
            if ($method === 'GET') {
                $this->getSavingsConfig();
                return true;
            }
            if ($method === 'POST') {
                $this->updateSavingsConfig();
                return true;
            }
        }

        if ($route === '/admin/overview') {
            $this->requireMethod($method, 'GET');
            $this->adminOverview();
            return true;
        }

        if ($route === '/admin/users') {
            $this->requireMethod($method, 'GET');
            $this->adminUsers();
            return true;
        }

        if ($route === '/admin/agents') {
            $this->requireMethod($method, 'GET');
            $this->adminAgents();
            return true;
        }

        if ($route === '/admin/transactions') {
            $this->requireMethod($method, 'GET');
            $this->adminTransactions();
            return true;
        }

        if ($route === '/admin/exchange-rates') {
            $this->requireMethod($method, 'GET');
            $this->adminExchangeRates();
            return true;
        }

        if ($route === '/admin/settings') {
            $this->requireMethod($method, 'GET');
            $this->adminSettings();
            return true;
        }

        if ($route === '/admin/volume-chart') {
            $this->requireMethod($method, 'GET');
            $this->adminVolumeChart();
            return true;
        }

        if ($route === '/admin/audit-logs') {
            $this->requireMethod($method, 'GET');
            $this->adminAuditLogs();
            return true;
        }

        if (preg_match('#^/admin/users/(\d+)/toggle-status$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->adminToggleUserStatus((int) $m[1]);
            return true;
        }

        if (preg_match('#^/admin/users/(\d+)/role$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->adminChangeUserRole((int) $m[1]);
            return true;
        }

        if (preg_match('#^/admin/agents/(\d+)/commission$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->adminUpdateAgentCommission((int) $m[1]);
            return true;
        }

        if (preg_match('#^/admin/exchange-rates/(\d+)$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->adminUpdateExchangeRate((int) $m[1]);
            return true;
        }

        if (preg_match('#^/admin/settings/(\w+)$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->adminUpdateSetting($m[1]);
            return true;
        }

        if ($route === '/links') {
            $this->requireMethod($method, 'GET');
            $this->listLinks();
            return true;
        }

        if ($route === '/links/create') {
            $this->requireMethod($method, 'POST');
            $this->createLink();
            return true;
        }

        if ($route === '/links/redeem') {
            $this->requireMethod($method, 'POST');
            $this->redeemLink();
            return true;
        }

        if (preg_match('#^/links/(\d+)/revoke$#', $route, $m)) {
            $this->requireMethod($method, 'POST');
            $this->revokeLink((int) $m[1]);
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
            $walletType = $account['wallet_type'];
            $balance = (int) $account['balance'];
            $currencyTotals = $totals[$currency] ?? ['income' => 0, 'outcome' => 0, 'total_count' => 0];

            if (!isset($metrics[$currency])) {
                $metrics[$currency] = [
                    'balance' => 0,
                    'savings_balance' => 0,
                    'income' => 0,
                    'outcome' => 0,
                    'savings_rate' => 0,
                    'total_count' => 0,
                ];
            }

            $metrics[$currency]['income'] = $currencyTotals['income'];
            $metrics[$currency]['outcome'] = $currencyTotals['outcome'];
            $metrics[$currency]['total_count'] = $currencyTotals['total_count'];
            $metrics[$currency]['balance'] += $balance;

            if ($walletType === 'savings') {
                $metrics[$currency]['savings_balance'] += $balance;
            }
        }

        foreach ($metrics as $currency => &$data) {
            $total = $data['balance'];
            $data['savings_rate'] = $total > 0
                ? max(0, min(99, (int) bcmul(bcdiv((string) $data['savings_balance'], (string) $total, 4), '100', 0)))
                : 0;
        }
        unset($data);

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
        $userId = (int) $this->user['id'];
        json_response(['success' => true, 'data' => [
            'accounts' => $this->accounts->listByUser($userId),
            'current_accounts' => $this->accounts->listByUser($userId, 'current'),
            'savings_accounts' => $this->accounts->listByUser($userId, 'savings'),
            'savings_config' => [
                'CDF' => $this->savingsConfig->getForUser($userId, 'CDF'),
                'USD' => $this->savingsConfig->getForUser($userId, 'USD'),
            ],
            'movements' => $this->ledger->recentByUser($userId, 8),
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
            'SELECT balance FROM accounts WHERE user_id = :user_id AND currency = :currency AND wallet_type = \'current\' LIMIT 1'
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

    private function adminUsers(): void
    {
        $search = (string) ($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE (u.full_name LIKE :search OR u.afric_number LIKE :search2 OR u.email LIKE :search3)';
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
        }

        $count = (int) $this->db->prepare("SELECT COUNT(*) FROM users u {$where}")->execute($params)->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT u.id, u.afric_number, u.full_name, u.email, u.role, u.is_active, u.account_type, "
            . "u.is_verified, u.created_at, "
            . "COALESCE(ts.badge, 'none') AS badge, COALESCE(ts.trust_score, 0) AS trust_score "
            . "FROM users u "
            . "LEFT JOIN user_trust_scores ts ON ts.user_id = u.id "
            . "{$where} ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->execute(array_merge($params, [':limit' => $perPage, ':offset' => $offset]));
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => [
            'users' => $users,
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
        ]]);
    }

    private function adminToggleUserStatus(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $stmt->execute([':id' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Utilisateur introuvable.']], 404);
        }

        $stmt = $this->db->prepare('SELECT is_active FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $isActive = (int) $stmt->fetchColumn();

        json_response(['success' => true, 'data' => ['is_active' => $isActive === 1]]);
    }

    private function adminChangeUserRole(int $userId): void
    {
        $payload = request_json_body();
        $role = (string) ($payload['role'] ?? '');

        if (!in_array($role, ['customer', 'agent', 'admin'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_role', 'message' => 'Rôle invalide.']], 422);
        }

        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute([':role' => $role, ':id' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Utilisateur introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['role' => $role]]);
    }

    private function adminAgents(): void
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.afric_number, u.full_name, u.email, u.is_active, u.created_at, "
            . "a.agent_code, a.commission_rate, a.phone AS agent_phone "
            . "FROM users u JOIN agents a ON a.user_id = u.id "
            . "ORDER BY u.created_at DESC"
        );
        $stmt->execute();
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $agents]);
    }

    private function adminUpdateAgentCommission(int $agentId): void
    {
        $payload = request_json_body();
        $rate = (int) ($payload['commission_rate'] ?? 0);

        if ($rate < 0 || $rate > 100000) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_rate', 'message' => 'Taux invalide (0-100000 bps).']], 422);
        }

        $stmt = $this->db->prepare('SELECT id FROM agents WHERE id = :id');
        $stmt->execute([':id' => $agentId]);
        if (!$stmt->fetch()) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Agent introuvable.']], 404);
        }

        $stmt = $this->db->prepare('UPDATE agents SET commission_rate = :rate WHERE id = :id');
        $stmt->execute([':rate' => $rate, ':id' => $agentId]);

        json_response(['success' => true, 'data' => ['commission_rate' => $rate]]);
    }

    private function adminTransactions(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $count = (int) $this->db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT t.id, t.transaction_reference, t.type, t.amount, t.currency, t.fees, t.total_amount, "
            . "t.status, t.recipient_name, t.recipient_account, t.created_at, "
            . "u.full_name AS user_name, u.afric_number AS user_afric "
            . "FROM transactions t "
            . "LEFT JOIN users u ON u.id = t.user_id "
            . "ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->execute([':limit' => $perPage, ':offset' => $offset]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => [
            'transactions' => $transactions,
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
        ]]);
    }

    private function adminExchangeRates(): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM exchange_rates ORDER BY effective_date DESC"
        );
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $rates]);
    }

    private function adminUpdateExchangeRate(int $rateId): void
    {
        $payload = request_json_body();
        $rate = (int) ($payload['rate'] ?? 0);

        if ($rate <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_rate', 'message' => 'Taux invalide.']], 422);
        }

        $stmt = $this->db->prepare('UPDATE exchange_rates SET rate = :rate WHERE id = :id');
        $stmt->execute([':rate' => $rate, ':id' => $rateId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Taux introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['rate' => $rate]]);
    }

    private function adminSettings(): void
    {
        $stmt = $this->db->query('SELECT * FROM admin_settings ORDER BY setting_key');
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $settings]);
    }

    private function adminUpdateSetting(string $key): void
    {
        $payload = request_json_body();
        $value = (string) ($payload['value'] ?? '');

        $stmt = $this->db->prepare('UPDATE admin_settings SET setting_value = :val WHERE setting_key = :key');
        $stmt->execute([':val' => $value, ':key' => $key]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Paramètre introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['setting_key' => $key, 'setting_value' => $value]]);
    }

    private function adminVolumeChart(): void
    {
        $stmt = $this->db->query(
            "SELECT DATE(created_at) AS day, currency, "
            . "SUM(CASE WHEN type IN ('deposit', 'deposit_agent', 'deposit_bank', 'deposit_mobile_money') THEN total_amount ELSE 0 END) AS income, "
            . "SUM(CASE WHEN type NOT IN ('deposit', 'deposit_agent', 'deposit_bank', 'deposit_mobile_money', 'wallet_transfer', 'early_unlock') THEN total_amount ELSE 0 END) AS outcome "
            . "FROM transactions "
            . "WHERE created_at >= datetime('now', '-30 days') "
            . "GROUP BY DATE(created_at), currency "
            . "ORDER BY day ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chart = [];
        foreach ($rows as $row) {
            $day = $row['day'];
            $cur = $row['currency'];
            if (!isset($chart[$day])) $chart[$day] = [];
            $chart[$day][$cur] = [
                'income' => (int) $row['income'],
                'outcome' => (int) $row['outcome'],
            ];
        }

        json_response(['success' => true, 'data' => $chart]);
    }

    private function adminAuditLogs(): void
    {
        $stmt = $this->db->prepare(
            "SELECT l.id, l.action, l.entity_type, l.entity_id, l.old_values, l.new_values, "
            . "l.ip_address, l.created_at, u.full_name AS user_name "
            . "FROM audit_logs l "
            . "LEFT JOIN users u ON u.id = l.user_id "
            . "ORDER BY l.created_at DESC LIMIT 50"
        );
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $logs]);
    }

    /* ── Wallet transfers ── */

    private function transferToSavings(): void
    {
        $payload = request_json_body();
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));

        if ($amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Montant invalide.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'PIN incorrect.']], 422);
        }

        $this->verifyPin((int) $this->user['id'], $pin);

        $amount = $amount * 100;
        $userId = (int) $this->user['id'];

        try {
            $this->accounts->transferBetweenWallets($userId, $currency, $amount, 'current', 'savings');

            $reference = 'SV-' . date('ymdHis') . '-' . random_int(1000, 9999);
            $stmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, metadata, created_at, completed_at) '
                . 'VALUES (:ik, :ref, :uid, :type, :amount, :cur, 0, :amount, :status, :rtype, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':ik' => bin2hex(random_bytes(16)),
                ':ref' => $reference,
                ':uid' => $userId,
                ':type' => 'wallet_transfer',
                ':amount' => $amount,
                ':cur' => $currency,
                ':status' => 'completed',
                ':rtype' => 'self',
                ':meta' => json_encode(['direction' => 'to_savings', 'method' => 'manual']),
            ]);

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'message' => 'Transfert vers l\'épargne effectué avec succès.',
            ]]);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => $e->getMessage()]], 422);
        }
    }

    private function transferFromSavings(): void
    {
        $payload = request_json_body();
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));

        if ($amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Montant invalide.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'PIN incorrect.']], 422);
        }

        $this->verifyPin((int) $this->user['id'], $pin);

        $amount = $amount * 100;
        $userId = (int) $this->user['id'];
        $config = $this->savingsConfig->getForUser($userId, $currency);

        if ($config['is_locked']) {
            json_response(['success' => false, 'error' => ['code' => 'savings_locked', 'message' => 'Votre épargne est bloquée jusqu\'à la fin de la période.']], 422);
        }

        if ($config['mode'] === 'flexible') {
            $used = $config['withdrawals_this_month'];
            $limit = $config['flexible_withdrawals_per_month'];
            if ($used >= $limit) {
                json_response(['success' => false, 'error' => ['code' => 'withdrawal_limit_reached', 'message' => "Vous avez atteint la limite de {$limit} retraits flexibles ce mois-ci."]], 422);
            }
        }

        $fees = 0;
        if ($config['mode'] === 'locked') {
            $feeBps = $config['early_withdraw_fee_bps'];
            $fees = (int) bcdiv(bcmul((string) $amount, (string) $feeBps, 0), '10000', 0);
        }

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount, 'savings');

            $this->db->beginTransaction();
            try {
                $this->accounts->move($userId, $currency, -$amount, 'savings');

                $netAmount = $amount - $fees;
                if ($netAmount > 0) {
                    $this->accounts->move($userId, $currency, $netAmount, 'current');
                }

                $reference = 'SV-' . date('ymdHis') . '-' . random_int(1000, 9999);
                $stmt = $this->db->prepare(
                    'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, metadata, created_at, completed_at) '
                    . 'VALUES (:ik, :ref, :uid, :type, :amount, :cur, :fees, :total, :status, :rtype, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $stmt->execute([
                    ':ik' => bin2hex(random_bytes(16)),
                    ':ref' => $reference,
                    ':uid' => $userId,
                    ':type' => 'wallet_transfer',
                    ':amount' => $netAmount,
                    ':cur' => $currency,
                    ':fees' => $fees,
                    ':total' => $netAmount,
                    ':status' => 'completed',
                    ':rtype' => 'self',
                    ':meta' => json_encode(['direction' => 'to_current', 'method' => $config['mode']]),
                ]);

                $this->db->commit();

                json_response(['success' => true, 'data' => [
                    'reference' => $reference,
                    'amount' => $netAmount,
                    'currency' => $currency,
                    'fees' => $fees,
                    'message' => $fees > 0
                        ? "Retrait effectué avec frais de " . number_format($fees / 100, 2, ',', ' ') . " {$currency}."
                        : 'Retrait de l\'épargne effectué avec succès.',
                ]]);
            } catch (Exception $e) {
                $this->rollbackIfNeeded();
                throw $e;
            }
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => $e->getMessage()]], 422);
        }
    }

    /* ── Savings config ── */

    private function getSavingsConfig(): void
    {
        $userId = (int) $this->user['id'];
        $currency = strtoupper(trim((string) ($_GET['currency'] ?? 'CDF')));

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            $currency = 'CDF';
        }

        json_response(['success' => true, 'data' => $this->savingsConfig->getForUser($userId, $currency)]);
    }

    private function updateSavingsConfig(): void
    {
        $payload = request_json_body();
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Devise invalide.']], 422);
        }

        $allowed = ['cashback_enabled', 'roundup_enabled', 'roundup_to_nearest', 'mode', 'lock_duration_days'];
        $data = array_intersect_key($payload, array_flip($allowed));

        if (isset($data['mode']) && !in_array($data['mode'], ['flexible', 'locked'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Mode invalide.']], 422);
        }

        $userId = (int) $this->user['id'];
        $config = $this->savingsConfig->update($userId, $currency, $data);

        json_response(['success' => true, 'data' => $config]);
    }

    private function createLink(): void
    {
        $payload = request_json_body();
        $type = (string) ($payload['type'] ?? '');
        $currency = strtoupper((string) ($payload['currency'] ?? ''));
        $durationHours = (int) ($payload['duration_hours'] ?? 24);
        $pin = (string) ($payload['pin'] ?? '');
        $amount = isset($payload['amount']) ? (int) $payload['amount'] : null;
        $maxAmount = isset($payload['max_amount']) ? (int) $payload['max_amount'] : null;

        if (!in_array($type, ['send', 'withdraw', 'merchant'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_type', 'message' => 'Type invalide.']], 422);
        }

        if (!in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_currency', 'message' => 'Devise invalide.']], 422);
        }

        if (strlen($pin) < 4 || strlen($pin) > 8) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin_length', 'message' => 'Le PIN doit contenir entre 4 et 8 chiffres.']], 422);
        }

        if (!ctype_digit($pin)) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin', 'message' => 'Le PIN doit être numérique.']], 422);
        }

        if ($durationHours < 1 || $durationHours > 720) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_duration', 'message' => 'La durée doit être entre 1 heure et 30 jours.']], 422);
        }

        if ($amount !== null && $maxAmount !== null) {
            json_response(['success' => false, 'error' => ['code' => 'ambiguous_amount', 'message' => 'Montant fixe et plafond sont exclusifs.']], 422);
        }

        $expiresAt = date('Y-m-d H:i:s', time() + $durationHours * 3600);

        $link = $this->paymentLinks->create(
            (int) $this->user['id'],
            $type,
            $amount,
            $maxAmount,
            $currency,
            $pin,
            $expiresAt
        );

        json_response(['success' => true, 'data' => $link]);
    }

    private function listLinks(): void
    {
        $this->paymentLinks->expireOld();
        $links = $this->paymentLinks->listForUser((int) $this->user['id']);

        json_response(['success' => true, 'data' => $links]);
    }

    private function redeemLink(): void
    {
        $payload = request_json_body();
        $code = (string) ($payload['code'] ?? '');
        $pin = (string) ($payload['pin'] ?? '');
        $redeemerId = (int) ($this->user['id'] ?? 0);
        $amount = isset($payload['amount']) ? (int) $payload['amount'] : null;

        if ($code === '' || $pin === '') {
            json_response(['success' => false, 'error' => ['code' => 'missing_fields', 'message' => 'Code et PIN requis.']], 422);
        }

        $link = $this->paymentLinks->findByCode($code);
        if ($link === null) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lien introuvable.']], 404);
        }

        $error = $this->paymentLinks->validate($link['id'], $pin);
        if ($error !== null) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_link', 'message' => $error]], 422);
        }

        if ((int) $link['user_id'] === $redeemerId) {
            json_response(['success' => false, 'error' => ['code' => 'self_redeem', 'message' => 'Vous ne pouvez pas utiliser votre propre lien.']], 422);
        }

        $finalAmount = $link['amount'];
        if ($finalAmount === null) {
            if ($amount === null || $amount <= 0) {
                json_response(['success' => false, 'error' => ['code' => 'amount_required', 'message' => 'Montant requis pour ce lien.']], 422);
            }
            if ($link['max_amount'] !== null && $amount > $link['max_amount']) {
                json_response(['success' => false, 'error' => ['code' => 'amount_exceeds_max', "message" => "Le montant dépasse le plafond de {$link['max_amount']} {$link['currency']}."]], 422);
            }
            $finalAmount = $amount;
        }

        $senderId = (int) $link['user_id'];
        $currency = $link['currency'];

        $accountsModel = new Account($this->db);

        try {
            $accountsModel->ensureBalance($senderId, $currency, $finalAmount);
        } catch (Throwable) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant sur le compte de l\'émetteur.']], 422);
        }

        $redeemerAccounts = $accountsModel->listByUser($redeemerId, 'current');
        $hasAccount = false;
        foreach ($redeemerAccounts as $ra) {
            if ($ra['currency'] === $currency) {
                $hasAccount = true;
                break;
            }
        }
        if (!$hasAccount) {
            $db = $this->db;
            $db->prepare('INSERT INTO accounts (user_id, currency, wallet_type, balance, created_at, updated_at, version) VALUES (?, ?, \'current\', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)')
                ->execute([$redeemerId, $currency]);
        }

        $this->db->beginTransaction();
        try {
            $accountsModel->move($senderId, $currency, -$finalAmount);
            $accountsModel->move($redeemerId, $currency, $finalAmount);

            $reference = 'PL-' . bin2hex(random_bytes(8));

            $this->ledger->record([
                'idempotency_key' => $reference,
                'transaction_reference' => $reference,
                'user_id' => $senderId,
                'type' => $link['type'],
                'amount' => $finalAmount,
                'currency' => $currency,
                'fees' => 0,
                'total_amount' => $finalAmount,
                'status' => 'completed',
                'recipient_type' => 'user',
                'recipient_name' => (string) ($this->user['full_name'] ?? ''),
                'recipient_account' => (string) ($this->user['afric_number'] ?? ''),
                'metadata' => json_encode([
                    'source' => 'payment_link',
                    'link_code' => $link['code'],
                    'link_type' => $link['type'],
                    'redeemer_id' => $redeemerId,
                ]),
            ]);

            $this->paymentLinks->use($link['id'], $redeemerId);
            $this->db->commit();

            json_response(['success' => true, 'data' => [
                'reference' => $reference,
                'amount' => $finalAmount,
                'currency' => $currency,
                'recipient_name' => $this->user['full_name'],
                'link_type' => $link['type'],
            ]]);
        } catch (Throwable $e) {
            $this->db->rollBack();
            json_response(['success' => false, 'error' => ['code' => 'transaction_failed', 'message' => 'Échec de la transaction.']], 500);
        }
    }

    private function revokeLink(int $id): void
    {
        $this->paymentLinks->revoke($id);

        json_response(['success' => true]);
    }

    private function verifyPin(int $userId, string $pin): void
    {
        $stmt = $this->db->prepare('SELECT security_pin_hash FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !$row['security_pin_hash'] || !password_verify($pin, $row['security_pin_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin', 'message' => 'PIN incorrect.']], 422);
        }
    }
}
