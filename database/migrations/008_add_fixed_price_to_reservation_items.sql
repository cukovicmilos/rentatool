-- Migration: Add manual fixed price to reservation items
-- Description: NULL = automatic pricing (price_per_day * days), otherwise admin-defined total for the item

ALTER TABLE reservation_items ADD COLUMN fixed_price REAL DEFAULT NULL;
