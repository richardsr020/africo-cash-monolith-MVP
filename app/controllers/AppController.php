<?php

declare(strict_types=1);

final class AppController
{
    private Account $accounts;

    private Ledger $ledger;

    private LinkedAccount $linkedAccounts;

    public function __construct(private PDO $db, private array $user)
    {
        $this->accounts = new Account($db);
        $this->ledger = new Ledger($db);
        $this->linkedAccounts = new LinkedAccount($db);
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

        if ($route === '/profile' && $method === 'GET') {
            json_response(['success' => true, 'data' => ['user' => $this->user]]);
            return true;
        }

        if ($route === '/profile' && $method === 'POST') {
            $this->updateProfile();
            return true;
        }

        if ($route === '/profile/change-password') {
            $this->requireMethod($method, 'POST');
            $this->changePassword();
            return true;
        }

        if ($route === '/profile/change-pin') {
            $this->requireMethod($method, 'POST');
            $this->changePin();
            return true;
        }

        if ($route === '/profile/sessions') {
            if ($method === 'GET') {
                $this->listSessions();
                return true;
            }
        }

        if (preg_match('#^/profile/sessions/(\d+)$#', $route, $matches)) {
            $sessionId = (int) $matches[1];
            if ($method === 'DELETE') {
                $this->revokeSession($sessionId);
                return true;
            }
        }

        if ($route === '/profile/preferences') {
            if ($method === 'GET') {
                $this->getPreferences();
                return true;
            }
            if ($method === 'POST') {
                $this->updatePreferences();
                return true;
            }
        }

        if ($route === '/profile/two-factor/enable') {
            $this->requireMethod($method, 'POST');
            $this->enableTwoFactor();
            return true;
        }

        if ($route === '/profile/two-factor/disable') {
            $this->requireMethod($method, 'POST');
            $this->disableTwoFactor();
            return true;
        }

        if ($route === '/profile/linked-accounts') {
            $this->requireMethod($method, 'GET');
            $this->profileLinkedAccounts();
            return true;
        }

        if ($route === '/profile/activity') {
            $this->requireMethod($method, 'GET');
            $this->profileActivity();
            return true;
        }

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

        if ($route === '/transfer') {
            $this->requireMethod($method, 'POST');
            $this->transfer();
            return true;
        }

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
        $totalBalance = array_sum(array_map(static fn (array $account): int => (int) $account['balance'], $accounts));

        json_response(['success' => true, 'data' => [
            'user' => $this->user,
            'accounts' => $accounts,
            'metrics' => [
                'total_balance' => $totalBalance,
                'income' => $totals['income'],
                'outcome' => $totals['outcome'],
                'savings_rate' => $totalBalance > 0 ? max(0, min(99, (int) round(($totalBalance - $totals['outcome']) / max($totalBalance, 1) * 100))) : 0,
                'total_count' => $totals['total_count'],
            ],
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

    private function calculateNextPayDate(string $frequency, int $dayOfMonth): string
    {
        $now = new DateTime();
        $next = clone $now;

        switch ($frequency) {
            case 'weekly':
                $next->modify('next ' . ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'][min($dayOfMonth - 1, 4)]);
                break;
            case 'monthly':
                $next->setDate((int) $next->format('Y'), (int) $next->format('m'), $dayOfMonth);
                if ($next <= $now) {
                    $next->modify('+1 month');
                }
                break;
            case 'quarterly':
                $next->setDate((int) $next->format('Y'), (int) (ceil((int) $next->format('m') / 3) * 3), $dayOfMonth);
                if ($next <= $now) {
                    $next->modify('+3 months');
                }
                break;
            case 'yearly':
                $next->setDate((int) $next->format('Y'), 1, $dayOfMonth);
                if ($next <= $now) {
                    $next->modify('+1 year');
                }
                break;
        }

        return $next->format('Y-m-d H:i:s');
    }

    private function updateProfile(): void
    {
        $payload = request_json_body();
        $fullName = trim((string) ($payload['full_name'] ?? $this->user['full_name']));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $this->user['notification_phone']));
        $city = trim((string) ($payload['city'] ?? $this->user['onboarding']['city'] ?? ''));
        $profession = trim((string) ($payload['profession'] ?? $this->user['onboarding']['profession'] ?? ''));
        $address = trim((string) ($payload['address'] ?? $this->user['onboarding']['address'] ?? ''));
        $preferredName = trim((string) ($payload['preferred_name'] ?? $this->user['onboarding']['preferred_name'] ?? ''));

        if ($fullName === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Nom et téléphone requis.']], 422);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE users SET full_name = :full_name, notification_phone = :phone, address = :city, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute([
                ':id' => (int) $this->user['id'],
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':city' => $city,
            ]);

            $onboardStmt = $this->db->prepare(
                'UPDATE user_onboarding SET preferred_name = :pref, city = :city, profession = :prof, address = :addr, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid'
            );
            $onboardStmt->execute([
                ':pref' => $preferredName,
                ':city' => $city,
                ':prof' => $profession,
                ':addr' => $address,
                ':uid' => (int) $this->user['id'],
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Erreur lors de la mise à jour.']], 500);
            return;
        }

        json_response(['success' => true, 'data' => ['user' => (new User($this->db))->findPublicById((int) $this->user['id'])]]);
    }

    private function changePassword(): void
    {
        $payload = request_json_body();
        $currentPassword = (string) ($payload['current_password'] ?? '');
        $newPassword = (string) ($payload['new_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Mot de passe actuel et nouveau requis.']], 422);
        }

        if (strlen($newPassword) < 8) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.']], 422);
        }

        if (!password_verify($currentPassword, $this->user['password_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_password', 'message' => 'Mot de passe actuel incorrect.']], 422);
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => (int) $this->user['id']]);

        json_response(['success' => true, 'data' => ['message' => 'Mot de passe modifié avec succès.']]);
    }

    private function changePin(): void
    {
        $payload = request_json_body();
        $currentPin = trim((string) ($payload['current_pin'] ?? ''));
        $newPin = trim((string) ($payload['new_pin'] ?? ''));

        if ($currentPin === '' || $newPin === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'PIN actuel et nouveau requis.']], 422);
        }

        if (!preg_match('/^\d{4}$/', $newPin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        $stmt = $this->db->prepare('SELECT security_pin_hash FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => (int) $this->user['id']]);
        $row = $stmt->fetch();

        if ($row && $row['security_pin_hash'] && !password_verify($currentPin, $row['security_pin_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_pin', 'message' => 'PIN actuel incorrect.']], 422);
        }

        $hash = password_hash($newPin, PASSWORD_BCRYPT);
        $updateStmt = $this->db->prepare('UPDATE user_onboarding SET security_pin_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
        $updateStmt->execute([':hash' => $hash, ':uid' => (int) $this->user['id']]);

        json_response(['success' => true, 'data' => ['message' => 'PIN modifié avec succès.']]);
    }

    private function listSessions(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare(
            'SELECT id, user_agent, ip_address, created_at, last_used_at FROM auth_sessions WHERE user_id = :uid AND revoked_at IS NULL AND expires_at > CURRENT_TIMESTAMP ORDER BY last_used_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        json_response(['success' => true, 'data' => ['sessions' => $stmt->fetchAll()]]);
    }

    private function revokeSession(int $sessionId): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('UPDATE auth_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $sessionId, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Session introuvable.']], 404);
        }

        json_response(['success' => true, 'data' => ['message' => 'Session révoquée.']]);
    }

    private function getPreferences(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('SELECT preferences FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        $defaults = ['notify_sms' => true, 'notify_email' => true, 'notify_push' => true, 'two_factor_enabled' => false, 'login_alerts' => true, 'transaction_alerts' => true, 'marketing' => false];
        $prefs = $row ? json_decode((string) ($row['preferences'] ?? '{}'), true) : [];

        json_response(['success' => true, 'data' => ['preferences' => array_merge($defaults, is_array($prefs) ? $prefs : [])]]);
    }

    private function updatePreferences(): void
    {
        $payload = request_json_body();
        $userId = (int) $this->user['id'];

        $stmt = $this->db->prepare('SELECT preferences FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        $defaults = ['notify_sms' => true, 'notify_email' => true, 'notify_push' => true, 'two_factor_enabled' => false, 'login_alerts' => true, 'transaction_alerts' => true, 'marketing' => false];
        $existing = $row ? json_decode((string) ($row['preferences'] ?? '{}'), true) : [];
        $existing = array_merge($defaults, is_array($existing) ? $existing : []);

        foreach ($existing as $key => $value) {
            if (isset($payload[$key])) {
                $existing[$key] = (bool) $payload[$key];
            }
        }

        $updateStmt = $this->db->prepare('UPDATE user_onboarding SET preferences = :prefs, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
        $updateStmt->execute([':prefs' => json_encode($existing), ':uid' => $userId]);

        json_response(['success' => true, 'data' => ['preferences' => $existing]]);
    }

    private function enableTwoFactor(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('SELECT preferences FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        $prefs = $row ? json_decode((string) ($row['preferences'] ?? '{}'), true) : [];
        $prefs['two_factor_enabled'] = true;

        $updateStmt = $this->db->prepare('UPDATE user_onboarding SET preferences = :prefs, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
        $updateStmt->execute([':prefs' => json_encode($prefs), ':uid' => $userId]);

        json_response(['success' => true, 'data' => ['two_factor_enabled' => true]]);
    }

    private function disableTwoFactor(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare('SELECT preferences FROM user_onboarding WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        $prefs = $row ? json_decode((string) ($row['preferences'] ?? '{}'), true) : [];
        $prefs['two_factor_enabled'] = false;

        $updateStmt = $this->db->prepare('UPDATE user_onboarding SET preferences = :prefs, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
        $updateStmt->execute([':prefs' => json_encode($prefs), ':uid' => $userId]);

        json_response(['success' => true, 'data' => ['two_factor_enabled' => false]]);
    }

    private function profileLinkedAccounts(): void
    {
        $userId = (int) $this->user['id'];
        json_response(['success' => true, 'data' => [
            'linked_accounts' => $this->linkedAccounts->listByUser($userId),
        ]]);
    }

    private function profileActivity(): void
    {
        $userId = (int) $this->user['id'];
        $stmt = $this->db->prepare(
            'SELECT transaction_reference, type, amount, currency, total_amount, status, recipient_name, provider_name, created_at '
            . 'FROM transactions WHERE user_id = :uid ORDER BY created_at DESC LIMIT 15'
        );
        $stmt->execute([':uid' => $userId]);
        json_response(['success' => true, 'data' => ['activity' => $stmt->fetchAll()]]);
    }

    private function adminOverview(): void
    {
        $users = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $agents = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'")->fetchColumn();
        $transactions = (int) $this->db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $volume = (int) $this->db->query('SELECT COALESCE(SUM(total_amount), 0) FROM transactions')->fetchColumn();

        json_response(['success' => true, 'data' => compact('users', 'agents', 'transactions', 'volume')]);
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
        if ($status === 'in_progress') {
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

        $this->ledger->updateStatus((int) $transaction['id'], 'in_progress');

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

        if (($transaction['status'] ?? '') !== 'in_progress') {
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

    private function chartFromAccounts(array $accounts, array $totals): array
    {
        $seed = max(20, (int) (($totals['income'] + $totals['outcome'] + array_sum(array_column($accounts, 'balance'))) / 1000));

        return array_map(static fn (int $index): int => max(18, min(96, ($seed + ($index * 9)) % 100)), range(0, 6));
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/(?!^\+)[^\d]/', '', trim($phone)) ?? '';
    }

    private function requireMethod(string $actualMethod, string $expectedMethod): void
    {
        if ($actualMethod !== $expectedMethod) {
            json_response(['success' => false, 'error' => ['code' => 'method_not_allowed', 'message' => 'Méthode non autorisée.']], 405);
        }
    }

    private function transfer(): void
    {
        $payload = request_json_body();
        $recipient = preg_replace('/\D+/', '', (string) ($payload['recipient'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'CDF')));
        $pin = trim((string) ($payload['pin'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

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

        try {
            $this->accounts->ensureBalance($userId, $currency, $amount);
        } catch (RuntimeException $e) {
            json_response(['success' => false, 'error' => ['code' => 'insufficient_balance', 'message' => 'Solde insuffisant.']], 422);
        }

        $this->db->beginTransaction();

        try {
            $reference = 'TR-' . date('ymdHis') . '-' . random_int(1000, 9999);

            $senderTxnStmt = $this->db->prepare(
                'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $senderTxnStmt->execute([
                ':idempotency_key' => bin2hex(random_bytes(16)),
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

        $fees = (int) round($amount * 0.015);
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

        $fees = (int) round($amount * 0.025);
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

    private function rollbackIfNeeded(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
