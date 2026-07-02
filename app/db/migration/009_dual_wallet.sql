-- =====================================================
-- DUAL WALLET: courant + épargne
-- Migration 009
-- =====================================================

PRAGMA foreign_keys = OFF;

DROP VIEW IF EXISTS user_balance_readable;
DROP VIEW IF EXISTS transaction_history_readable;

-- =====================================================
-- 1. RECREATE accounts TABLE WITH wallet_type
-- =====================================================
DROP INDEX IF EXISTS idx_transactions_user;
DROP INDEX IF EXISTS idx_transactions_status;
DROP INDEX IF EXISTS idx_transactions_idempotency;
DROP INDEX IF EXISTS idx_transactions_created;
DROP INDEX IF EXISTS idx_transactions_user_type;

DROP TABLE IF EXISTS accounts_new;
CREATE TABLE accounts_new (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    currency        VARCHAR(3) NOT NULL,
    wallet_type     VARCHAR(20) NOT NULL DEFAULT 'current',
    balance         INTEGER NOT NULL DEFAULT 0 CHECK (balance >= 0),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    version         INTEGER NOT NULL DEFAULT 1,
    UNIQUE(user_id, currency, wallet_type)
);

INSERT INTO accounts_new (id, user_id, currency, wallet_type, balance, created_at, updated_at, version)
SELECT id, user_id, currency, 'current', balance, created_at, updated_at, version FROM accounts;

DROP TABLE accounts;
ALTER TABLE accounts_new RENAME TO accounts;

CREATE TRIGGER IF NOT EXISTS update_accounts_updated_at
AFTER UPDATE ON accounts
BEGIN
    UPDATE accounts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- 2. ADD wallet_transfer TYPE TO transactions
-- =====================================================
DROP TABLE IF EXISTS transactions_new;
CREATE TABLE transactions_new (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    idempotency_key       VARCHAR(100) UNIQUE NOT NULL,
    transaction_reference VARCHAR(50) UNIQUE NOT NULL,
    user_id               INTEGER NOT NULL REFERENCES users(id),
    agent_id              INTEGER REFERENCES agents(id),
    type                  VARCHAR(30) NOT NULL,
    amount                INTEGER NOT NULL CHECK (amount > 0),
    currency              VARCHAR(3) NOT NULL,
    fees                  INTEGER NOT NULL DEFAULT 0 CHECK (fees >= 0),
    total_amount          INTEGER NOT NULL CHECK (total_amount > 0),
    status                VARCHAR(20) NOT NULL DEFAULT 'pending',
    recipient_type        VARCHAR(50),
    recipient_name        VARCHAR(150),
    recipient_account     VARCHAR(100),
    provider_name         VARCHAR(50),
    exchange_rate         INTEGER,
    converted_amount      INTEGER,
    atm_code              VARCHAR(6),
    metadata              JSON,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at          DATETIME,
    CONSTRAINT valid_transaction_type CHECK (type IN (
        'deposit_agent', 'deposit_mobile_money', 'deposit_bank',
        'withdraw_agent', 'withdraw_bank', 'withdraw_atm',
        'send_africo', 'send_mobile_money', 'send_bank',
        'conversion', 'bill_payment', 'send', 'deposit', 'withdraw', 'bank', 'bill', 'atm',
        'wallet_transfer', 'early_unlock'
    )),
    CONSTRAINT valid_transaction_status CHECK (status IN ('pending', 'succeeded', 'failed', 'completed', 'cancelled'))
);

INSERT INTO transactions_new (
    id, idempotency_key, transaction_reference, user_id, agent_id,
    type, amount, currency, fees, total_amount, status,
    recipient_type, recipient_name, recipient_account, provider_name,
    exchange_rate, converted_amount, atm_code, metadata,
    created_at, completed_at
)
SELECT
    id, idempotency_key, transaction_reference, user_id, agent_id,
    type, amount, currency, fees, total_amount, status,
    recipient_type, recipient_name, recipient_account, provider_name,
    exchange_rate, converted_amount, atm_code, metadata,
    created_at, completed_at
FROM transactions;

DROP TABLE transactions;
ALTER TABLE transactions_new RENAME TO transactions;

CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
CREATE INDEX IF NOT EXISTS idx_transactions_idempotency ON transactions(idempotency_key);
CREATE INDEX IF NOT EXISTS idx_transactions_created ON transactions(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_transactions_user_type ON transactions(user_id, type);

-- =====================================================
-- 3. savings_configs TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS savings_configs (
    id                              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id                         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    currency                        VARCHAR(3) NOT NULL DEFAULT 'CDF',
    cashback_enabled                BOOLEAN NOT NULL DEFAULT 0,
    roundup_enabled                 BOOLEAN NOT NULL DEFAULT 0,
    roundup_to_nearest              INTEGER NOT NULL DEFAULT 100,
    mode                            VARCHAR(20) NOT NULL DEFAULT 'flexible',
    lock_duration_days              INTEGER DEFAULT NULL,
    lock_started_at                 DATETIME DEFAULT NULL,
    flexible_withdrawals_per_month  INTEGER NOT NULL DEFAULT 2,
    early_withdraw_fee_bps          INTEGER NOT NULL DEFAULT 500,
    early_withdraw_delay_days       INTEGER NOT NULL DEFAULT 7,
    created_at                      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, currency)
);

CREATE TRIGGER IF NOT EXISTS update_savings_configs_updated_at
AFTER UPDATE ON savings_configs
BEGIN
    UPDATE savings_configs SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- 4. SEED DATA: savings accounts + configs for existing users
-- =====================================================
INSERT OR IGNORE INTO accounts (user_id, currency, wallet_type, balance)
SELECT id, 'CDF', 'savings', 0 FROM users WHERE is_active = 1;

INSERT OR IGNORE INTO accounts (user_id, currency, wallet_type, balance)
SELECT id, 'USD', 'savings', 0 FROM users WHERE is_active = 1;

INSERT OR IGNORE INTO savings_configs (user_id, currency)
SELECT id, 'CDF' FROM users WHERE is_active = 1;

INSERT OR IGNORE INTO savings_configs (user_id, currency)
SELECT id, 'USD' FROM users WHERE is_active = 1;

-- =====================================================
-- 5. RECREATE VIEWS
-- =====================================================
CREATE VIEW IF NOT EXISTS user_balance_readable AS
SELECT
    u.id,
    u.afric_number,
    u.full_name,
    a.currency,
    a.wallet_type,
    a.balance / 100.0 AS balance,
    a.balance AS balance_cents
FROM users u
JOIN accounts a ON u.id = a.user_id;

CREATE VIEW IF NOT EXISTS transaction_history_readable AS
SELECT
    t.transaction_reference,
    t.type,
    t.amount / 100.0 AS amount,
    t.currency,
    t.fees / 100.0 AS fees,
    t.status,
    t.created_at,
    COALESCE(c.agent_share / 100.0, 0) AS commission_agent
FROM transactions t
LEFT JOIN commissions c ON t.id = c.transaction_id
ORDER BY t.created_at DESC;

PRAGMA foreign_keys = ON;
