-- =====================================================
-- MIGRATION TRUST BADGES & USER RATINGS
-- =====================================================

CREATE TABLE IF NOT EXISTS user_trust_scores (
    user_id             INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    badge               VARCHAR(20) NOT NULL DEFAULT 'none',
    trust_score         INTEGER NOT NULL DEFAULT 0,
    volume_6m_cdf       INTEGER NOT NULL DEFAULT 0,
    volume_6m_usd       INTEGER NOT NULL DEFAULT 0,
    tx_count_6m         INTEGER NOT NULL DEFAULT 0,
    rating_avg          DECIMAL(3,2) NOT NULL DEFAULT 0,
    rating_count        INTEGER NOT NULL DEFAULT 0,
    badge_awarded_at    DATETIME,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_badge CHECK (badge IN ('none', 'silver', 'gold'))
);

CREATE TRIGGER IF NOT EXISTS update_user_trust_scores_updated_at
AFTER UPDATE ON user_trust_scores
BEGIN
    UPDATE user_trust_scores SET updated_at = CURRENT_TIMESTAMP WHERE user_id = NEW.user_id;
END;

CREATE TABLE IF NOT EXISTS user_ratings (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    rater_id              INTEGER NOT NULL REFERENCES users(id),
    rated_user_id         INTEGER NOT NULL REFERENCES users(id),
    transaction_reference VARCHAR(50) NOT NULL REFERENCES transactions(transaction_reference),
    rating                INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment               TEXT,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(rater_id, transaction_reference)
);

CREATE INDEX IF NOT EXISTS idx_user_ratings_rated ON user_ratings(rated_user_id);
CREATE INDEX IF NOT EXISTS idx_user_ratings_rater ON user_ratings(rater_id);
