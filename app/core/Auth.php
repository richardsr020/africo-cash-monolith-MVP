<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Account.php';
require_once dirname(__DIR__) . '/models/AuthSession.php';
require_once dirname(__DIR__) . '/models/Ledger.php';
require_once dirname(__DIR__) . '/models/LinkedAccount.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/AppController.php';

function handle_auth_api_request(PDO $db, string $path): bool
{
    return (new AuthController($db))->handle($path);
}

function get_authenticated_user(PDO $db): ?array
{
    return (new AuthController($db))->currentUser();
}
