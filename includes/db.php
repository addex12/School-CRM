<?php
class Database {
    private $host;
    private $dbname;
    private $user;
    private $pass;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->dbname = getenv('DB_NAME') ?: 'parent_survey_system';
        $this->user = getenv('DB_USER') ?: 'root';
        $this->pass = getenv('DB_PASS') ?: '@f*BaDKLjk@x4qL';
                
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Database connection error");
        }
    }

    public static function getConnection() {
        static $conn = null;
        if ($conn === null) {
            $conn = new Database();
        }
        return $conn->conn;
    }
}

// Initialize the connection globally
$pdo = Database::getConnection();

// Ensure the `users` table exists with all required columns
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        role ENUM('admin', 'teacher', 'parent', 'student') NOT NULL DEFAULT 'parent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        last_activity TIMESTAMP NULL DEFAULT NULL
    )
");

// Ensure the `audit_logs` table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");


