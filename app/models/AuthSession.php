<?php

declare(strict_types=1);

final class AuthSession
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $userId, string $ipAddress, string $userAgent): string
    {
        $token = bin2hex(random_bytes(24));
        $statement = $this->db->prepare(
            'INSERT INTO auth_sessions (user_id, token_hash, user_agent, ip_address, created_at, expires_at, last_used_at) '
            . 'VALUES (:user_id, :token_hash, :user_agent, :ip_address, CURRENT_TIMESTAMP, :expires_at, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':token_hash' => hash('sha256', $token),
            ':user_agent' => $userAgent,
            ':ip_address' => $ipAddress,
            ':expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);

        return $token;
    }

    public function findUserByToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $statement = $this->db->prepare(
            'SELECT u.id, u.afric_number, u.email, u.full_name, u.notification_phone, u.country, u.account_type, u.role, u.is_verified, u.is_active, u.created_at, '
            . 'COALESCE(o.is_completed, 0) AS onboarding_completed, o.preferred_name, o.city, o.primary_use, o.monthly_volume, o.default_currency, o.mobile_operator '
            . 'FROM auth_sessions s '
            . 'JOIN users u ON u.id = s.user_id '
            . 'LEFT JOIN user_onboarding o ON o.user_id = u.id '
            . 'WHERE s.token_hash = :token_hash AND s.revoked_at IS NULL AND s.expires_at > CURRENT_TIMESTAMP '
            . 'LIMIT 1'
        );
        $statement->execute([':token_hash' => $tokenHash]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            return null;
        }

        $touchStatement = $this->db->prepare('UPDATE auth_sessions SET last_used_at = CURRENT_TIMESTAMP WHERE token_hash = :token_hash');
        $touchStatement->execute([':token_hash' => $tokenHash]);

        return User::normalize($user);
    }

    public function revokeByUser(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE auth_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND revoked_at IS NULL');
        $statement->execute([':user_id' => $userId]);
    }
}
