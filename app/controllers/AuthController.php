<?php

declare(strict_types=1);

final class AuthController
{
    private User $users;

    private Account $accounts;

    private AuthSession $sessions;

    public function __construct(private PDO $db)
    {
        $this->users = new User($db);
        $this->accounts = new Account($db);
        $this->sessions = new AuthSession($db);
    }

    public function handle(string $path): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($path === '/api/auth/register-intent') {
            $this->requireMethod($method, 'POST');
            $this->registerIntent();
            return true;
        }

        if ($path === '/api/auth/register') {
            $this->requireMethod($method, 'POST');
            $this->register();
            return true;
        }

        if ($path === '/api/auth/login') {
            $this->requireMethod($method, 'POST');
            $this->login();
            return true;
        }

        if ($path === '/api/auth/me') {
            $this->requireMethod($method, 'GET');
            $this->me();
            return true;
        }

        if ($path === '/api/auth/onboarding') {
            $this->requireMethod($method, 'POST');
            $this->onboarding();
            return true;
        }

        if ($path === '/api/auth/logout') {
            $this->requireMethod($method, 'POST');
            $this->logout();
            return true;
        }

        return false;
    }

    public function currentUser(): ?array
    {
        if (!empty($_SESSION['auth_user_id'])) {
            $user = $this->users->findPublicById((int) $_SESSION['auth_user_id']);
            if ($user !== null && (bool) $user['is_active']) {
                return $user;
            }
        }

        $token = $this->bearerToken();
        if ($token === null) {
            return null;
        }

        $user = $this->sessions->findUserByToken($token);
        if ($user !== null && (bool) $user['is_active']) {
            $_SESSION['auth_user_id'] = (int) $user['id'];
            return $user;
        }

        return null;
    }

    private function registerIntent(): void
    {
        $payload = request_json_body();
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));

        if ($fullName === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Veuillez renseigner votre nom et votre téléphone.']], 422);
        }

        json_response(['success' => true, 'message' => 'Votre demande a bien été enregistrée. Nous vous recontacterons très bientôt.']);
    }

    private function register(): void
    {
        $payload = request_json_body();
        $email = $this->normalizeEmail((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Email et mot de passe sont obligatoires.']], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Adresse email invalide.']], 422);
        }

        if (strlen($password) < 8) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le mot de passe doit contenir au moins 8 caractères.']], 422);
        }

        if ($this->users->emailExists($email)) {
            json_response(['success' => false, 'error' => ['code' => 'email_taken', 'message' => 'Cet email est déjà associé à un compte.']], 409);
        }

        $africoNumber = $this->users->generateUniqueAfricoNumber();
        $fallbackName = $this->nameFromEmail($email);

        try {
            $this->db->beginTransaction();
            $userId = $this->users->createCustomer([
                'afric_number' => $africoNumber,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'full_name' => $fallbackName,
                'address' => '',
                'profession' => '',
                'notification_phone' => '+243000000000',
                'country' => 'CD',
                'account_type' => 'personal',
            ]);
            $this->users->createOnboardingShell($userId);
            $this->accounts->createDefaultWallets($userId);
            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'registration_failed', 'message' => 'Impossible de créer le compte pour le moment.']], 500);
        }

        $user = $this->users->findPublicById($userId);
        if ($user === null) {
            json_response(['success' => false, 'error' => ['code' => 'registration_failed', 'message' => 'Le compte a été créé mais ne peut pas être chargé.']], 500);
        }

        $token = $this->startSession((int) $user['id']);
        json_response(['success' => true, 'data' => ['user' => $user, 'access_token' => $token, 'next' => '/onboarding']], 201);
    }

    private function login(): void
    {
        $payload = request_json_body();
        $email = $this->normalizeEmail((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Email et mot de passe sont obligatoires.']], 422);
        }

        $user = $this->users->findForLogin($email);
        if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
            json_response(['success' => false, 'error' => ['code' => 'invalid_credentials', 'message' => 'Email ou mot de passe incorrect.']], 401);
        }

        if (!((bool) $user['is_active'])) {
            json_response(['success' => false, 'error' => ['code' => 'account_disabled', 'message' => 'Ce compte est désactivé.']], 403);
        }

        $token = $this->startSession((int) $user['id']);
        $normalizedUser = User::normalize($user);
        json_response(['success' => true, 'data' => [
            'user' => $normalizedUser,
            'access_token' => $token,
            'next' => $normalizedUser['onboarding_completed'] ? '/dashboard' : '/onboarding',
        ]]);
    }

    private function me(): void
    {
        $user = $this->currentUser();
        if ($user === null) {
            json_response(['success' => false, 'error' => ['code' => 'unauthenticated', 'message' => 'Vous devez vous connecter.']], 401);
        }

        json_response(['success' => true, 'data' => ['user' => $user]]);
    }

    private function onboarding(): void
    {
        $user = $this->currentUser();
        if ($user === null) {
            json_response(['success' => false, 'error' => ['code' => 'unauthenticated', 'message' => 'Vous devez vous connecter.']], 401);
        }

        $payload = request_json_body();
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $primaryUse = trim((string) ($payload['primary_use'] ?? 'personal'));
        $monthlyVolume = trim((string) ($payload['monthly_volume'] ?? 'starter'));
        $defaultCurrency = strtoupper(trim((string) ($payload['default_currency'] ?? 'CDF')));
        $mobileOperator = trim((string) ($payload['mobile_operator'] ?? ''));
        $securityPin = preg_replace('/\D+/', '', (string) ($payload['security_pin'] ?? ''));
        $accountType = trim((string) ($payload['account_type'] ?? 'personal'));

        if ($fullName === '' || $phone === '' || $city === '' || $securityPin === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Profil, téléphone, ville et PIN sont requis.']], 422);
        }

        if (!$this->isValidPhone($phone)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Téléphone invalide.']], 422);
        }

        if (strlen($securityPin) !== 4) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Le PIN doit contenir exactement 4 chiffres.']], 422);
        }

        if (!in_array($defaultCurrency, ['CDF', 'USD'], true)) {
            $defaultCurrency = 'CDF';
        }

        if (!in_array($accountType, ['personal', 'business', 'agent'], true)) {
            $accountType = 'personal';
        }

        $this->users->completeOnboarding((int) $user['id'], [
            'preferred_name' => trim((string) ($payload['preferred_name'] ?? $fullName)),
            'full_name' => $fullName,
            'phone' => $phone,
            'city' => $city,
            'address' => $city,
            'profession' => trim((string) ($payload['profession'] ?? '')),
            'country' => 'CD',
            'account_type' => $accountType,
            'primary_use' => $primaryUse,
            'monthly_volume' => $monthlyVolume,
            'default_currency' => $defaultCurrency,
            'mobile_operator' => $mobileOperator,
            'security_pin_hash' => password_hash($securityPin, PASSWORD_BCRYPT),
        ]);

        json_response(['success' => true, 'data' => [
            'user' => $this->users->findPublicById((int) $user['id']),
            'next' => '/dashboard',
        ]]);
    }

    private function logout(): void
    {
        $user = $this->currentUser();
        if ($user !== null) {
            $this->sessions->revokeByUser((int) $user['id']);
        }

        session_unset();
        session_destroy();
        json_response(['success' => true, 'message' => 'Déconnecté avec succès.']);
    }

    private function startSession(int $userId): string
    {
        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = $userId;

        return $this->sessions->create(
            $userId,
            (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')
        );
    }

    private function bearerToken(): ?string
    {
        $authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
        if ($authorization === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authorization = trim((string) ($headers['Authorization'] ?? $headers['authorization'] ?? ''));
        }

        if (!str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authorization, 7));

        return $token !== '' ? $token : null;
    }

    private function requireMethod(string $actualMethod, string $expectedMethod): void
    {
        if ($actualMethod !== $expectedMethod) {
            json_response(['success' => false, 'error' => ['code' => 'method_not_allowed', 'message' => 'Méthode non autorisée.']], 405);
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/(?!^\+)[^\d]/', '', trim($phone)) ?? '';
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function nameFromEmail(string $email): string
    {
        $localPart = (string) strtok($email, '@');
        $name = trim((string) preg_replace('/[._-]+/', ' ', $localPart));

        return $name !== '' ? ucwords($name) : 'Client Africo';
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^\+?[0-9]{8,14}$/', $phone) && strlen($phone) >= 9 && strlen($phone) <= 15;
    }

    private function rollbackIfNeeded(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
