-- Insert common class levels
INSERT INTO class_levels (name) VALUES
('Kindergarten'),
('Primary'),
('Secondary'),
('High School');

-- Insert common class names
INSERT INTO class_names (name) VALUES
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

-- Insert classes (assuming classes table has: id, class_level_id, class_name_id, section)
-- Adjust column names as per your schema

-- Example: Assigning sections A, B, C for each grade in Primary and Secondary
INSERT INTO classes (class_level_id, class_name_id, section) VALUES
-- Primary Grades 1-6, Sections A, B, C
(2, 1, 'A'), (2, 1, 'B'), (2, 1, 'C'),
(2, 2, 'A'), (2, 2, 'B'), (2, 2, 'C'),
(2, 3, 'A'), (2, 3, 'B'), (2, 3, 'C'),
(2, 4, 'A'), (2, 4, 'B'), (2, 4, 'C'),
(2, 5, 'A'), (2, 5, 'B'), (2, 5, 'C'),
(2, 6, 'A'), (2, 6, 'B'), (2, 6, 'C'),
-- Secondary Grades 7-10, Sections A, B
(3, 7, 'A'), (3, 7, 'B'),
(3, 8, 'A'), (3, 8, 'B'),
(3, 9, 'A'), (3, 9, 'B'),
(3, 10, 'A'), (3, 10, 'B');
