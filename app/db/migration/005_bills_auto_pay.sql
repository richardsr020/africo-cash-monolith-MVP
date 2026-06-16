CREATE TABLE IF NOT EXISTS auto_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    service_type VARCHAR(100) NOT NULL,
    customer_reference VARCHAR(100) NOT NULL,
    amount INTEGER,
    currency VARCHAR(3) NOT NULL DEFAULT 'CDF',
    frequency VARCHAR(20) NOT NULL DEFAULT 'monthly',
    day_of_month INTEGER NOT NULL DEFAULT 1,
    max_amount INTEGER,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    last_paid_at DATETIME,
    next_pay_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_frequency CHECK (frequency IN ('weekly', 'monthly', 'quarterly', 'yearly')),
    CONSTRAINT valid_day CHECK (day_of_month BETWEEN 1 AND 28)
);

CREATE TRIGGER IF NOT EXISTS update_auto_payments_updated_at
AFTER UPDATE ON auto_payments
BEGIN
    UPDATE auto_payments SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
