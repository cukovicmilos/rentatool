-- Migration: Add EUR to RSD exchange rate setting
-- Description: Default rate used to show prices in RSD on tool pages

INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('eur_rate', '118');
