<?php
session_start();
// ...existing code for admin check...

// ...existing code for database connection...

$email_notifications = isset($_POST['email_notifications']) ? '1' : '0';
$sms_notifications   = isset($_POST['sms_notifications']) ? '1' : '0';

// Additional customizable settings can be added here...
// $slack_integration = $_POST['slack_integration'] ?? '';

$sql = "INSERT INTO system_settings (key_name, key_value) VALUES
        ('email_notifications', ?),
        ('sms_notifications', ?)
        ON DUPLICATE KEY UPDATE key_value=VALUES(key_value)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $email_notifications, $sms_notifications);
$stmt->execute();

// ...existing code or additional logging...

header('Location: dashboard.php?comm_settings_saved=1');
exit();
