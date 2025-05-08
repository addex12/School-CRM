--
-- Developer: Adugna Gizaw
-- Email: gizawadugna@gmail.com
-- LinkedIn: https://www.linkedin.com/in/eleganceict
-- Twitter: https://twitter.com/eleganceict1
-- GitHub: https://github.com/addex12
--
-- Adugna: Table for storing OAuth settings (Google, Facebook, Telegram)

CREATE TABLE IF NOT EXISTS oauth_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(64) NOT NULL UNIQUE,
    `value` TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
