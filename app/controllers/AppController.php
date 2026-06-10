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

        if ($route === '/transactions' && $method === 'GET') {
            $this->transactions();
            return true;
        }

        if ($route === '/transactions' && $method === 'POST') {
            $this->createTransaction();
            return true;
        }

        if ($route === '/mobile-money') {
            $this->requireMethod($method, 'GET');
            $this->mobileMoney();
            return true;
        }

        if ($route === '/mobile-money/link') {
            $this->requireMethod($method, 'POST');
            $this->linkMobileMoney();
            return true;
        }

        if ($route === '/banking/transfer') {
            $this->requireMethod($method, 'POST');
            $this->bankTransfer();
            return true;
        }

        if ($route === '/atm/code') {
            $this->requireMethod($method, 'POST');
            $this->atmCode();
            return true;
        }

        if ($route === '/bills/verify') {
            $this->requireMethod($method, 'POST');
            $this->verifyBill();
            return true;
        }

        if ($route === '/profile' && $method === 'GET') {
            json_response(['success' => true, 'data' => ['user' => $this->user]]);
            return true;
        }

        if ($route === '/profile' && $method === 'POST') {
            $this->updateProfile();
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

    private function transactions(): void
    {
        json_response(['success' => true, 'data' => ['transactions' => $this->ledger->recentByUser((int) $this->user['id'], 20)]]);
    }

    private function createTransaction(): void
    {
        $payload = request_json_body();
        $type = $this->mapTransactionType((string) ($payload['type'] ?? 'send'));
        $currency = strtoupper((string) ($payload['currency'] ?? 'CDF'));
        $amount = $this->amountToCents($payload['amount'] ?? 0);
        $beneficiary = trim((string) ($payload['beneficiary'] ?? $payload['recipient'] ?? 'Bénéficiaire Africo'));
        $fees = $this->feesFor($type, $amount);
        $userId = (int) $this->user['id'];

        if ($amount <= 0 || !in_array($currency, ['CDF', 'USD'], true)) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Montant ou devise invalide.']], 422);
        }

        try {
            $this->db->beginTransaction();
            if ($type === 'deposit') {
                $this->accounts->move($userId, $currency, $amount);
            } else {
                $this->accounts->ensureBalance($userId, $currency, $amount + $fees);
                $this->accounts->move($userId, $currency, -($amount + $fees));
            }

            $transaction = $this->ledger->record($userId, [
                'type' => $type,
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'recipient_type' => 'customer',
                'recipient_name' => $beneficiary,
                'recipient_account' => $beneficiary,
                'provider_name' => 'Africo Cash',
            ]);
            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'transaction_failed', 'message' => $throwable->getMessage()]], 422);
        }

        json_response(['success' => true, 'data' => ['transaction' => $transaction, 'accounts' => $this->accounts->listByUser($userId)]], 201);
    }

    private function mobileMoney(): void
    {
        json_response(['success' => true, 'data' => [
            'providers' => [
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

    private function bankTransfer(): void
    {
        $payload = request_json_body();
        $bank = trim((string) ($payload['bank'] ?? 'Banque partenaire'));
        $account = trim((string) ($payload['account'] ?? ''));
        $amount = $this->amountToCents($payload['amount'] ?? 0);
        $currency = strtoupper((string) ($payload['currency'] ?? 'USD'));
        $fees = $this->feesFor('bank', $amount);
        $userId = (int) $this->user['id'];

        if ($account === '' || $amount <= 0) {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Compte et montant requis.']], 422);
        }

        try {
            $this->db->beginTransaction();
            $this->accounts->ensureBalance($userId, $currency, $amount + $fees);
            $this->accounts->move($userId, $currency, -($amount + $fees));
            $this->linkedAccounts->link($userId, 'bank', $bank, $bank . ' - ' . substr($account, -4), $account);
            $transaction = $this->ledger->record($userId, [
                'type' => 'bank',
                'amount' => $amount,
                'currency' => $currency,
                'fees' => $fees,
                'recipient_type' => 'bank',
                'recipient_name' => $bank,
                'recipient_account' => $account,
                'provider_name' => $bank,
            ]);
            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'banking_failed', 'message' => $throwable->getMessage()]], 422);
        }

        json_response(['success' => true, 'data' => ['transaction' => $transaction]], 201);
    }

    private function atmCode(): void
    {
        $payload = request_json_body();
        $amount = $this->amountToCents($payload['amount'] ?? 0);
        $currency = strtoupper((string) ($payload['currency'] ?? 'CDF'));
        $code = (string) random_int(100000, 999999);
        $userId = (int) $this->user['id'];

        try {
            $this->db->beginTransaction();
            $this->accounts->ensureBalance($userId, $currency, $amount);
            $this->accounts->move($userId, $currency, -$amount);
            $insertCode = $this->db->prepare('INSERT INTO atm_temp_codes (code, afric_number, amount, status, created_at, expires_at) VALUES (:code, :afric_number, :amount, :status, CURRENT_TIMESTAMP, datetime(CURRENT_TIMESTAMP, "+10 minutes"))');
            $insertCode->execute([
                ':code' => $code,
                ':afric_number' => $this->user['africo_number'],
                ':amount' => $amount,
                ':status' => 'active',
            ]);
            $transaction = $this->ledger->record($userId, [
                'type' => 'atm',
                'amount' => $amount,
                'currency' => $currency,
                'fees' => 0,
                'recipient_type' => 'atm',
                'recipient_name' => 'Retrait ATM',
                'provider_name' => 'Africo ATM',
                'atm_code' => $code,
            ]);
            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->rollbackIfNeeded();
            json_response(['success' => false, 'error' => ['code' => 'atm_failed', 'message' => $throwable->getMessage()]], 422);
        }

        json_response(['success' => true, 'data' => ['code' => $code, 'expires_in_minutes' => 10, 'transaction' => $transaction]], 201);
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

        json_response(['success' => true, 'data' => [
            'bill' => [
                'reference' => $reference,
                'service' => $service,
                'customer_name' => $this->user['full_name'],
                'amount' => $amount,
                'currency' => 'CDF',
                'status' => 'verified',
            ],
        ]]);
    }

    private function updateProfile(): void
    {
        $payload = request_json_body();
        $fullName = trim((string) ($payload['full_name'] ?? $this->user['full_name']));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $this->user['notification_phone']));
        $city = trim((string) ($payload['city'] ?? $this->user['onboarding']['city'] ?? ''));

        if ($fullName === '' || $phone === '') {
            json_response(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'Nom et téléphone requis.']], 422);
        }

        $statement = $this->db->prepare('UPDATE users SET full_name = :full_name, notification_phone = :phone, address = :city, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute([
            ':id' => (int) $this->user['id'],
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':city' => $city,
        ]);

        json_response(['success' => true, 'data' => ['user' => (new User($this->db))->findPublicById((int) $this->user['id'])]]);
    }

    private function adminOverview(): void
    {
        $users = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $agents = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'")->fetchColumn();
        $transactions = (int) $this->db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $volume = (int) $this->db->query('SELECT COALESCE(SUM(total_amount), 0) FROM transactions')->fetchColumn();

        json_response(['success' => true, 'data' => compact('users', 'agents', 'transactions', 'volume')]);
    }

    private function amountToCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function feesFor(string $type, int $amount): int
    {
        return match ($type) {
            'bank' => max(100, (int) round($amount * 0.015)),
            'withdraw', 'atm' => max(50, (int) round($amount * 0.01)),
            'send' => max(25, (int) round($amount * 0.006)),
            default => 0,
        };
    }

    private function mapTransactionType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            'dépôt', 'depot', 'deposit' => 'deposit',
            'retrait', 'withdraw' => 'withdraw',
            'conversion' => 'conversion',
            default => 'send',
        };
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

    private function rollbackIfNeeded(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
