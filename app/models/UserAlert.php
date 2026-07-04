<?php

declare(strict_types=1);

final class UserAlert
{
    public function __construct(private PDO $db)
    {
    }

    public function activeForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, body, alert_type, created_at '
            . 'FROM user_alerts WHERE user_id = :uid AND is_dismissed = 0 '
            . 'ORDER BY created_at DESC LIMIT 5'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dismiss(int $alertId, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE user_alerts SET is_dismissed = 1 WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $alertId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function create(int $userId, string $title, string $body = null, string $alertType = 'info'): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_alerts (user_id, title, body, alert_type) VALUES (:uid, :title, :body, :type)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':type' => $alertType,
        ]);
        return $this->findById((int) $this->db->lastInsertId());
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, title, body, alert_type, created_at FROM user_alerts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
