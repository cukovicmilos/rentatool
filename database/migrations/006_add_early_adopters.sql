-- Migration: Add early adopters table for "rent your tool" interest list
-- Date: 2026-09-04
-- Description: Stores emails of people interested in listing their tools for rent

CREATE TABLE IF NOT EXISTS early_adopters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_early_adopters_created_at ON early_adopters(created_at);
CREATE INDEX IF NOT EXISTS idx_early_adopters_email ON early_adopters(email);
