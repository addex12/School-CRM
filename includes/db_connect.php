<!-- Author: Adugna Gizaw
Email: gizawadugna@gmail.com
Phone: +251925582067-->

<?php
include_once 'config.php';

class DBConnect {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
