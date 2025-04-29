<?php
session_start();
require_once '../config.php'; // adjust path as needed
// ...existing code for authentication...

$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $pdo = new PDO($dsn, $db_user, $db_pass); // adjust vars as needed
    foreach ($logTables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
    file_put_contents(__DIR__ . '/last_log_clear.txt', date('Y-m-d H:i:s'));
    $message = "System logs cleared successfully.";
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
