-- Insert common class levels
INSERT INTO class_levels (curriculum_id, level_name) VALUES
(1, 'Kindergarten'),
(1, 'Primary'),
(1, 'Secondary'),
(1, 'High School');

-- Insert common class names
INSERT INTO class_names (grade) VALUES
('Grade 1'),
('Grade 2'),
('Grade 3'),
('Grade 4'),
('Grade 5'),
('Grade 6'),
('Grade 7'),
('Grade 8'),
('Grade 9'),
('Grade 10'),
('Grade 11'),
('Grade 12');

-- Insert classes (assuming classes table has: id, class_level_id, class_name)
-- Adjust column names as per your schema

-- Example: Assigning sections A, B, C for each grade in Primary and Secondary
INSERT INTO classes (class_level_id, class_name) VALUES
-- Primary Grades 1-6, Sections A, B, C
(2, 'Grade 1 A'), (2, 'Grade 1 B'), (2, 'Grade 1 C'),
(2, 'Grade 2 A'), (2, 'Grade 2 B'), (2, 'Grade 2 C'),
(2, 'Grade 3 A'), (2, 'Grade 3 B'), (2, 'Grade 3 C'),
(2, 'Grade 4 A'), (2, 'Grade 4 B'), (2, 'Grade 4 C'),
(2, 'Grade 5 A'), (2, 'Grade 5 B'), (2, 'Grade 5 C'),
(2, 'Grade 6 A'), (2, 'Grade 6 B'), (2, 'Grade 6 C'),
-- Secondary Grades 7-10, Sections A, B
(3, 'Grade 7 A'), (3, 'Grade 7 B'),
(3, 'Grade 8 A'), (3, 'Grade 8 B'),
(3, 'Grade 9 A'), (3, 'Grade 9 B'),
(3, 'Grade 10 A'), (3, 'Grade 10 B');
