<?php
class Database {
    private $host;
    private $dbname;
    private $user;
    private $pass;
    private $conn;

    public function __construct() {
        $this->host = getenv(name: 'DB_HOST') ?: 'localhost';
        $this->dbname = getenv(name: 'DB_NAME') ?: 'parent_survey_system';
        $this->user = getenv(name: 'DB_USER') ?: 'root';
        $this->pass = getenv(name: 'DB_PASS') ?: '';
        
        try {
            $this->conn = new PDO(
                dsn: "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                username: $this->user,
                password: $this->pass,
                options: [
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
?>
}
?>