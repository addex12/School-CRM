ALTER TABLE teachers
    ADD COLUMN address VARCHAR(255) NULL,
    ADD COLUMN date_of_birth DATE NULL,
    ADD COLUMN gender VARCHAR(20) NULL,
    ADD COLUMN qualification VARCHAR(255) NULL,
    ADD COLUMN subject_specialization VARCHAR(255) NULL,
    ADD COLUMN status VARCHAR(50) NULL,
    ADD COLUMN class_id INT NULL;
