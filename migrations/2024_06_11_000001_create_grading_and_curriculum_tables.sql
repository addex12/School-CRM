-- Add grading_scales table if it does not exist
CREATE TABLE IF NOT EXISTS grading_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Drop foreign key constraint if it exists (safe for reruns)
ALTER TABLE grading_scales DROP FOREIGN KEY grading_scales_ibfk_1;

-- Add foreign key constraint
ALTER TABLE grading_scales ADD CONSTRAINT grading_scales_ibfk_1 FOREIGN KEY (curriculum_id) REFERENCES curriculums(id) ON DELETE CASCADE;

-- Make curriculum_id nullable
ALTER TABLE grading_scales MODIFY COLUMN curriculum_id INT NULL;

-- Add country column to curriculums if not exists
ALTER TABLE curriculums ADD COLUMN IF NOT EXISTS country VARCHAR(100);

-- Add curriculum_grades table if it does not exist
CREATE TABLE IF NOT EXISTS curriculum_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculum_id INT NOT NULL,
    grade_name VARCHAR(50) NOT NULL,
    grade_order INT,
    FOREIGN KEY (curriculum_id) REFERENCES curriculums(id) ON DELETE CASCADE
);

-- Fix foreign key constraint to reference the correct table name (curriculums, not curricula)
ALTER TABLE curriculum_grades DROP FOREIGN KEY curriculum_grades_ibfk_1;
ALTER TABLE curriculum_grades
  ADD CONSTRAINT curriculum_grades_ibfk_1 FOREIGN KEY (curriculum_id) REFERENCES curriculums(id) ON DELETE CASCADE;

-- Seed grading scales (general, not tied to a curriculum)
INSERT INTO grading_scales (name, description) VALUES
('A-F', 'A (Excellent), B (Good), C (Average), D (Below Average), F (Fail)'),
('Percentage', '0-100% scale'),
('GPA 4.0', 'Grade Point Average on a 4.0 scale'),
('GPA 5.0', 'Grade Point Average on a 5.0 scale'),
('IGCSE', 'International General Certificate of Secondary Education grading (A*-G)');

-- Seed curriculums (only insert if not exists to avoid duplicate entry error)
INSERT INTO curriculums (name, country)
SELECT * FROM (SELECT 'US K-12', 'USA') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'US K-12')
UNION ALL
SELECT * FROM (SELECT 'British Curriculum', 'UK') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'British Curriculum')
UNION ALL
SELECT * FROM (SELECT 'CBSE', 'India') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'CBSE')
UNION ALL
SELECT * FROM (SELECT 'IB', 'International') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'IB')
UNION ALL
SELECT * FROM (SELECT 'IGCSE', 'International') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'IGCSE');

-- Add Ethiopian Curriculum if not exists
INSERT INTO curriculums (name, country)
SELECT * FROM (SELECT 'Ethiopian Curriculum', 'Ethiopia') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM curriculums WHERE name = 'Ethiopian Curriculum');

-- Add Ethiopian Grading Scale if not exists
INSERT INTO grading_scales (name, description)
SELECT * FROM (SELECT 'Ethiopian 100-point', '0-100 scale, Pass mark 50, Distinction 85+') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM grading_scales WHERE name = 'Ethiopian 100-point');

-- Insert all subjects (global, including Ethiopian curriculum) if not exists
-- MySQL does not support INSERT ... WHERE NOT EXISTS for multiple rows directly.
-- Use individual INSERT IGNORE statements for each subject to avoid duplicates.

