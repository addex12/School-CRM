// Author: Adugna Gizaw
// Email: gizawadugna@gmail.com
// Phone: +251925582067

<?php
class Database {
    private $host = "localhost";
    private $db_name = "school_crm";
    private $username = "yourusername";
    private $password = "yourpassword";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
