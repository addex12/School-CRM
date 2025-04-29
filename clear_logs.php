<?php
require_once __DIR__ . '/config.php'; // adjust path as needed

$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$pdo = new PDO($dsn, $db_user, $db_pass); // adjust vars as needed
foreach ($logTables as $table) {
    $pdo->exec("TRUNCATE TABLE `$table`");
}
file_put_contents(__DIR__ . '/admin/last_log_clear.txt', date('Y-m-d H:i:s'));
