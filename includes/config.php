<?php
//require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader
require_once 'Database.php';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Base configuration
define(constant_name: 'BASE_URL', value: 'http://localhost/survey');
define(constant_name: 'UPLOAD_DIR', value: __DIR__ . '/../uploads');
?>