<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/MigrationRunner.php';
require_once __DIR__ . '/app/core/Auth.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self' http://localhost:8080 http://127.0.0.1:8080; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json_body(): array
{
    $rawBody = (string) file_get_contents('php://input');
    $decoded = json_decode($rawBody, true);

    return is_array($decoded) ? $decoded : [];
}

MigrationRunner::run();

function backend_base_url(): string
{
    $configured = trim((string) getenv('AFRICO_BACKEND_BASE_URL'));

    return rtrim($configured !== '' ? $configured : 'http://localhost:8080/api', '/');
}

function proxy_backend_request(string $path): void
{
    $backendPath = substr($path, 4);
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $targetUrl = backend_base_url() . $backendPath . ($query !== '' ? '?' . $query : '');
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $rawBody = (string) file_get_contents('php://input');

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest',
    ];

    $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($authorization !== '') {
        $headers[] = 'Authorization: ' . $authorization;
    }

    $curl = curl_init($targetUrl);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
    ]);

    if (!in_array($method, ['GET', 'HEAD'], true)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $rawBody);
    }

    $response = curl_exec($curl);
    if ($response === false) {
        $message = curl_error($curl) ?: 'Backend indisponible.';
        curl_close($curl);
        json_response(['error' => ['code' => 'backend_unavailable', 'message' => $message]], 502);
    }

    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr((string) $response, 0, $headerSize);
    $responseBody = substr((string) $response, $headerSize);
    curl_close($curl);

    http_response_code($status > 0 ? $status : 502);
    foreach (explode("\n", $responseHeaders) as $headerLine) {
        $headerLine = trim($headerLine);
        if ($headerLine === '' || !str_contains($headerLine, ':')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode(':', $headerLine, 2));
        $lowerName = strtolower($name);
        if (in_array($lowerName, ['content-type', 'content-length'], true)) {
            header($name . ': ' . $value);
        }
    }

    if ($responseBody !== '') {
        echo $responseBody;
    }
    exit;
}

