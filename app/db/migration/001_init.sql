-- =====================================================
-- BASE DE DONNÉES AFRICO CASH - VERSION SQLITE
-- Compatible SQLite 3.35+
-- =====================================================

-- =====================================================
-- 1. ACTIVATION DES CONTRAINTES DE CLÉS ÉTRANGÈRES
-- =====================================================
PRAGMA foreign_keys = ON;

-- =====================================================
-- 2. TABLES (sans les types ENUM, remplacés par TEXT avec CHECK)
-- =====================================================

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    afric_number VARCHAR(20) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    address TEXT,
    profession VARCHAR(100),
    notification_phone VARCHAR(20) NOT NULL,
    country VARCHAR(2) NOT NULL DEFAULT 'CD',
    account_type VARCHAR(20) NOT NULL DEFAULT 'personal',
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    kyc_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    is_verified BOOLEAN NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    registered_by_agent_id INTEGER REFERENCES users(id),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_user_account_type CHECK (account_type IN ('personal', 'business', 'agent')),
    CONSTRAINT valid_user_role CHECK (role IN ('customer', 'agent', 'admin')),
    CONSTRAINT valid_user_kyc_status CHECK (kyc_status IN ('pending', 'level_1_verified', 'rejected')),
    CONSTRAINT valid_phone CHECK (notification_phone GLOB '[0-9+]*' AND length(notification_phone) BETWEEN 9 AND 15)
);

-- Table des agents
CREATE TABLE IF NOT EXISTS agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    agent_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INTEGER UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    commission_rate INTEGER NOT NULL DEFAULT 5000,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_agent_code CHECK (agent_code GLOB '[A-Z][A-Z][0-9][0-9][0-9][0-9][0-9]*'),
    CONSTRAINT valid_commission_rate CHECK (commission_rate BETWEEN 0 AND 10000)
);

-- Table des comptes
CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    currency VARCHAR(3) NOT NULL,
    balance INTEGER NOT NULL DEFAULT 0 CHECK (balance >= 0),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    version INTEGER NOT NULL DEFAULT 1,
    UNIQUE(user_id, currency)
);

-- Table des transactions
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    idempotency_key VARCHAR(100) UNIQUE NOT NULL,
    transaction_reference VARCHAR(50) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id),
    agent_id INTEGER REFERENCES agents(id),
    type VARCHAR(30) NOT NULL,
    amount INTEGER NOT NULL CHECK (amount > 0),
    currency VARCHAR(3) NOT NULL,
    fees INTEGER NOT NULL DEFAULT 0 CHECK (fees >= 0),
    total_amount INTEGER NOT NULL CHECK (total_amount > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    recipient_type VARCHAR(50),
    recipient_name VARCHAR(150),
    recipient_account VARCHAR(100),
    provider_name VARCHAR(50),
    exchange_rate INTEGER,
    converted_amount INTEGER,
    atm_code VARCHAR(6),
    metadata JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    CONSTRAINT valid_transaction_type CHECK (type IN (
        'deposit_agent', 'deposit_mobile_money', 'deposit_bank',
        'withdraw_agent', 'withdraw_bank', 'withdraw_atm',
        'send_africo', 'send_mobile_money', 'send_bank',
        'conversion', 'bill_payment', 'send', 'deposit', 'withdraw', 'bank', 'bill', 'atm'
    )),
    CONSTRAINT valid_transaction_status CHECK (status IN ('pending', 'succeeded', 'failed', 'completed', 'cancelled'))
);

