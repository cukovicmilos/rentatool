-- Migration: Add time_start and time_end to reservations
-- Description: Allow specifying pickup/return time alongside dates

ALTER TABLE reservations ADD COLUMN time_start TEXT DEFAULT '08:00';
ALTER TABLE reservations ADD COLUMN time_end TEXT DEFAULT '18:00';
