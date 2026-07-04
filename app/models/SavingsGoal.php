<?php

declare(strict_types=1);

final class SavingsGoal
{
    public function __construct(private PDO $db)
    {
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, target_amount, current_amount, currency, is_completed, sort_order '
            . 'FROM savings_goals WHERE user_id = :uid ORDER BY sort_order ASC, created_at ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $userId, string $name, int $targetAmount, string $currency = 'CDF'): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO savings_goals (user_id, name, target_amount, current_amount, currency, sort_order) '
            . 'VALUES (:uid, :name, :target, 0, :currency, '
            . '(SELECT COALESCE(MAX(sort_order), 0) + 1 FROM savings_goals WHERE user_id = :uid2))'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':target' => $targetAmount,
            ':currency' => $currency,
            ':uid2' => $userId,
        ]);
        return $this->findById((int) $this->db->lastInsertId());
    }

    public function updateProgress(int $goalId, int $userId, int $currentAmount): void
    {
        $stmt = $this->db->prepare(
            'UPDATE savings_goals SET current_amount = :amount, is_completed = CASE WHEN :amount >= target_amount THEN 1 ELSE 0 END '
            . 'WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':amount' => $currentAmount, ':id' => $goalId, ':uid' => $userId]);
    }

    public function delete(int $goalId, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM savings_goals WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $goalId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, target_amount, current_amount, currency, is_completed, sort_order '
            . 'FROM savings_goals WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
