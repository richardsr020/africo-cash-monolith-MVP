-- =====================================================
-- LIENS DE PAIEMENT
-- Migration 010
-- =====================================================

-- Codes temporaires universels : envoi P2P, retrait agent, paiement marchand
CREATE TABLE IF NOT EXISTS payment_links (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code            VARCHAR(20) UNIQUE NOT NULL,
    type            VARCHAR(20) NOT NULL,
    amount          INTEGER,
    max_amount      INTEGER,
    currency        VARCHAR(3) NOT NULL,
    pin_hash        VARCHAR(255) NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'active',
    expires_at      DATETIME NOT NULL,
    used_at         DATETIME,
    used_by_user_id INTEGER REFERENCES users(id),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_link_type CHECK (type IN ('send', 'withdraw', 'merchant')),
    CONSTRAINT valid_link_status CHECK (status IN ('active', 'used', 'expired', 'revoked'))
);

CREATE INDEX IF NOT EXISTS idx_payment_links_user ON payment_links(user_id, status);
CREATE INDEX IF NOT EXISTS idx_payment_links_code ON payment_links(code);
