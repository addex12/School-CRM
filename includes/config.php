<?php
// Add at the top of includes/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader
require_once 'db.php';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Base configuration
define(constant_name: 'BASE_URL', value: 'https://crm.flipperschool.com');
define(constant_name: 'UPLOAD_DIR', value: __DIR__ . '/../uploads');

// Ensure SMTP settings are available
$smtpHost = getSystemSetting('smtp_host', 'smtp.gmail.com');
$smtpPort = getSystemSetting('smtp_port', 587);
$smtpUsername = getSystemSetting('smtp_username', 'your-email@gmail.com');
$smtpPassword = getSystemSetting('smtp_password', 'your-email-password');
$smtpSecure = getSystemSetting('smtp_secure', 'tls');

if (!$smtpHost || !$smtpPort || !$smtpUsername || !$smtpPassword || !$smtpSecure) {
    error_log("SMTP settings are incomplete. Please check the system settings in the database.");
}
