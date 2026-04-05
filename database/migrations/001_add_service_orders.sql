-- Migration: Add service order support to reservation_items
-- Date: 2026-04-05
-- Description: Allow reservation_items to store service orders (not just tool rentals)

-- Recreate reservation_items with nullable tool_id and new service columns
CREATE TABLE reservation_items_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reservation_id INTEGER NOT NULL,
    tool_id INTEGER DEFAULT NULL,
    tool_name TEXT NOT NULL,
    price_per_day REAL NOT NULL,
    days INTEGER NOT NULL,
    subtotal REAL NOT NULL,
    item_type TEXT DEFAULT 'tool' CHECK(item_type IN ('tool', 'service')),
    service_description TEXT,
    service_date DATE,
    service_location TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE RESTRICT
);

-- Copy existing data
INSERT INTO reservation_items_new (id, reservation_id, tool_id, tool_name, price_per_day, days, subtotal)
SELECT id, reservation_id, tool_id, tool_name, price_per_day, days, subtotal FROM reservation_items;

-- Drop old table and rename new one
DROP TABLE reservation_items;
ALTER TABLE reservation_items_new RENAME TO reservation_items;

-- Recreate indexes
CREATE INDEX IF NOT EXISTS idx_reservation_items_reservation ON reservation_items(reservation_id);
CREATE INDEX IF NOT EXISTS idx_reservation_items_tool ON reservation_items(tool_id);
CREATE INDEX IF NOT EXISTS idx_reservation_items_type ON reservation_items(item_type);
