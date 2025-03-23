INSERT INTO students (first_name, last_name, email, enrollment_date) VALUES
('John', 'Doe', 'john.doe@example.com', '2023-09-01'),
('Jane', 'Smith', 'jane.smith@example.com', '2023-09-01'),
('Emily', 'Johnson', 'emily.johnson@example.com', '2023-09-01');

INSERT INTO teachers (first_name, last_name, email, hire_date) VALUES
('Michael', 'Brown', 'michael.brown@example.com', '2020-01-15'),
('Sarah', 'Davis', 'sarah.davis@example.com', '2019-03-22');

INSERT INTO courses (course_name, course_code, credits) VALUES
('Mathematics', 'MATH101', 3),
('Science', 'SCI101', 4),
('History', 'HIST101', 3);

INSERT INTO enrollments (student_id, course_id, enrollment_date) VALUES
(1, 1, '2023-09-01'),
(1, 2, '2023-09-01'),
(2, 1, '2023-09-01'),
(3, 3, '2023-09-01');