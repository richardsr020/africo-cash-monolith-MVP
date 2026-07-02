<?php

declare(strict_types=1);

final class Ledger
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string,mixed> $data
     */
    public function record(int $userId, array $data): array
    {
        $reference = $data['reference'] ?? $this->generateReference((string) $data['type']);
        $statement = $this->db->prepare(
            'INSERT INTO transactions (idempotency_key, transaction_reference, user_id, type, amount, currency, fees, total_amount, status, recipient_type, recipient_name, recipient_account, provider_name, atm_code, metadata, created_at, completed_at) '
            . 'VALUES (:idempotency_key, :reference, :user_id, :type, :amount, :currency, :fees, :total_amount, :status, :recipient_type, :recipient_name, :recipient_account, :provider_name, :atm_code, :metadata, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            ':idempotency_key' => $data['idempotency_key'] ?? bin2hex(random_bytes(16)),
            ':reference' => $reference,
            ':user_id' => $userId,
            ':type' => $data['type'],
            ':amount' => $data['amount'],
            ':currency' => $data['currency'],
            ':fees' => $data['fees'] ?? 0,
            ':total_amount' => $data['total_amount'] ?? ((int) $data['amount'] + (int) ($data['fees'] ?? 0)),
            ':status' => $data['status'] ?? 'completed',
            ':recipient_type' => $data['recipient_type'] ?? null,
            ':recipient_name' => $data['recipient_name'] ?? null,
            ':recipient_account' => $data['recipient_account'] ?? null,
            ':provider_name' => $data['provider_name'] ?? null,
            ':atm_code' => $data['atm_code'] ?? null,
            ':metadata' => json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return $this->findByReference($userId, (string) $reference) ?? [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recentByUser(int $userId, int $limit = 8): array
    {
        $statement = $this->db->prepare(
            'SELECT transaction_reference, type, amount, currency, fees, total_amount, status, recipient_name, recipient_account, provider_name, atm_code, created_at '
            . 'FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([$this, 'normalize'], $statement->fetchAll());
    }

    public function totals(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT currency, "
            . "SUM(CASE WHEN type IN ('deposit', 'deposit_agent', 'deposit_bank', 'deposit_mobile_money') THEN amount ELSE 0 END) AS income, "
            . "SUM(CASE WHEN type NOT IN ('deposit', 'deposit_agent', 'deposit_bank', 'deposit_mobile_money', 'wallet_transfer', 'early_unlock') THEN total_amount ELSE 0 END) AS outcome, "
            . "COUNT(*) AS total_count "
            . "FROM transactions WHERE user_id = :user_id GROUP BY currency"
        );
        $statement->execute([':user_id' => $userId]);
        $rows = $statement->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['currency']] = [
                'income' => (int) ($row['income'] ?? 0),
                'outcome' => (int) ($row['outcome'] ?? 0),
                'total_count' => (int) ($row['total_count'] ?? 0),
            ];
        }

        foreach (['CDF', 'USD'] as $currency) {
            if (!isset($result[$currency])) {
                $result[$currency] = ['income' => 0, 'outcome' => 0, 'total_count' => 0];
            }
        }

        return $result;
    }

    public function findByAtmCode(string $atmCode): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, transaction_reference, type, amount, currency, fees, total_amount, status, user_id, recipient_name, recipient_account, provider_name, atm_code, metadata, created_at, completed_at '
            . 'FROM transactions WHERE atm_code = :atm_code ORDER BY created_at DESC LIMIT 1'
        );
        $statement->execute([':atm_code' => $atmCode]);
        $transaction = $statement->fetch();

        return is_array($transaction) ? $transaction : null;
    }

    public function updateStatus(int $transactionId, string $status): void
    {
        if ($status === 'completed') {
            $statement = $this->db->prepare(
                'UPDATE transactions SET status = :status, completed_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
        } else {
            $statement = $this->db->prepare(
                'UPDATE transactions SET status = :status WHERE id = :id'
            );
        }
        $statement->execute([
            ':status' => $status,
            ':id' => $transactionId,
        ]);
    }

    public function markAtmWithdrawn(int $transactionId): void
    {
        $statement = $this->db->prepare('SELECT metadata FROM transactions WHERE id = :id');
        $statement->execute([':id' => $transactionId]);
        $row = $statement->fetch();
        $metadata = $row ? json_decode($row['metadata'] ?: '{}', true) : [];
        $metadata['dab_withdrawn_at'] = date('Y-m-d H:i:s');

        $update = $this->db->prepare('UPDATE transactions SET metadata = :metadata WHERE id = :id');
        $update->execute([
            ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':id' => $transactionId,
        ]);
    }

    private function findByReference(int $userId, string $reference): ?array
    {
        $statement = $this->db->prepare(
            'SELECT transaction_reference, type, amount, currency, fees, total_amount, status, recipient_name, recipient_account, provider_name, atm_code, created_at '
            . 'FROM transactions WHERE user_id = :user_id AND transaction_reference = :reference LIMIT 1'
        );
        $statement->execute([':user_id' => $userId, ':reference' => $reference]);
        $transaction = $statement->fetch();

        return is_array($transaction) ? $this->normalize($transaction) : null;
    }

    private function generateReference(string $type): string
    {
        return strtoupper(substr(preg_replace('/[^a-z]/i', '', $type) ?: 'TX', 0, 3)) . '-' . date('ymdHis') . '-' . random_int(100, 999);
    }

    /**
     * @param array<string,mixed> $transaction
     * @return array<string,mixed>
     */
    private function normalize(array $transaction): array
    {
        return [
            'reference' => (string) $transaction['transaction_reference'],
            'type' => (string) $transaction['type'],
            'amount' => (int) $transaction['amount'],
            'currency' => (string) $transaction['currency'],
            'fees' => (int) $transaction['fees'],
            'total_amount' => (int) $transaction['total_amount'],
            'status' => (string) $transaction['status'],
            'recipient_name' => (string) ($transaction['recipient_name'] ?? ''),
            'recipient_account' => (string) ($transaction['recipient_account'] ?? ''),
            'provider_name' => (string) ($transaction['provider_name'] ?? ''),
            'atm_code' => (string) ($transaction['atm_code'] ?? ''),
            'created_at' => (string) $transaction['created_at'],
        ];
    }
}