-- Table des commissions
CREATE TABLE IF NOT EXISTS commissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
    agent_id INTEGER REFERENCES agents(id),
    agent_share INTEGER NOT NULL DEFAULT 0 CHECK (agent_share >= 0),
    operator_share INTEGER NOT NULL DEFAULT 0 CHECK (operator_share >= 0),
    myriad_share INTEGER NOT NULL DEFAULT 0 CHECK (myriad_share >= 0),
    africogroup_share INTEGER NOT NULL DEFAULT 0 CHECK (africogroup_share >= 0),
    total_commission INTEGER NOT NULL CHECK (total_commission >= 0),
    is_paid BOOLEAN NOT NULL DEFAULT 0,
    paid_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_commission_sum CHECK (
        agent_share + operator_share + myriad_share + africogroup_share = total_commission
    )
);

-- Table des sessions USSD
CREATE TABLE IF NOT EXISTS ussd_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    afric_number VARCHAR(20),
    current_menu VARCHAR(50) NOT NULL,
    context JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL DEFAULT (datetime(CURRENT_TIMESTAMP, '+5 minutes'))
);

-- Table des codes temporaires ATM
CREATE TABLE IF NOT EXISTS atm_temp_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(6) NOT NULL,
    afric_number VARCHAR(20) NOT NULL,
    amount INTEGER NOT NULL CHECK (amount > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL DEFAULT (datetime(CURRENT_TIMESTAMP, '+10 minutes')),
    used_at DATETIME,
    CONSTRAINT valid_atm_status CHECK (status IN ('active', 'used', 'expired'))
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    recipient_phone VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    sent_at DATETIME,
    retry_count INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_notification_type CHECK (type IN ('sms', 'push', 'email')),
    CONSTRAINT valid_notification_status CHECK (status IN ('pending', 'sent', 'failed'))
);

-- Table des paiements de factures
CREATE TABLE IF NOT EXISTS bill_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id),
    transaction_id INTEGER NOT NULL REFERENCES transactions(id),
    provider VARCHAR(50) NOT NULL,
    customer_number VARCHAR(100) NOT NULL,
    service_type VARCHAR(100),
    amount INTEGER NOT NULL CHECK (amount > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_bill_provider CHECK (provider IN ('canalplus', 'unigom', 'econet', 'snela'))
);

-- Table des taux de change
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate INTEGER NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'africo',
    effective_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(from_currency, to_currency, effective_date)
);

-- Table des logs d'audit
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id TEXT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table des balances journalières
CREATE TABLE IF NOT EXISTS daily_balances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id),
    account_id INTEGER NOT NULL REFERENCES accounts(id),
    currency VARCHAR(3) NOT NULL,
    balance_at_start INTEGER NOT NULL,
    balance_at_end INTEGER NOT NULL,
    date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, account_id, date)
);

