<!--
 * Developer: Adugna Gizaw
 * Email: <a href="mailto:Gizawadugna@gmail.com">Gizawadugna@gmail.com</a>
 * Phone: +251925582067
 * LinkedIn: <a href="https://linkedin.com/in/eleganceict" target="_blank">linkedin.com/in/eleganceict</a>
 * Twitter: <a href="https://twitter.com/eleganceict1" target="_blank">@eleganceict1</a>
 * GitHub: <a href="https://github.com/addex12" target="_blank">github.com/addex12</a>
 *
 * File: db.php
 * Description: Handles database connection and initialization.
-->

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

    public function getConnection(): PDO {
        return $this->conn;
    }
}
?>