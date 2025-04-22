-- Add missing columns to the teachers table if they do not exist

ALTER TABLE teachers
	ADD COLUMN user_id INT(11) NOT NULL,
	ADD COLUMN class_id INT(11) NULL,
	ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	ADD COLUMN name VARCHAR(255) NULL,
	ADD COLUMN subject_id INT(11) NULL,
	ADD COLUMN class_name_id INT(11) NULL,
	ADD COLUMN section_id INT(11) NULL,
	ADD COLUMN email VARCHAR(255) NULL,
	ADD COLUMN username VARCHAR(255) NULL;

ALTER TABLE sections
	ADD COLUMN section VARCHAR(50) NULL;