function handle_api_request(string $path): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($path === '/api/me') {
        $path = '/api/auth/me';
    }

    if (str_starts_with($path, '/api/auth/')) {
        if (handle_auth_api_request(Database::getInstance()->getConnection(), $path)) {
            return;
        }
    }

    if (str_starts_with($path, '/api/app/')) {
        $db = Database::getInstance()->getConnection();
        $user = get_authenticated_user($db);
        if ($user === null) {
            json_response(['success' => false, 'error' => ['code' => 'unauthenticated', 'message' => 'Vous devez vous connecter.']], 401);
        }

        if (!(bool) ($user['onboarding_completed'] ?? false)) {
            json_response(['success' => false, 'error' => ['code' => 'onboarding_required', 'message' => 'Veuillez terminer l’onboarding.']], 409);
        }

        $controllers = [
            new ProfileController($db, $user),
            new BillController($db, $user),
            new BankingController($db, $user),
            new WithdrawController($db, $user),
            new AppController($db, $user),
        ];

        foreach ($controllers as $controller) {
            if ($controller->handle($path)) {
                return;
            }
        }
    }

    if (!in_array($path, ['/api/ussd/session'], true)) {
        proxy_backend_request($path);
    }

    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
    }

    $payload = request_json_body();

    if ($path === '/api/ussd/session') {
        $state = (string) ($payload['state'] ?? 'dial');
        $input = trim((string) ($payload['input'] ?? ''));
        $db = Database::getInstance()->getConnection();
        $user = get_authenticated_user($db);

        $menuScreen = "AFRICO CASH\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter";

        if ($state === 'dial') {
            if ($input !== '*400#') {
                json_response([
                    'success' => true,
                    'state' => 'dial',
                    'screen' => "Composez *400#\npuis appuyez sur Appeler.",
                ]);
            }

            unset($_SESSION['ussd']);
            json_response(['success' => true, 'state' => 'menu', 'screen' => $menuScreen]);
        }

        if ($state === 'menu') {
            if ($input === '0') {
                json_response(['success' => true, 'state' => 'menu', 'screen' => $menuScreen]);
            }
            if ($input === '1') {
                if ($user === null) {
                    json_response(['success' => true, 'state' => 'menu', 'screen' => "Connectez-vous d'abord sur Africo Cash.\n{$menuScreen}"]);
                }
                $accounts = (new Account($db))->listByUser((int) $user['id']);
                $lines = ["SOLDE AFRICO CASH"];
                foreach ($accounts as $acc) {
                    $lines[] = "{$acc['currency']}: {$acc['formatted_balance']}";
                }
                $lines[] = "0. Menu";
                json_response(['success' => true, 'state' => 'menu', 'screen' => implode("\n", $lines)]);
            }
            if ($input === '2') {
                json_response(['success' => true, 'state' => 'send_recipient', 'screen' => "ENVOYER ARGENT\nEntrez le numéro du destinataire\n(8 chiffres)\n0. Annuler"]);
            }
            if ($input === '3') {
                json_response(['success' => true, 'state' => 'withdraw_amount', 'screen' => "RETRAIT DAB\nEntrez le montant en CDF\n0. Annuler"]);
            }
            if ($input === '4') {
                json_response(['success' => true, 'state' => 'dial', 'screen' => "Session terminée.\nComposez *400# pour recommencer."]);
            }
            json_response(['success' => true, 'state' => 'menu', 'screen' => "Option inconnue.\n{$menuScreen}"]);
        }

        if (in_array($state, ['send_recipient', 'send_amount', 'send_confirm', 'withdraw_amount', 'withdraw_confirm'], true)) {
            if ($user === null) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Connectez-vous d'abord sur\nAfrico Cash.\n{$menuScreen}"]);
            }
        }

        if ($state === 'send_recipient') {
            if ($input === '0') {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Opération annulée.\n{$menuScreen}"]);
            }
            if (!preg_match('/^\d{8}$/', $input)) {
                json_response(['success' => true, 'state' => 'send_recipient', 'screen' => "Numéro invalide (8 chiffres).\nEntrez le numéro du destinataire\n0. Annuler"]);
            }
            if ($input === $user['africo_number']) {
                json_response(['success' => true, 'state' => 'send_recipient', 'screen' => "Vous ne pouvez pas vous envoyer\nde l'argent à vous-même.\nEntrez le numéro du destinataire\n0. Annuler"]);
            }

            $stmt = $db->prepare('SELECT id, full_name FROM users WHERE afric_number = :number AND is_active = 1 LIMIT 1');
            $stmt->execute([':number' => $input]);
            $recipient = $stmt->fetch();
            if (!$recipient) {
                json_response(['success' => true, 'state' => 'send_recipient', 'screen' => "Destinataire introuvable.\nEntrez le numéro du destinataire\n0. Annuler"]);
            }

            $_SESSION['ussd'] = ['recipient' => $input, 'recipient_name' => $recipient['full_name']];
            json_response(['success' => true, 'state' => 'send_amount', 'screen' => "ENVOYER ARGENT\nÀ: {$recipient['full_name']}\nMontant en CDF\n0. Annuler"]);
        }

        if ($state === 'send_amount') {
            if ($input === '0' || !isset($_SESSION['ussd']['recipient'])) {
                unset($_SESSION['ussd']);
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Opération annulée.\n{$menuScreen}"]);
            }

            $amount = (int) preg_replace('/\D+/', '', $input);
            if ($amount < 100) {
                json_response(['success' => true, 'state' => 'send_amount', 'screen' => "Montant minimum: 100 CDF.\nEntrez le montant en CDF\n0. Annuler"]);
            }
            if ($amount > 1000000) {
                json_response(['success' => true, 'state' => 'send_amount', 'screen' => "Montant maximum: 1 000 000 CDF.\nEntrez le montant en CDF\n0. Annuler"]);
            }

            $_SESSION['ussd']['amount'] = $amount;
            json_response([
                'success' => true,
                'state' => 'send_confirm',
                'screen' => "ENVOYER ARGENT\nÀ: {$_SESSION['ussd']['recipient_name']}\nMontant: " . number_format($amount, 0, ',', ' ') . " CDF\n1. Confirmer\n0. Annuler",
            ]);
        }

        if ($state === 'send_confirm') {
            if ($input === '0') {
                unset($_SESSION['ussd']);
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Opération annulée.\n{$menuScreen}"]);
            }
            if ($input !== '1') {
                json_response(['success' => true, 'state' => 'send_confirm', 'screen' => "Option inconnue.\n1. Confirmer\n0. Annuler"]);
            }

            $context = $_SESSION['ussd'] ?? [];
            $recipient = (string) ($context['recipient'] ?? '');
            $amount = (int) ($context['amount'] ?? 0);
            unset($_SESSION['ussd']);

            if ($recipient === '' || $amount <= 0) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Session expirée.\n{$menuScreen}"]);
            }

            $stmt = $db->prepare('SELECT id, full_name FROM users WHERE afric_number = :number AND is_active = 1 LIMIT 1');
            $stmt->execute([':number' => $recipient]);
            $recipientUser = $stmt->fetch();
            if (!$recipientUser) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Destinataire introuvable.\n{$menuScreen}"]);
            }

            $recipientAccountStmt = $db->prepare('SELECT 1 FROM accounts WHERE user_id = :uid AND currency = :cur LIMIT 1');
            $recipientAccountStmt->execute([':uid' => $recipientUser['id'], ':currency' => 'CDF']);
            if (!$recipientAccountStmt->fetch()) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Le destinataire n'a pas de compte CDF.\n{$menuScreen}"]);
            }

            $accounts = new Account($db);
            try {
                $accounts->ensureBalance((int) $user['id'], 'CDF', $amount);
            } catch (RuntimeException $e) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Solde insuffisant.\n{$menuScreen}"]);
            }

            $db->beginTransaction();
            try {
                $reference = 'USSD-' . date('ymdHis') . '-' . random_int(100, 999);

                $txnStmt = $db->prepare(
                    'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, metadata, created_at, completed_at) '
                    . 'VALUES (:ik, :ref, :uid, :type, :amount, :cur, 0, :amount, :status, :rtype, :rname, :raccount, :meta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $txnStmt->execute([
                    ':ik' => bin2hex(random_bytes(16)),
                    ':ref' => $reference,
                    ':uid' => (int) $user['id'],
                    ':type' => 'send',
                    ':amount' => $amount,
                    ':cur' => 'CDF',
                    ':status' => 'completed',
                    ':rtype' => 'user',
                    ':rname' => $recipientUser['full_name'],
                    ':raccount' => $recipient,
                    ':meta' => json_encode(['source' => 'ussd']),
                ]);
                $txnStmt->execute([
                    ':ik' => bin2hex(random_bytes(16)),
                    ':ref' => $reference . '-R',
                    ':uid' => (int) $recipientUser['id'],
                    ':type' => 'deposit',
                    ':amount' => $amount,
                    ':cur' => 'CDF',
                    ':status' => 'completed',
                    ':rtype' => 'user',
                    ':rname' => $user['full_name'],
                    ':raccount' => $user['africo_number'],
                    ':meta' => json_encode(['source' => 'ussd', 'parent_reference' => $reference]),
                ]);

                $accounts->move((int) $user['id'], 'CDF', -$amount);
                $accounts->move((int) $recipientUser['id'], 'CDF', $amount);
                $db->commit();

                json_response(['success' => true, 'state' => 'menu', 'screen' => "Transfert réussi!\nRéf: {$reference}\n" . number_format($amount, 0, ',', ' ') . " CDF\nvers {$recipientUser['full_name']}\n{$menuScreen}"]);
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Erreur lors du transfert.\n{$menuScreen}"]);
            }
        }

        if ($state === 'withdraw_amount') {
            if ($input === '0') {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Opération annulée.\n{$menuScreen}"]);
            }

            $amount = (int) preg_replace('/\D+/', '', $input);
            if ($amount < 1000) {
                json_response(['success' => true, 'state' => 'withdraw_amount', 'screen' => "Montant minimum: 1 000 CDF.\nEntrez le montant en CDF\n0. Annuler"]);
            }
            if ($amount > 1000000) {
                json_response(['success' => true, 'state' => 'withdraw_amount', 'screen' => "Montant maximum: 1 000 000 CDF.\nEntrez le montant en CDF\n0. Annuler"]);
            }

            $_SESSION['ussd'] = ['withdraw_amount' => $amount];
            json_response([
                'success' => true,
                'state' => 'withdraw_confirm',
                'screen' => "RETRAIT DAB\nMontant: " . number_format($amount, 0, ',', ' ') . " CDF\n1. Confirmer\n0. Annuler",
            ]);
        }

        if ($state === 'withdraw_confirm') {
            if ($input === '0') {
                unset($_SESSION['ussd']);
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Opération annulée.\n{$menuScreen}"]);
            }
            if ($input !== '1') {
                json_response(['success' => true, 'state' => 'withdraw_confirm', 'screen' => "Option inconnue.\n1. Confirmer\n0. Annuler"]);
            }

            $amount = (int) ($_SESSION['ussd']['withdraw_amount'] ?? 0);
            unset($_SESSION['ussd']);

            if ($amount <= 0) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Session expirée.\n{$menuScreen}"]);
            }

            $atmCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            try {
                (new Ledger($db))->record((int) $user['id'], [
                    'type' => 'withdraw',
                    'amount' => $amount,
                    'currency' => 'CDF',
                    'fees' => 0,
                    'total_amount' => $amount,
                    'status' => 'pending',
                    'recipient_type' => 'atm',
                    'recipient_name' => 'Retrait DAB USSD',
                    'atm_code' => $atmCode,
                    'metadata' => ['source' => 'ussd', 'expires_at' => date('Y-m-d H:i:s', time() + 600)],
                ]);

                json_response(['success' => true, 'state' => 'menu', 'screen' => "Retrait DAB enregistré!\nCode: {$atmCode}\n" . number_format($amount, 0, ',', ' ') . " CDF\nValable 10 minutes\n{$menuScreen}"]);
            } catch (Exception $e) {
                json_response(['success' => true, 'state' => 'menu', 'screen' => "Erreur lors du retrait.\n{$menuScreen}"]);
            }
        }

        json_response(['success' => true, 'state' => 'dial', 'screen' => 'Session réinitialisée. Composez *400#.']);
    }

    json_response(['success' => false, 'message' => 'API introuvable.'], 404);
}

