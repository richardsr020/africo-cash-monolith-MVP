-- Authentification email/password et onboarding MVP.
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email_unique ON users(email) WHERE email IS NOT NULL;

UPDATE users
SET email = 'jean.dupont@africocash.test'
WHERE afric_number = '12345678' AND email IS NULL;

UPDATE users
SET email = 'agent@africocash.test'
WHERE afric_number = 'AGENT001' AND email IS NULL;

CREATE TABLE IF NOT EXISTS user_onboarding (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    preferred_name VARCHAR(80),
    city VARCHAR(100),
    primary_use VARCHAR(40) NOT NULL DEFAULT 'personal',
    monthly_volume VARCHAR(40) NOT NULL DEFAULT 'starter',
    default_currency VARCHAR(3) NOT NULL DEFAULT 'CDF',
    mobile_operator VARCHAR(40),
    security_pin_hash VARCHAR(255),
    is_completed BOOLEAN NOT NULL DEFAULT 0,
    completed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS linked_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(30) NOT NULL,
    provider VARCHAR(80) NOT NULL,
    account_label VARCHAR(120) NOT NULL,
    account_reference VARCHAR(120) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, type, provider, account_reference),
    CONSTRAINT valid_linked_account_type CHECK (type IN ('mobile_money', 'bank')),
    CONSTRAINT valid_linked_account_status CHECK (status IN ('active', 'pending', 'disabled'))
);

CREATE TRIGGER IF NOT EXISTS update_user_onboarding_updated_at
AFTER UPDATE ON user_onboarding
BEGIN
    UPDATE user_onboarding SET updated_at = CURRENT_TIMESTAMP WHERE user_id = NEW.user_id;
END;

CREATE TRIGGER IF NOT EXISTS update_linked_accounts_updated_at
AFTER UPDATE ON linked_accounts
BEGIN
    UPDATE linked_accounts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

INSERT OR IGNORE INTO user_onboarding (
    user_id, preferred_name, city, primary_use, monthly_volume, default_currency, mobile_operator, is_completed, completed_at, created_at, updated_at
)
SELECT id, full_name, 'Kinshasa', 'personal', 'growth', 'CDF', 'Airtel Money', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM users
WHERE afric_number = '12345678';

INSERT OR IGNORE INTO user_onboarding (
    user_id, preferred_name, city, primary_use, monthly_volume, default_currency, mobile_operator, is_completed, completed_at, created_at, updated_at
)
SELECT id, full_name, 'Lubumbashi', 'agent', 'scale', 'CDF', 'Orange Money', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM users
WHERE afric_number = 'AGENT001';
