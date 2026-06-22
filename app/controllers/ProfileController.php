<?php

declare(strict_types=1);

final class ProfileController extends BaseController
{
    private LinkedAccount $linkedAccounts;

    public function __construct(PDO $db, array $user)
    {
        parent::__construct($db, $user);
        $this->linkedAccounts = new LinkedAccount($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = substr($path, strlen('/api/app'));

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

        if ($route === '/profile/forgot-pin') {
            $this->requireMethod($method, 'POST');
            $this->forgotPin();
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

        return false;
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

        $pwStmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $pwStmt->execute([':id' => (int) $this->user['id']]);
        $pwRow = $pwStmt->fetch();
        $storedHash = $pwRow ? (string) ($pwRow['password_hash'] ?? '') : '';

        if (!password_verify($currentPassword, $storedHash)) {
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

    private function forgotPin(): void
    {
        $payload = request_json_body();
        $password = (string) ($payload['password'] ?? '');
        $newPin = trim((string) ($payload['new_pin'] ?? ''));

        if ($password === '' || $newPin === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Mot de passe et nouveau PIN requis.']], 422);
        }

        if (!preg_match('/^\d{4}$/', $newPin)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir 4 chiffres.']], 422);
        }

        $pwStmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $pwStmt->execute([':id' => (int) $this->user['id']]);
        $pwRow = $pwStmt->fetch();
        $storedHash = $pwRow ? (string) ($pwRow['password_hash'] ?? '') : '';

        if (!password_verify($password, $storedHash)) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_password', 'message' => 'Mot de passe incorrect.']], 422);
        }

        $hash = password_hash($newPin, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE user_onboarding SET security_pin_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
        $stmt->execute([':hash' => $hash, ':uid' => (int) $this->user['id']]);

        json_response(['success' => true, 'data' => ['message' => 'PIN réinitialisé avec succès.']]);
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
}
