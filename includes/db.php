<?php
// Ensure no output before this point
class Database {
    private $host = 'localhost';
    private $db_name = 'school_crm';
    private $username = 'root'; // Replace with actual username
    private $password = 'password123'; // Replace with actual password
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}

