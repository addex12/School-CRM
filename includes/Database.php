<?php
class Database {
    private $host = '127.0.0.1'; // Matches your XAMPP server
    private $dbname = 'parent_survey_system'; // Ensure this matches your database name
    private $user = 'root'; // Default XAMPP username
    private $pass = ''; // Default XAMPP password (empty by default)
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error");
        }
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }
}
?>