if (str_starts_with($requestPath, '/api/')) {
    handle_api_request($requestPath);
}

$pages = require __DIR__ . '/app/config/pages.php';
require __DIR__ . '/app/core/Router.php';

$requestedPage = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$route = Router::resolve($pages, $_SERVER['REQUEST_URI'] ?? '/', $requestedPage ?: null);
$currentPage = $route['page'];
$pageKey = $route['key'];

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST' && in_array($pageKey, ['login', 'register', 'onboarding'], true)) {
    $db = Database::getInstance()->getConnection();
    $authController = new AuthController($db);
    if ($pageKey === 'login') {
        $authController->handleLoginForm();
    } elseif ($pageKey === 'register') {
        $authController->handleRegisterForm();
    } else {
        $authController->handleOnboardingForm();
    }
}

$currentUser = null;
$db = Database::getInstance()->getConnection();
if (($currentPage['section'] ?? null) === 'app' || $pageKey === 'onboarding') {
    $currentUser = get_authenticated_user($db);

    if ($currentUser === null) {
        header('Location: ' . route_path('login') . '?return=' . rawurlencode((string) ($currentPage['path'] ?? '/dashboard')));
        exit;
    }

    if (($currentPage['section'] ?? null) === 'app' && !(bool) ($currentUser['onboarding_completed'] ?? false)) {
        header('Location: ' . route_path('onboarding'));
        exit;
    }

    if ($pageKey === 'onboarding' && (bool) ($currentUser['onboarding_completed'] ?? false)) {
        header('Location: ' . route_path('dashboard'));
        exit;
    }
}

if ($method === 'POST' && $currentUser !== null) {
    if ($pageKey === 'transactions') {
        if (($_POST['action'] ?? '') === 'send') {
            (new TransactionController($db))->send();
        }
    }

    if ($pageKey === 'banking') {
        if (($_POST['action'] ?? '') === 'transfer') {
            (new TransactionController($db))->handleBankTransfer();
        }
    }

}

if ($route['status'] !== 200) {
    http_response_code($route['status']);
}

function asset_path(string $path): string
{
    $normalizedPath = '/' . ltrim($path, '/');
    $absolutePath = __DIR__ . $normalizedPath;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';

    return htmlspecialchars($normalizedPath . '?v=' . $version, ENT_QUOTES, 'UTF-8');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function route_path(string $key): string
{
    global $pages;

    return e((string) ($pages[$key]['path'] ?? '/'));
}

require __DIR__ . '/app/partials/document_start.php';
require __DIR__ . '/' . ltrim($currentPage['view'], '/');
require __DIR__ . '/app/partials/document_end.php';
