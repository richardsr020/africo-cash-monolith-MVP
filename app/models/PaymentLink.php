<?php

declare(strict_types=1);

final class PaymentLink
{
    private const CODE_PREFIX = 'ACP-';

    public function __construct(private PDO $db)
    {
    }

    public function create(int $userId, string $type, ?int $amount, ?int $maxAmount, string $currency, string $pin, string $expiresAt): array
    {
        $pinHash = password_hash($pin, PASSWORD_BCRYPT);
        $code = $this->generateCode();

        $stmt = $this->db->prepare(
            'INSERT INTO payment_links (user_id, code, type, amount, max_amount, currency, pin_hash, expires_at) '
            . 'VALUES (:uid, :code, :type, :amount, :max_amount, :currency, :pin_hash, :expires_at)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':code' => $code,
            ':type' => $type,
            ':amount' => $amount,
            ':max_amount' => $maxAmount,
            ':currency' => $currency,
            ':pin_hash' => $pinHash,
            ':expires_at' => $expiresAt,
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->normalize([
            'id' => $id,
            'user_id' => $userId,
            'code' => $code,
            'type' => $type,
            'amount' => $amount,
            'max_amount' => $maxAmount,
            'currency' => $currency,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'used_at' => null,
            'used_by_user_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_links WHERE code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->normalize($row) : null;
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM payment_links WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50'
        );
        $stmt->execute([':uid' => $userId]);

        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    public function validate(int $linkId, string $pin): ?string
    {
        $stmt = $this->db->prepare('SELECT status, pin_hash, expires_at FROM payment_links WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $linkId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return 'Lien introuvable.';
        }

        if ($row['status'] !== 'active') {
            return "Ce lien n'est plus actif (statut : {$row['status']}).";
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            $this->markExpired($linkId);
            return 'Ce lien a expiré.';
        }

        if (!password_verify($pin, (string) $row['pin_hash'])) {
            return 'PIN incorrect.';
        }

        return null;
    }

    public function use(int $linkId, int $redeemerId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payment_links SET status = \'used\', used_at = CURRENT_TIMESTAMP, used_by_user_id = :rid WHERE id = :id'
        );
        $stmt->execute([':rid' => $redeemerId, ':id' => $linkId]);
    }

    public function revoke(int $linkId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payment_links SET status = \'revoked\' WHERE id = :id AND status = \'active\''
        );
        $stmt->execute([':id' => $linkId]);
    }

    public function markExpired(int $linkId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payment_links SET status = \'expired\' WHERE id = :id AND status = \'active\''
        );
        $stmt->execute([':id' => $linkId]);
    }

    public function expireOld(): int
    {
        $stmt = $this->db->prepare(
            'UPDATE payment_links SET status = \'expired\' WHERE status = \'active\' AND expires_at < datetime(\'now\')'
        );
        $stmt->execute();

        return $stmt->rowCount();
    }

    private function generateCode(): string
    {
        do {
            $bytes = random_bytes(6);
            $random = bin2hex($bytes);
            $code = self::CODE_PREFIX . strtoupper($random);
            $stmt = $this->db->prepare('SELECT 1 FROM payment_links WHERE code = :code LIMIT 1');
            $stmt->execute([':code' => $code]);
        } while ($stmt->fetchColumn() !== false);

        return $code;
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'code' => (string) $row['code'],
            'type' => (string) $row['type'],
            'amount' => $row['amount'] !== null ? (int) $row['amount'] : null,
            'max_amount' => $row['max_amount'] !== null ? (int) $row['max_amount'] : null,
            'currency' => (string) $row['currency'],
            'status' => (string) $row['status'],
            'expires_at' => (string) $row['expires_at'],
            'used_at' => $row['used_at'] !== null ? (string) $row['used_at'] : null,
            'used_by_user_id' => $row['used_by_user_id'] !== null ? (int) $row['used_by_user_id'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }
}
