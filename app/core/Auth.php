<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Account.php';
require_once dirname(__DIR__) . '/models/AuthSession.php';
require_once dirname(__DIR__) . '/models/Ledger.php';
require_once dirname(__DIR__) . '/models/LinkedAccount.php';
require_once dirname(__DIR__) . '/models/Transaction.php';
require_once dirname(__DIR__) . '/controllers/BaseController.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/AppController.php';
require_once dirname(__DIR__) . '/controllers/ProfileController.php';
require_once dirname(__DIR__) . '/controllers/BillController.php';
require_once dirname(__DIR__) . '/controllers/BankingController.php';
require_once dirname(__DIR__) . '/controllers/WithdrawController.php';
require_once dirname(__DIR__) . '/controllers/TransactionController.php';
require_once dirname(__DIR__) . '/models/SavingsGoal.php';
require_once dirname(__DIR__) . '/models/UserAlert.php';

function handle_auth_api_request(PDO $db, string $path): bool
{
    return (new AuthController($db))->handle($path);
}

function get_authenticated_user(PDO $db): ?array
{
    return (new AuthController($db))->currentUser();
}