-- =====================================================
-- 3. CRÉATION DES INDEX
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
CREATE INDEX IF NOT EXISTS idx_transactions_idempotency ON transactions(idempotency_key);
CREATE INDEX IF NOT EXISTS idx_transactions_created ON transactions(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_transactions_user_type ON transactions(user_id, type);
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(notification_phone);
CREATE INDEX IF NOT EXISTS idx_users_afric_number ON users(afric_number);
CREATE INDEX IF NOT EXISTS idx_agents_code ON agents(agent_code);
CREATE INDEX IF NOT EXISTS idx_atm_codes_active ON atm_temp_codes(code, status);
CREATE INDEX IF NOT EXISTS idx_ussd_sessions_expires ON ussd_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_ussd_sessions_id ON ussd_sessions(session_id, phone_number);
CREATE INDEX IF NOT EXISTS idx_notifications_status ON notifications(status);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_bill_payments_user ON bill_payments(user_id);
CREATE INDEX IF NOT EXISTS idx_bill_payments_provider ON bill_payments(provider);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_exchange_rates_date ON exchange_rates(effective_date);
CREATE INDEX IF NOT EXISTS idx_daily_balances_date ON daily_balances(date);

-- =====================================================
-- 4. TRIGGERS (équivalents des fonctions PostgreSQL)
-- =====================================================

-- Trigger pour updated_at sur users
CREATE TRIGGER IF NOT EXISTS update_users_updated_at 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger pour updated_at sur accounts
CREATE TRIGGER IF NOT EXISTS update_accounts_updated_at 
AFTER UPDATE ON accounts
BEGIN
    UPDATE accounts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger pour updated_at sur bill_payments
CREATE TRIGGER IF NOT EXISTS update_bill_payments_updated_at 
AFTER UPDATE ON bill_payments
BEGIN
    UPDATE bill_payments SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- 5. DONNÉES DE TEST
-- =====================================================

-- Utilisateur de test (mot de passe: MotDePasse123!)
INSERT OR IGNORE INTO users (
    afric_number, password_hash, full_name, address, profession, notification_phone, 
    is_verified, is_active, created_at, updated_at
) VALUES (
    '12345678',
    '$2y$10$.ft.Z9VfhvrkovA3r0xIOeFgsfTvUw3kA70pz3onZHs2M2VqTAzVG',
    'Jean Dupont',
    'Kinshasa, RDC',
    'Commerçant',
    '+243812345678',
    1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
);

-- Comptes pour l'utilisateur (150.50 USD = 15050 centimes, 350000 CDF = 35000000 centimes)
INSERT OR IGNORE INTO accounts (user_id, currency, balance) VALUES
(1, 'USD', 15050),
(1, 'CDF', 35000000);

-- Agent de test
INSERT OR IGNORE INTO users (
    afric_number, password_hash, full_name, address, profession, notification_phone, 
    is_verified, is_active, account_type, role, created_at, updated_at
) VALUES (
    'AGENT001',
    '$2y$10$.ft.Z9VfhvrkovA3r0xIOeFgsfTvUw3kA70pz3onZHs2M2VqTAzVG',
    'Paul Agent',
    'Lubumbashi, RDC',
    'Agent Africo',
    '+243998877665',
    1, 1, 'agent', 'agent', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO agents (agent_code, user_id, full_name, phone, commission_rate)
VALUES ('AG12345', 2, 'Paul Agent', '+243998877665', 5000);

-- Taux de change (1 USD = 2300.50 CDF)
INSERT OR IGNORE INTO exchange_rates (from_currency, to_currency, rate, effective_date)
VALUES
    ('USD', 'CDF', 2300500, DATE('now')),
    ('CDF', 'USD', 435, DATE('now'));

-- Code ATM de test
INSERT OR IGNORE INTO atm_temp_codes (code, afric_number, amount, status)
VALUES ('654321', '12345678', 10000, 'active');

-- =====================================================
-- 6. VUES
-- =====================================================

-- Vue : Solde utilisateur (format lisible)
CREATE VIEW IF NOT EXISTS user_balance_readable AS
SELECT 
    u.id,
    u.afric_number,
    u.full_name,
    a.currency,
    a.balance / 100.0 AS balance,
    a.balance AS balance_cents
FROM users u
JOIN accounts a ON u.id = a.user_id;

-- Vue : Historique des transactions formaté
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

-- =====================================================
-- 7. FONCTION DE GÉNÉRATION DE NUMÉRO AFRICO (en SQLite)
-- =====================================================
-- Note: SQLite n'a pas de fonction RANDOM() aussi simple que PostgreSQL
-- Cette fonction est à implémenter côté application Go

-- =====================================================
-- 8. MESSAGE DE CONFIRMATION
-- =====================================================
SELECT '==================================================' AS '';
SELECT 'BASE DE DONNÉES AFRICO CASH - SQLITE - CRÉÉE AVEC SUCCÈS !' AS '';
SELECT '==================================================' AS '';
SELECT 'Comptes de test :' AS '';
SELECT '  - Utilisateur : Numéro Africo = 12345678' AS '';
SELECT '  - Agent : Code agent = AG12345' AS '';
SELECT '  - Code ATM : 654321 (valable 10 minutes)' AS '';
SELECT '==================================================' AS '';

-- Vérification finale
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_accounts FROM accounts;
