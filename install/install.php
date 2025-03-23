<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4.0 or higher is required.');
}

// Check required extensions
$required_extensions = ['mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Extension $ext is required.");
    }
}

// Database connection
$host = 'localhost';
$user = 'flipperschool_crm';
$pass = 'A25582067s_';
$dbname = 'flipperschool_school_crm';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . ' (' . $conn->connect_errno . ')');
}

// Create database
if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
    die('Database creation failed: ' . $conn->error);
}

$conn->select_db($dbname);

// Create tables
$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'teacher', 'parent', 'admin') NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS surveys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        user_id INT NOT NULL,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (survey_id) REFERENCES surveys(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )",
    "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) NOT NULL,
        setting_value TEXT
    )"
];

foreach ($queries as $query) {
    if (!$conn->query($query)) {
        die('Table creation failed: ' . $conn->error);
    }
}

// Create admin user
$admin_username = 'admin';
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);
if (!$conn->query("INSERT INTO users (username, password, role) VALUES ('$admin_username', '$admin_password', 'admin')")) {
    die('Admin user creation failed: ' . $conn->error);
}

echo 'Installation completed successfully.';
?>
