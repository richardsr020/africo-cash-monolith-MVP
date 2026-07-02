-- =====================================================
-- ADMIN SETTINGS & GLOBAL FEES CONFIG
-- Migration 011
-- =====================================================

CREATE TABLE IF NOT EXISTS admin_settings (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key     VARCHAR(100) UNIQUE NOT NULL,
    setting_value   TEXT NOT NULL,
    description     VARCHAR(255),
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO admin_settings (setting_key, setting_value, description) VALUES
    ('transfer_fee_percent', '0', 'Frais de transfert P2P (pourcentage)'),
    ('mobile_money_fee_percent', '1.5', 'Frais Mobile Money (pourcentage)'),
    ('bank_transfer_fee_percent', '2.5', 'Frais virement bancaire (pourcentage)'),
    ('atm_withdraw_fee_flat', '5000', 'Frais fixe retrait DAB (centimes)'),
    ('agent_withdraw_fee_percent', '0.5', 'Frais retrait agent (pourcentage)'),
    ('early_unlock_fee_bps', '500', 'Frais déblocage anticipé épargne (bps)'),
    ('exchange_rate_markup_percent', '1', 'Marge taux de change (pourcentage)'),
    ('agent_default_commission_bps', '5000', 'Commission agent par défaut (bps)'),
    ('min_transfer_amount_cdf', '100', 'Montant minimum transfert CDF'),
    ('max_transfer_amount_cdf', '1000000', 'Montant maximum transfert CDF'),
    ('min_transfer_amount_usd', '1', 'Montant minimum transfert USD'),
    ('max_transfer_amount_usd', '10000', 'Montant maximum transfert USD'),
    ('savings_default_cashback', '0', 'Cashback épargne par défaut (activé/désactivé)'),
    ('savings_default_roundup', '0', 'Arrondi épargne par défaut (activé/désactivé)'),
    ('payment_link_max_amount', '500000', 'Montant maximum pour un lien de paiement');

CREATE TRIGGER IF NOT EXISTS update_admin_settings_updated_at
AFTER UPDATE ON admin_settings
BEGIN
    UPDATE admin_settings SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE INDEX IF NOT EXISTS idx_admin_settings_key ON admin_settings(setting_key);
