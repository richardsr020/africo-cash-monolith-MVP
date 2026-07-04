CREATE TABLE IF NOT EXISTS savings_goals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    target_amount INTEGER NOT NULL CHECK (target_amount > 0),
    current_amount INTEGER NOT NULL DEFAULT 0 CHECK (current_amount >= 0),
    currency VARCHAR(3) NOT NULL DEFAULT 'CDF',
    is_completed BOOLEAN NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, name)
);

INSERT OR IGNORE INTO savings_goals (user_id, name, target_amount, current_amount, currency, sort_order)
SELECT id, 'Fonds d''urgence', 50000000, 0, 'CDF', 1 FROM users WHERE is_active = 1;

INSERT OR IGNORE INTO savings_goals (user_id, name, target_amount, current_amount, currency, sort_order)
SELECT id, 'Voyage annuel', 20000000, 0, 'CDF', 2 FROM users WHERE is_active = 1;

INSERT OR IGNORE INTO savings_goals (user_id, name, target_amount, current_amount, currency, sort_order)
SELECT id, 'Achat véhicule', 100000000, 0, 'CDF', 3 FROM users WHERE is_active = 1;

CREATE TRIGGER IF NOT EXISTS update_savings_goals_updated_at
AFTER UPDATE ON savings_goals
BEGIN
    UPDATE savings_goals SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TABLE IF NOT EXISTS user_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    body TEXT,
    alert_type VARCHAR(30) NOT NULL DEFAULT 'info',
    is_dismissed BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_alert_type CHECK (alert_type IN ('info', 'warning', 'success', 'danger'))
);

CREATE INDEX IF NOT EXISTS idx_savings_goals_user ON savings_goals(user_id);
CREATE INDEX IF NOT EXISTS idx_user_alerts_user ON user_alerts(user_id);
CREATE INDEX IF NOT EXISTS idx_user_alerts_dismissed ON user_alerts(user_id, is_dismissed);