INSERT IGNORE INTO subjects (name) VALUES ('Mathematics');
INSERT IGNORE INTO subjects (name) VALUES ('English');
INSERT IGNORE INTO subjects (name) VALUES ('Physics');
INSERT IGNORE INTO subjects (name) VALUES ('Chemistry');
INSERT IGNORE INTO subjects (name) VALUES ('Biology');
INSERT IGNORE INTO subjects (name) VALUES ('Geography');
INSERT IGNORE INTO subjects (name) VALUES ('History');
INSERT IGNORE INTO subjects (name) VALUES ('Civics');
INSERT IGNORE INTO subjects (name) VALUES ('Economics');
INSERT IGNORE INTO subjects (name) VALUES ('ICT');
INSERT IGNORE INTO subjects (name) VALUES ('Amharic');
INSERT IGNORE INTO subjects (name) VALUES ('Afan Oromo');
INSERT IGNORE INTO subjects (name) VALUES ('Somali');
INSERT IGNORE INTO subjects (name) VALUES ('Tigrigna');
INSERT IGNORE INTO subjects (name) VALUES ('HPE');
INSERT IGNORE INTO subjects (name) VALUES ('Moral Education');
INSERT IGNORE INTO subjects (name) VALUES ('Technical Drawing');
INSERT IGNORE INTO subjects (name) VALUES ('Agriculture');
INSERT IGNORE INTO subjects (name) VALUES ('Business');
INSERT IGNORE INTO subjects (name) VALUES ('Accounting');
INSERT IGNORE INTO subjects (name) VALUES ('General Science');
INSERT IGNORE INTO subjects (name) VALUES ('Social Studies');
INSERT IGNORE INTO subjects (name) VALUES ('Music');
INSERT IGNORE INTO subjects (name) VALUES ('Art');
INSERT IGNORE INTO subjects (name) VALUES ('French');
INSERT IGNORE INTO subjects (name) VALUES ('Arabic');
INSERT IGNORE INTO subjects (name) VALUES ('Religious Education');
INSERT IGNORE INTO subjects (name) VALUES ('Environmental Science');
INSERT IGNORE INTO subjects (name) VALUES ('Science');
INSERT IGNORE INTO subjects (name) VALUES ('Reading');
INSERT IGNORE INTO subjects (name) VALUES ('Writing');
INSERT IGNORE INTO subjects (name) VALUES ('Handwriting');
INSERT IGNORE INTO subjects (name) VALUES ('Drama');
INSERT IGNORE INTO subjects (name) VALUES ('Physical Education');
INSERT IGNORE INTO subjects (name) VALUES ('Home Economics');
INSERT IGNORE INTO subjects (name) VALUES ('Technology');
INSERT IGNORE INTO subjects (name) VALUES ('Ethics');

-- Seed grades/classes for US K-12
INSERT INTO curriculum_grades (curriculum_id, grade_name, grade_order) VALUES
(1, 'Kindergarten', 0),
(1, 'Grade 1', 1),
(1, 'Grade 2', 2),
(1, 'Grade 3', 3),
(1, 'Grade 4', 4),
(1, 'Grade 5', 5),
(1, 'Grade 6', 6),
(1, 'Grade 7', 7),
(1, 'Grade 8', 8),
(1, 'Grade 9 (Freshman)', 9),
(1, 'Grade 10 (Sophomore)', 10),
(1, 'Grade 11 (Junior)', 11),
(1, 'Grade 12 (Senior)', 12);

-- Seed grades/classes for British Curriculum
INSERT INTO curriculum_grades (curriculum_id, grade_name, grade_order) VALUES
(2, 'Reception', 0),
(2, 'Year 1', 1),
(2, 'Year 2', 2),
(2, 'Year 3', 3),
(2, 'Year 4', 4),
(2, 'Year 5', 5),
(2, 'Year 6', 6),
(2, 'Year 7', 7),
(2, 'Year 8', 8),
(2, 'Year 9', 9),
(2, 'Year 10', 10),
(2, 'Year 11', 11),
(2, 'Year 12', 12),
(2, 'Year 13', 13);

-- Seed grades/classes for CBSE
INSERT INTO curriculum_grades (curriculum_id, grade_name, grade_order) VALUES
(3, 'Class 1', 1),
(3, 'Class 2', 2),
(3, 'Class 3', 3),
(3, 'Class 4', 4),
(3, 'Class 5', 5),
(3, 'Class 6', 6),
(3, 'Class 7', 7),
(3, 'Class 8', 8),
(3, 'Class 9', 9),
(3, 'Class 10', 10),
(3, 'Class 11', 11),
(3, 'Class 12', 12);

-- Seed grades/classes for IB
INSERT INTO curriculum_grades (curriculum_id, grade_name, grade_order) VALUES
(4, 'PYP 1', 1),
(4, 'PYP 2', 2),
(4, 'PYP 3', 3),
(4, 'PYP 4', 4),
(4, 'PYP 5', 5),
(4, 'MYP 1', 6),
(4, 'MYP 2', 7),
(4, 'MYP 3', 8),
(4, 'MYP 4', 9),
(4, 'MYP 5', 10),
(4, 'DP 1', 11),
(4, 'DP 2', 12);

-- Seed grades/classes for IGCSE
INSERT INTO curriculum_grades (curriculum_id, grade_name, grade_order) VALUES
(5, 'Year 10', 10),
(5, 'Year 11', 11);

