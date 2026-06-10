<?php

declare(strict_types=1);

final class Account
{
    public function __construct(private PDO $db)
    {
    }

    public function createDefaultWallets(int $userId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO accounts (user_id, currency, balance, created_at, updated_at, version) '
            . 'VALUES (:user_id, :currency, :balance, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'
        );

        foreach (['CDF' => 25000000, 'USD' => 10000] as $currency => $balance) {
            $statement->execute([
                ':user_id' => $userId,
                ':currency' => $currency,
                ':balance' => $balance,
            ]);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listByUser(int $userId): array
    {
        $statement = $this->db->prepare('SELECT id, currency, balance, updated_at FROM accounts WHERE user_id = :user_id ORDER BY currency');
        $statement->execute([':user_id' => $userId]);

        return array_map(static function (array $account): array {
            return [
                'id' => (int) $account['id'],
                'currency' => (string) $account['currency'],
                'balance' => (int) $account['balance'],
                'formatted_balance' => number_format(((int) $account['balance']) / 100, 2, ',', ' '),
                'updated_at' => (string) $account['updated_at'],
            ];
        }, $statement->fetchAll());
    }

    public function ensureBalance(int $userId, string $currency, int $amount): void
    {
        $statement = $this->db->prepare('SELECT balance FROM accounts WHERE user_id = :user_id AND currency = :currency LIMIT 1');
        $statement->execute([':user_id' => $userId, ':currency' => $currency]);
        $balance = $statement->fetchColumn();

        if ($balance === false || (int) $balance < $amount) {
            throw new RuntimeException('Solde insuffisant.');
        }
    }

    public function move(int $userId, string $currency, int $amountDelta): void
    {
        $statement = $this->db->prepare(
            'UPDATE accounts SET balance = balance + :delta, version = version + 1, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :user_id AND currency = :currency'
        );
        $statement->execute([
            ':delta' => $amountDelta,
            ':user_id' => $userId,
            ':currency' => $currency,
        ]);
    }
}
