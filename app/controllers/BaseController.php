<?php

declare(strict_types=1);

abstract class BaseController
{
    protected PDO $db;
    protected array $user;

    public function __construct(PDO $db, array $user)
    {
        $this->db = $db;
        $this->user = $user;
    }

    abstract public function handle(string $path): bool;

    protected function requireMethod(string $actualMethod, string $expectedMethod): void
    {
        if ($actualMethod !== $expectedMethod) {
            json_response(['success' => false, 'error' => ['code' => 'method_not_allowed', 'message' => 'Méthode non autorisée.']], 405);
        }
    }

    protected function rollbackIfNeeded(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/(?!^\+)[^\d]/', '', trim($phone)) ?? '';
    }

    protected function calculateNextPayDate(string $frequency, int $dayOfMonth): string
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

    protected function chartFromAccounts(array $accounts, array $totals): array
    {
        $chart = [];
        foreach ($accounts as $account) {
            $currency = $account['currency'];
            $balance = (int) $account['balance'];
            $currencyTotals = $totals[$currency] ?? ['income' => 0, 'outcome' => 0];
            $seed = max(20, (int) (($currencyTotals['income'] + $currencyTotals['outcome'] + $balance) / 1000));
            $chart[$currency] = array_map(static fn (int $index): int => max(18, min(96, ($seed + ($index * 9)) % 100)), range(0, 6));
        }

        return $chart;
    }
}
