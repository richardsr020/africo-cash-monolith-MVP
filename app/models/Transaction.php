<?php

class Transaction {
    private $db;
    
    const TYPE_SEND = 'send';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_BILL = 'bill';
    
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO transactions (
                idempotency_key, transaction_reference, user_id, type,
                amount, currency, fees, total_amount, status,
                recipient_name, recipient_account, provider_name, metadata
            ) VALUES (
                :idempotency_key, :transaction_reference, :user_id, :type,
                :amount, :currency, :fees, :total_amount, :status,
                :recipient_name, :recipient_account, :provider_name, :metadata
            )
        ");
        
        $stmt->execute([
            ':idempotency_key' => $data['idempotency_key'],
            ':transaction_reference' => $data['transaction_reference'],
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':amount' => $data['amount'],
            ':currency' => $data['currency'],
            ':fees' => $data['fees'] ?? 0,
            ':total_amount' => $data['total_amount'],
            ':status' => $data['status'] ?? self::STATUS_PENDING,
            ':recipient_name' => $data['recipient_name'] ?? null,
            ':recipient_account' => $data['recipient_account'] ?? null,
            ':provider_name' => $data['provider_name'] ?? null,
            ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateStatus(string $reference, string $status): bool {
        $stmt = $this->db->prepare("
            UPDATE transactions 
            SET status = :status, 
                completed_at = CURRENT_TIMESTAMP
            WHERE transaction_reference = :reference
        ");
        
        return $stmt->execute([
            ':reference' => $reference,
            ':status' => $status
        ]);
    }
    
    public function getByReference(string $reference): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name as user_name, u.afric_number
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.transaction_reference = :reference
        ");
        $stmt->execute([':reference' => $reference]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['metadata']) {
            $result['metadata'] = json_decode($result['metadata'], true);
        }
        
        return $result ?: null;
    }
    
    public function getUserTransactions(int $userId, int $limit = 50, int $offset = 0, array $filters = []): array {
        $sql = "SELECT * FROM transactions WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function generateReference(): string {
        return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    public function generateIdempotencyKey(): string {
        return 'IDP-' . uniqid() . '-' . bin2hex(random_bytes(8));
    }
    
    public function checkBalance(int $userId, string $currency = 'CDF'): int {
        $stmt = $this->db->prepare("
            SELECT balance FROM accounts 
            WHERE user_id = :user_id AND currency = :currency
        ");
        $stmt->execute([':user_id' => $userId, ':currency' => $currency]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['balance'] : 0;
    }
    
    public function updateBalance(int $userId, int $amount, string $operation = 'debit', string $currency = 'CDF'): bool {
        $sql = $operation === 'credit' 
            ? "UPDATE accounts SET balance = balance + :amount, version = version + 1 WHERE user_id = :user_id AND currency = :currency"
            : "UPDATE accounts SET balance = balance - :amount, version = version + 1 WHERE user_id = :user_id AND currency = :currency AND balance >= :amount";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':amount' => $amount,
            ':currency' => $currency
        ]);
    }
    
    public function findUserByAfricoNumber(string $number): ?array {
        $stmt = $this->db->prepare("
            SELECT id, afric_number, full_name, email 
            FROM users 
            WHERE afric_number = :number AND is_active = 1
        ");
        $stmt->execute([':number' => $number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}