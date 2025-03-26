ALTER TABLE surveys
ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0 AFTER ends_at;
