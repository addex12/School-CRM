-- Add academic_year and term columns to settings table if not exists
ALTER TABLE settings
    ADD COLUMN academic_year VARCHAR(50) NULL AFTER value,
    ADD COLUMN term VARCHAR(50) NULL AFTER academic_year;
