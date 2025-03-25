CREATE DATABASE parent_survey_system;
USE parent_survey_system;
;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'parent') NOT NULL DEFAULT 'parent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'text', 'rating') NOT NULL,
    options TEXT,
    is_required BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

CREATE TABLE responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_id INT NOT NULL,
    user_id INT NOT NULL,
    response TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id),
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(255) NOT NULL
);

INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@school.edu', 'admin123', 'admin');

INSERT INTO users (username, password, role, email, created_at, last_login)
('smtp_secure', 'tls', 'email');('smtp_password', 'your-email-password', 'email'),('smtp_username', 'your-email@gmail.com', 'email'),('smtp_port', '587', 'email'),('smtp_host', 'smtp.gmail.com', 'email'),INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES);    NULL    NOW(),     'admin@example.com',     'admin',     '$2y$10$eImiTXuWVxfM37uY4JANjQ==', -- Replace this with the hashed password    'admin_username', VALUES (
SELECT * FROM system_settings WHERE setting_group = 'email';