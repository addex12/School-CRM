<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_message = $_POST['notification_message'] ?? '';

    // Fetch all parent emails
    $parents = $conn->query("SELECT email FROM users WHERE role = 'parent'");
    while ($parent = $parents->fetch_assoc()) {
        $to = $parent['email'];
        $subject = "School Notification";
        $message = $notification_message;
        $headers = 'From: no-reply@schoolcrm.com' . "\r\n" .
                   'Reply-To: no-reply@schoolcrm.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }

    header('Location: dashboard.php?notification_sent=1');
    exit();
}
?>
