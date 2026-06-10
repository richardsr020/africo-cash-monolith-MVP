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

        if ((new AppController($db, $user))->handle($path)) {
            return;
        }
    }

    if (!in_array($path, ['/api/dab/withdraw', '/api/ussd/session'], true)) {
        proxy_backend_request($path);
    }

    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
    }

    $payload = request_json_body();

    if ($path === '/api/dab/withdraw') {
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper((string) ($payload['currency'] ?? 'CDF'));
        $code = preg_replace('/\D+/', '', (string) ($payload['code'] ?? ''));
        $pin = preg_replace('/\D+/', '', (string) ($payload['pin'] ?? ''));
        $balances = ['CDF' => 2450000, 'USD' => 860];

        if (!isset($balances[$currency]) || $amount < 1000 || $amount > $balances[$currency] || strlen($code) !== 6 || strlen($pin) !== 4) {
            json_response([
                'success' => false,
                'message' => 'Retrait refusé. Vérifiez le montant, le code et le PIN.',
            ], 422);
        }

        json_response([
            'success' => true,
            'message' => 'Retrait autorisé. Veuillez prendre vos billets.',
            'transaction' => [
                'reference' => 'DAB-' . date('His') . '-' . random_int(100, 999),
                'amount' => $amount,
                'currency' => $currency,
                'remaining_balance' => $balances[$currency] - $amount,
            ],
        ]);
    }

    if ($path === '/api/ussd/session') {
        $state = (string) ($payload['state'] ?? 'dial');
        $input = trim((string) ($payload['input'] ?? ''));

        if ($state === 'dial') {
            if ($input !== '*144#' && $input !== '*123#') {
                json_response([
                    'success' => true,
                    'state' => 'dial',
                    'screen' => "Composez *144# ou *123#\npuis appuyez sur Appeler.",
                ]);
            }

            json_response([
                'success' => true,
                'state' => 'menu',
                'screen' => "AFRICO CASH\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter",
            ]);
        }

        if ($state === 'menu') {
            $responses = [
                '1' => ['balance', "Solde Africo Cash\nCDF 2 450 000\nUSD 860\n0. Menu"],
                '2' => ['send_amount', "Envoyer argent\nEntrez le montant CDF\n0. Annuler"],
                '3' => ['withdraw_amount', "Retrait DAB\nEntrez le montant CDF\n0. Annuler"],
                '4' => ['dial', "Session terminée.\nComposez *144# pour recommencer."],
                '0' => ['menu', "AFRICO CASH\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter"],
            ];

            [$nextState, $screen] = $responses[$input] ?? ['menu', "Option inconnue.\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter"];
            json_response(['success' => true, 'state' => $nextState, 'screen' => $screen]);
        }

        if ($state === 'balance') {
            json_response([
                'success' => true,
                'state' => 'menu',
                'screen' => "AFRICO CASH\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter",
            ]);
        }

        if ($state === 'send_amount' || $state === 'withdraw_amount') {
            $amount = (int) preg_replace('/\D+/', '', $input);

            if ($amount <= 0) {
                json_response([
                    'success' => true,
                    'state' => 'menu',
                    'screen' => "Opération annulée.\n1. Solde\n2. Envoyer argent\n3. Retrait DAB\n4. Quitter",
                ]);
            }

            $screen = $state === 'send_amount'
                ? "Transfert simulé\nMontant: {$amount} CDF\nStatut: prêt à confirmer\n0. Menu"
                : "Code DAB généré\nMontant: {$amount} CDF\nCode: " . random_int(100000, 999999) . "\n0. Menu";

            json_response(['success' => true, 'state' => 'menu', 'screen' => $screen]);
        }

        json_response(['success' => true, 'state' => 'dial', 'screen' => 'Session réinitialisée. Composez *144#.']);
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
