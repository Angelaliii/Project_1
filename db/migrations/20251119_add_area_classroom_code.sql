-- Migration: 2025-11-19
-- Add area and classroom_code to classrooms, remove building/room
-- Run this on your MySQL (XAMPP) instance after backup

ALTER TABLE classrooms
    DROP COLUMN IF EXISTS building,
    DROP COLUMN IF EXISTS room,
    ADD COLUMN IF NOT EXISTS area VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS classroom_code VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS classroom_photo VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS classroom_notes TEXT NULL,
    ADD COLUMN IF NOT EXISTS features TEXT NULL,
    ADD COLUMN IF NOT EXISTS capacity INT NULL,
    ADD COLUMN IF NOT EXISTS available_times TEXT NULL,
    ADD COLUMN IF NOT EXISTS recording_system BOOLEAN NOT NULL DEFAULT FALSE;

-- Optional: insert seed classrooms if table is empty (uncomment to use)
-- INSERT INTO classrooms (classroom_name, area, classroom_code, capacity, recording_system, features)
-- VALUES
-- ('語言教室 FG202', '德芳外語大樓', 'FG202', 70, TRUE, '教室'),
-- ('語言教室 FG204', '德芳外語大樓', 'FG204', 56, TRUE, '教室');

-- Adjust classroom_permissions if needed (this migration assumes table exists)
-- ALTER TABLE classroom_permissions
--     DROP COLUMN IF EXISTS created_at,
--     DROP COLUMN IF EXISTS updated_at;

-- Note:
-- 1) Please backup your DB before running this migration.
-- 2) If your application depends on building/room columns elsewhere, update codebase first or run in maintenance window.
