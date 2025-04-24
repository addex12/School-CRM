-- Add missing columns/tables for dashboard cards

-- Survey Responses Table (if missing)
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    user_id INT,
    response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
);

-- Feedback Table: Ensure 'rating' column exists
ALTER TABLE feedback
    ADD COLUMN IF NOT EXISTS rating INT DEFAULT NULL;

-- Support Tickets Table: Ensure 'status' column exists
ALTER TABLE support_tickets
    ADD COLUMN IF NOT EXISTS status VARCHAR(32) DEFAULT 'open';

-- Activity Log Table: Ensure required columns exist
ALTER TABLE activity_log
    ADD COLUMN IF NOT EXISTS id INT AUTO_INCREMENT PRIMARY KEY,
    ADD COLUMN IF NOT EXISTS user_id INT,
    ADD COLUMN IF NOT EXISTS activity_type VARCHAR(64),
    ADD COLUMN IF NOT EXISTS description TEXT,
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45),
    ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP;
