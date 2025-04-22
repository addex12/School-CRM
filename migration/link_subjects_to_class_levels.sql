-- Link Cambridge Primary (Grades 1-6) subjects to class_level_id = 1
UPDATE subjects SET class_level_id = 1 WHERE id IN (1,2,3,4,5,6,7,8);

-- Link Cambridge Lower Secondary (Grades 7-9) subjects to class_level_id = 2
UPDATE subjects SET class_level_id = 2 WHERE id IN (9,10,11,12,13,14,15);

-- Link Cambridge Upper Secondary (IGCSE - Grades 10-11) subjects to class_level_id = 3
UPDATE subjects SET class_level_id = 3 WHERE id IN (16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35);

-- Link Cambridge Advanced (AS & A Level - Grades 12-13) subjects to class_level_id = 4
UPDATE subjects SET class_level_id = 4 WHERE id IN (36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51);

-- Add missing Cambridge curriculum subjects (example: Literature, Social Studies, etc.)
INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 1, 1, 'Literature', 'Primary Literature'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Literature' AND curriculum_id = 1 AND class_level_id = 1);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 1, 2, 'Literature', 'Lower Secondary Literature'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Literature' AND curriculum_id = 1 AND class_level_id = 2);

-- Add Ethiopian curriculum mandatory subjects as additional to Cambridge
-- (Assuming curriculum_id = 2 for Ethiopian, adjust as needed)
-- Link to appropriate class_level_id (use same as Cambridge for parallel grades)

-- Example: Amharic for all levels
INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 1, 'Amharic', 'Primary Amharic'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Amharic' AND curriculum_id = 2 AND class_level_id = 1);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 2, 'Amharic', 'Lower Secondary Amharic'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Amharic' AND curriculum_id = 2 AND class_level_id = 2);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 3, 'Amharic', 'Upper Secondary Amharic'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Amharic' AND curriculum_id = 2 AND class_level_id = 3);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 4, 'Amharic', 'Advanced Amharic'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Amharic' AND curriculum_id = 2 AND class_level_id = 4);

-- Example: Civics for all levels
INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 1, 'Civics', 'Primary Civics'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Civics' AND curriculum_id = 2 AND class_level_id = 1);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 2, 'Civics', 'Lower Secondary Civics'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Civics' AND curriculum_id = 2 AND class_level_id = 2);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 3, 'Civics', 'Upper Secondary Civics'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Civics' AND curriculum_id = 2 AND class_level_id = 3);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 4, 'Civics', 'Advanced Civics'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Civics' AND curriculum_id = 2 AND class_level_id = 4);

-- Example: History for all levels (if not already present)
INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 1, 'History', 'Primary History'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'History' AND curriculum_id = 2 AND class_level_id = 1);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 2, 'History', 'Lower Secondary History'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'History' AND curriculum_id = 2 AND class_level_id = 2);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 3, 'History', 'Upper Secondary History'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'History' AND curriculum_id = 2 AND class_level_id = 3);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 4, 'History', 'Advanced History'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'History' AND curriculum_id = 2 AND class_level_id = 4);

-- Example: Geography for all levels (if not already present)
INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 1, 'Geography', 'Primary Geography'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Geography' AND curriculum_id = 2 AND class_level_id = 1);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 2, 'Geography', 'Lower Secondary Geography'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Geography' AND curriculum_id = 2 AND class_level_id = 2);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 3, 'Geography', 'Upper Secondary Geography'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Geography' AND curriculum_id = 2 AND class_level_id = 3);

INSERT INTO subjects (curriculum_id, class_level_id, subject_name, description)
SELECT 2, 4, 'Geography', 'Advanced Geography'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_name = 'Geography' AND curriculum_id = 2 AND class_level_id = 4);

-- Example: Add more Ethiopian mandatory subjects as needed (e.g., Afan Oromo, Somali, etc.)

-- Link new Ethiopian subjects to Cambridge classes as additional
-- (Assuming students in Cambridge classes also take these Ethiopian subjects for the same class_level_id)

-- You can verify the mapping with:
-- SELECT id, subject_name, curriculum_id, class_level_id FROM subjects ORDER BY id;
