<?php
// Ensure no output before this point
class Database {
    private $host = 'localhost';
    private $db_name = 'school_crm';
    private $username = 'username'; // Replace with actual username
    private $password = 'password'; // Replace with actual password
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

// Initialize the connection globally
try {
    $dsn = "mysql:host=your_host;dbname=your_database;charset=utf8mb4";
    $username = "your_username";
    $password = "your_password";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    $_SESSION['error'] = "Database connection failed. Please contact the administrator.";
    header("Location: ../error.php");
    exit();
}

/// Create tables in proper order to satisfy foreign key dependencies
$tables = [
    // First create tables without foreign key dependencies
    "CREATE TABLE IF NOT EXISTS `roles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `role_name` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_name` (`role_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    // Then create users which depends on roles
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `email` varchar(100) NOT NULL,
        `role_id` int(11) NOT NULL,
        `active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`),
        CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    // Then create employees which depends on users
    "CREATE TABLE IF NOT EXISTS `employees` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `position_id` int(11) NOT NULL,
        `hire_date` date NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`),
        CONSTRAINT `fk_employee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    // Finally create attendance which depends on employees
    "CREATE TABLE IF NOT EXISTS `attendance` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `date` date NOT NULL,
        `check_in` time DEFAULT NULL,
        `check_out` time DEFAULT NULL,
        `hours_worked` decimal(5,2) DEFAULT NULL,
        `status` enum('present','absent','late','leave') NOT NULL,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

// Execute table creation in order
foreach ($tables as $table) {
    $pdo->exec($table);
}