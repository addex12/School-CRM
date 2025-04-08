<?php
session_start();
$error_message = $_SESSION['error'] ?? "An unexpected error occurred.";
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="error-container">
        <h1>Error</h1>
        <p><?= htmlspecialchars($error_message) ?></p>
        <a href="admin/dashboard.php" class="btn">Go Back to Dashboard</a>
    </div>
</body>
</html>
