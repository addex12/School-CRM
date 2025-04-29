<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php'; // adjust path as needed
// ...existing code for authentication...

$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$message = '';
$error = '';

try {
    // Use existing PDO connection if available, else create one
    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } else {
        // Try to get connection details from config.php
        // Example: $dsn = "mysql:host=localhost;dbname=flipperschool_parent_survey_system;charset=utf8mb4";
        if (!isset($dsn)) {
            $dsn = "mysql:host=localhost;dbname=flipperschool_parent_survey_system;charset=utf8mb4";
        }
        if (!isset($db_user)) {
            $db_user = "root";
        }
        if (!isset($db_pass)) {
            $db_pass = "";
        }
        $db = new PDO($dsn, $db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
        foreach ($logTables as $table) {
            $db->exec("TRUNCATE TABLE `$table`");
        }
        file_put_contents(__DIR__ . '/last_log_clear.txt', date('Y-m-d H:i:s'));
        $message = "System logs cleared successfully.";
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$lastClear = @file_get_contents(__DIR__ . '/last_log_clear.txt');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scheduler - System Log Maintenance</title>
    <!-- ...existing head code... -->
</head>
<body>
    <!-- ...existing navbar/sidebar... -->
    <div class="container">
        <h2>System Log Scheduler</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <button type="submit" name="clear_logs" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear all system logs?');">
                Clear All System Logs Now
            </button>
        </form>
        <p class="mt-3">
            <strong>Last Cleared:</strong>
            <?= $lastClear ? htmlspecialchars($lastClear) : 'Never' ?>
        </p>
        <p>
            <em>Note: Logs are also cleared automatically by the system CRON job.</em>
        </p>
    </div>
</body>
</html>
