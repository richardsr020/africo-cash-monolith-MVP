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
            'INSERT INTO accounts (user_id, currency, wallet_type, balance, created_at, updated_at, version) '
            . 'VALUES (:user_id, :currency, :wallet_type, :balance, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'
        );

        $wallets = [
            ['CDF', 'current', 25000000],
            ['USD', 'current', 10000],
            ['CDF', 'savings', 0],
            ['USD', 'savings', 0],
        ];

        foreach ($wallets as [$currency, $walletType, $balance]) {
            $statement->execute([
                ':user_id' => $userId,
                ':currency' => $currency,
                ':wallet_type' => $walletType,
                ':balance' => $balance,
            ]);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listByUser(int $userId, ?string $walletType = null): array
    {
        $sql = 'SELECT id, currency, wallet_type, balance, updated_at FROM accounts WHERE user_id = :user_id';
        $params = [':user_id' => $userId];

        if ($walletType !== null) {
            $sql .= ' AND wallet_type = :wallet_type';
            $params[':wallet_type'] = $walletType;
        }

        $sql .= ' ORDER BY currency, wallet_type';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return array_map(static function (array $account): array {
            return [
                'id' => (int) $account['id'],
                'currency' => (string) $account['currency'],
                'wallet_type' => (string) ($account['wallet_type'] ?? 'current'),
                'balance' => (int) $account['balance'],
                'formatted_balance' => number_format(((int) $account['balance']) / 100, 2, ',', ' '),
                'updated_at' => (string) $account['updated_at'],
            ];
        }, $statement->fetchAll());
    }

    public function ensureBalance(int $userId, string $currency, int $amount, string $walletType = 'current'): void
    {
        $statement = $this->db->prepare(
            'UPDATE accounts SET version = version + 1, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :user_id AND currency = :currency AND wallet_type = :wallet_type AND balance >= :amount'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':currency' => $currency,
            ':wallet_type' => $walletType,
            ':amount' => $amount,
        ]);

        if ($statement->rowCount() === 0) {
            $exists = $this->db->prepare(
                'SELECT 1 FROM accounts WHERE user_id = :user_id AND currency = :currency AND wallet_type = :wallet_type'
            );
            $exists->execute([
                ':user_id' => $userId,
                ':currency' => $currency,
                ':wallet_type' => $walletType,
            ]);
            if (!$exists->fetchColumn()) {
                throw new RuntimeException('Compte introuvable.');
            }
            throw new RuntimeException('Solde insuffisant.');
        }
    }

    public function move(int $userId, string $currency, int $amountDelta, string $walletType = 'current'): void
    {
        $sql = 'UPDATE accounts SET balance = balance + :delta, version = version + 1, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :user_id AND currency = :currency AND wallet_type = :wallet_type';

        if ($amountDelta < 0) {
            $sql .= ' AND balance + :delta >= 0';
        }

        $statement = $this->db->prepare($sql);
        $statement->execute([
            ':delta' => $amountDelta,
            ':user_id' => $userId,
            ':currency' => $currency,
            ':wallet_type' => $walletType,
        ]);

        if ($amountDelta < 0 && $statement->rowCount() === 0) {
            throw new RuntimeException('Solde insuffisant.');
        }
    }

    public function transferBetweenWallets(int $userId, string $currency, int $amount, string $fromType, string $toType): void
    {
        $this->db->beginTransaction();
        try {
            $this->ensureBalance($userId, $currency, $amount, $fromType);
            $this->move($userId, $currency, -$amount, $fromType);
            $this->move($userId, $currency, $amount, $toType);
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
