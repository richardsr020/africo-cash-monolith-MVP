<?php

declare(strict_types=1);

final class SavingsConfig
{
    private const MODE_FLEXIBLE = 'flexible';
    private const MODE_LOCKED = 'locked';

    public function __construct(private PDO $db)
    {
    }

    public function getForUser(int $userId, string $currency = 'CDF'): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM savings_configs WHERE user_id = :uid AND currency = :cur'
        );
        $stmt->execute([':uid' => $userId, ':cur' => $currency]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->defaults($userId, $currency);
        }

        return [
            'cashback_enabled' => (bool) ($row['cashback_enabled'] ?? false),
            'roundup_enabled' => (bool) ($row['roundup_enabled'] ?? false),
            'roundup_to_nearest' => (int) ($row['roundup_to_nearest'] ?? 100),
            'mode' => (string) ($row['mode'] ?? self::MODE_FLEXIBLE),
            'lock_duration_days' => $row['lock_duration_days'] !== null ? (int) $row['lock_duration_days'] : null,
            'lock_started_at' => $row['lock_started_at'] ?? null,
            'is_locked' => $this->isCurrentlyLocked($row),
            'flexible_withdrawals_per_month' => (int) ($row['flexible_withdrawals_per_month'] ?? 2),
            'early_withdraw_fee_bps' => (int) ($row['early_withdraw_fee_bps'] ?? 500),
            'early_withdraw_delay_days' => (int) ($row['early_withdraw_delay_days'] ?? 7),
            'withdrawals_this_month' => $this->getWithdrawalCountThisMonth($userId, $currency),
        ];
    }

    public function update(int $userId, string $currency, array $data): array
    {
        $existing = $this->db->prepare('SELECT id FROM savings_configs WHERE user_id = :uid AND currency = :cur');
        $existing->execute([':uid' => $userId, ':cur' => $currency]);
        $row = $existing->fetch();

        $fields = [
            'cashback_enabled',
            'roundup_enabled',
            'roundup_to_nearest',
            'mode',
            'lock_duration_days',
            'flexible_withdrawals_per_month',
            'early_withdraw_fee_bps',
            'early_withdraw_delay_days',
        ];

        $setClauses = [];
        $params = [':uid' => $userId, ':cur' => $currency];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return $this->getForUser($userId, $currency);
        }

        if ($row) {
            $sql = 'UPDATE savings_configs SET ' . implode(', ', $setClauses) . ', updated_at = CURRENT_TIMESTAMP '
                . 'WHERE user_id = :uid AND currency = :cur';
        } else {
            $sql = 'INSERT INTO savings_configs (user_id, currency, ' . implode(', ', $fields) . ') '
                . 'VALUES (:uid, :cur, ' . implode(', ', array_map(static fn (string $f) => ":$f", $fields)) . ')';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if (isset($data['mode']) && $data['mode'] === self::MODE_LOCKED) {
            $lockStmt = $this->db->prepare(
                'UPDATE savings_configs SET lock_started_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND currency = :cur'
            );
            $lockStmt->execute([':uid' => $userId, ':cur' => $currency]);
        }

        return $this->getForUser($userId, $currency);
    }

    public function calculateRoundUp(int $amount, int $roundToNearest): int
    {
        if ($roundToNearest <= 0) {
            return 0;
        }
        $remainder = $amount % $roundToNearest;
        if ($remainder === 0) {
            return 0;
        }
        return $roundToNearest - $remainder;
    }

    public function getWithdrawalCountThisMonth(int $userId, string $currency): int
    {
        $startOfMonth = date('Y-m-01 00:00:00');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM transactions "
            . "WHERE user_id = :uid AND type = 'wallet_transfer' AND currency = :cur "
            . "AND created_at >= :since "
            . "AND json_extract(metadata, '$.direction') = 'to_current'"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cur' => $currency,
            ':since' => $startOfMonth,
        ]);
        return (int) $stmt->fetchColumn();
    }

    private function isCurrentlyLocked(array $row): bool
    {
        $mode = (string) ($row['mode'] ?? self::MODE_FLEXIBLE);
        if ($mode !== self::MODE_LOCKED) {
            return false;
        }

        $lockStartedAt = $row['lock_started_at'] ?? null;
        $lockDurationDays = $row['lock_duration_days'] ?? null;

        if ($lockStartedAt === null || $lockDurationDays === null) {
            return false;
        }

        $lockEnd = date('Y-m-d H:i:s', strtotime($lockStartedAt . ' +' . $lockDurationDays . ' days'));
        return date('Y-m-d H:i:s') < $lockEnd;
    }

    private function defaults(int $userId, string $currency): array
    {
        return [
            'cashback_enabled' => false,
            'roundup_enabled' => false,
            'roundup_to_nearest' => 100,
            'mode' => self::MODE_FLEXIBLE,
            'lock_duration_days' => null,
            'lock_started_at' => null,
            'is_locked' => false,
            'flexible_withdrawals_per_month' => 2,
            'early_withdraw_fee_bps' => 500,
            'early_withdraw_delay_days' => 7,
            'withdrawals_this_month' => 0,
        ];
    }
}
