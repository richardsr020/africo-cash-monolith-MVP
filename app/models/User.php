<?php

declare(strict_types=1);

final class User
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string,string> $data
     */
    public function createCustomer(array $data): int
    {
        $role = $this->hasAnyAdmin() ? 'customer' : 'admin';

        $statement = $this->db->prepare(
            'INSERT INTO users (afric_number, email, password_hash, full_name, address, profession, notification_phone, country, account_type, role, kyc_status, is_verified, is_active, created_at, updated_at) '
            . 'VALUES (:afric_number, :email, :password_hash, :full_name, :address, :profession, :notification_phone, :country, :account_type, :role, :kyc_status, :is_verified, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            ':afric_number' => $data['afric_number'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':full_name' => $data['full_name'],
            ':address' => $data['address'],
            ':profession' => $data['profession'],
            ':notification_phone' => $data['notification_phone'],
            ':country' => $data['country'],
            ':account_type' => $data['account_type'],
            ':role' => $role,
            ':kyc_status' => 'pending',
            ':is_verified' => 0,
            ':is_active' => 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createOnboardingShell(int $userId): void
    {
        $statement = $this->db->prepare(
            'INSERT OR IGNORE INTO user_onboarding (user_id, is_completed, created_at, updated_at) '
            . 'VALUES (:user_id, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([':user_id' => $userId]);
    }

    public function hasAnyAdmin(): bool
    {
        $statement = $this->db->query('SELECT 1 FROM users WHERE role = \'admin\' LIMIT 1');

        return $statement->fetchColumn() !== false;
    }

    public function generateUniqueAfricoNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $statement = $this->db->prepare('SELECT 1 FROM users WHERE afric_number = :afric_number LIMIT 1');
            $statement->execute([':afric_number' => $candidate]);
            if ($statement->fetchColumn() === false) {
                return $candidate;
            }
        }

        throw new RuntimeException('Impossible de générer un numéro Africo unique.');
    }

    public function findPublicById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.id, u.afric_number, u.email, u.full_name, u.notification_phone, u.country, u.account_type, u.role, u.is_verified, u.is_active, u.created_at, '
            . 'COALESCE(o.is_completed, 0) AS onboarding_completed, o.preferred_name, o.city, o.primary_use, o.monthly_volume, o.default_currency, o.mobile_operator '
            . 'FROM users u LEFT JOIN user_onboarding o ON o.user_id = u.id WHERE u.id = :id LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? self::normalize($user) : null;
    }

    public function findForLogin(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.id, u.afric_number, u.email, u.password_hash, u.full_name, u.notification_phone, u.country, u.account_type, u.role, u.is_verified, u.is_active, u.created_at, '
            . 'COALESCE(o.is_completed, 0) AS onboarding_completed, o.preferred_name, o.city, o.primary_use, o.monthly_volume, o.default_currency, o.mobile_operator '
            . 'FROM users u LEFT JOIN user_onboarding o ON o.user_id = u.id WHERE lower(u.email) = lower(:email) LIMIT 1'
        );
        $statement->execute([':email' => $email]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM users WHERE lower(email) = lower(:email) LIMIT 1');
        $statement->execute([':email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function completeOnboarding(int $userId, array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO user_onboarding (user_id, preferred_name, city, primary_use, monthly_volume, default_currency, mobile_operator, security_pin_hash, is_completed, completed_at, created_at, updated_at) '
            . 'VALUES (:user_id, :preferred_name, :city, :primary_use, :monthly_volume, :default_currency, :mobile_operator, :security_pin_hash, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) '
            . 'ON CONFLICT(user_id) DO UPDATE SET preferred_name = excluded.preferred_name, city = excluded.city, primary_use = excluded.primary_use, monthly_volume = excluded.monthly_volume, default_currency = excluded.default_currency, mobile_operator = excluded.mobile_operator, security_pin_hash = excluded.security_pin_hash, is_completed = 1, completed_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':preferred_name' => $data['preferred_name'],
            ':city' => $data['city'],
            ':primary_use' => $data['primary_use'],
            ':monthly_volume' => $data['monthly_volume'],
            ':default_currency' => $data['default_currency'],
            ':mobile_operator' => $data['mobile_operator'],
            ':security_pin_hash' => $data['security_pin_hash'],
        ]);

        $updateUser = $this->db->prepare(
            'UPDATE users SET full_name = :full_name, notification_phone = :phone, country = :country, account_type = :account_type, address = :address, profession = :profession, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $updateUser->execute([
            ':id' => $userId,
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':country' => $data['country'],
            ':account_type' => $data['account_type'],
            ':address' => $data['address'],
            ':profession' => $data['profession'],
        ]);
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public static function normalize(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'africo_number' => (string) $user['afric_number'],
            'email' => (string) ($user['email'] ?? ''),
            'full_name' => (string) $user['full_name'],
            'notification_phone' => (string) $user['notification_phone'],
            'country' => (string) $user['country'],
            'account_type' => (string) $user['account_type'],
            'role' => (string) $user['role'],
            'is_verified' => (bool) $user['is_verified'],
            'is_active' => (bool) $user['is_active'],
            'onboarding_completed' => (bool) ($user['onboarding_completed'] ?? false),
            'created_at' => (string) $user['created_at'],
            'onboarding' => [
                'preferred_name' => (string) ($user['preferred_name'] ?? ''),
                'city' => (string) ($user['city'] ?? ''),
                'primary_use' => (string) ($user['primary_use'] ?? ''),
                'monthly_volume' => (string) ($user['monthly_volume'] ?? ''),
                'default_currency' => (string) ($user['default_currency'] ?? 'CDF'),
                'mobile_operator' => (string) ($user['mobile_operator'] ?? ''),
            ],
        ];
    }
}
