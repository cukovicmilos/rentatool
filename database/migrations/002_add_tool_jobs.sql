-- Migration: Add tool_jobs table for "Poslovi koje možete raditi sa ovim alatom"
-- Date: 2026-05-02
-- Description: Stores AI-generated jobs/tasks that can be done with each tool

CREATE TABLE IF NOT EXISTS tool_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tool_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_tool_jobs_tool_id ON tool_jobs(tool_id);
CREATE INDEX IF NOT EXISTS idx_tool_jobs_sort_order ON tool_jobs(sort_order);
