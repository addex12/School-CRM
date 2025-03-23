<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

// Handle system settings actions (update settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...handle form submissions for system settings...
}

$settings = $conn->query("SELECT * FROM system_settings")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
</head>
<body>
    <h1>System Settings</h1>
    <form method="post">
        <?php foreach ($settings as $setting): ?>
        <div>
            <label for="<?= $setting['setting_key'] ?>"><?= $setting['setting_key'] ?>:</label>
            <input type="text" id="<?= $setting['setting_key'] ?>" name="<?= $setting['setting_key'] ?>" value="<?= $setting['setting_value'] ?>">
        </div>
        <?php endforeach; ?>
        <button type="submit">Save Settings</button>
    </form>
</body>
</html>
