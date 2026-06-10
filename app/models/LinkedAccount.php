<?php

declare(strict_types=1);

final class LinkedAccount
{
    public function __construct(private PDO $db)
    {
    }

    public function link(int $userId, string $type, string $provider, string $label, string $reference): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO linked_accounts (user_id, type, provider, account_label, account_reference, status, created_at, updated_at) '
            . 'VALUES (:user_id, :type, :provider, :label, :reference, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) '
            . 'ON CONFLICT(user_id, type, provider, account_reference) DO UPDATE SET status = :status, account_label = :label, updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':provider' => $provider,
            ':label' => $label,
            ':reference' => $reference,
            ':status' => 'active',
        ]);

        return [
            'type' => $type,
            'provider' => $provider,
            'account_label' => $label,
            'account_reference' => $reference,
            'status' => 'active',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listByUser(int $userId, ?string $type = null): array
    {
        $sql = 'SELECT type, provider, account_label, account_reference, status, created_at FROM linked_accounts WHERE user_id = :user_id';
        if ($type !== null) {
            $sql .= ' AND type = :type';
        }
        $sql .= ' ORDER BY created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($type !== null) {
            $statement->bindValue(':type', $type);
        }
        $statement->execute();

        return $statement->fetchAll();
    }
}
