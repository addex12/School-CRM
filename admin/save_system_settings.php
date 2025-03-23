<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$user = 'flipperschool_crm';
$pass = 'A25582067s_';
$dbname = 'flipperschool_school_crm';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . ' (' . $conn->connect_errno . ')');
}

$school_name = $_POST['school_name'] ?? '';
$academic_year = $_POST['academic_year'] ?? '';

$sql = "INSERT INTO system_settings (key_name, key_value) VALUES 
        ('school_name', ?),
        ('academic_year', ?)
        ON DUPLICATE KEY UPDATE key_value=VALUES(key_value)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $school_name, $academic_year);
$stmt->execute();

header('Location: dashboard.php?settings_saved=1');
exit();
?>