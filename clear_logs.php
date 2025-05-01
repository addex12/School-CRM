<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */

require_once __DIR__ . '/includes/config.php';

$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$logFiles = [
    __DIR__ . '/error_log',
    __DIR__ . '/logs/error.log',
    __DIR__ . '/logs/raw_activity/log',
    __DIR__ . '/logs/user_activity.log'
];

$scheduleConfigFile = __DIR__ . '/admin/log_clear_schedule.json';
$lastClearFile = __DIR__ . '/admin/last_log_clear.txt';

// Determine schedule
$schedule = 'daily';
if (file_exists($scheduleConfigFile)) {
    $config = json_decode(file_get_contents($scheduleConfigFile), true);
    if (isset($config['interval'])) {
        $schedule = $config['interval'];
    }
}

// Check if it's time to clear logs
$shouldClear = false;
$now = time();
$lastClear = @file_get_contents($lastClearFile);
$lastClearTs = $lastClear ? strtotime($lastClear) : 0;

switch ($schedule) {
    case 'daily':
        $shouldClear = ($now - $lastClearTs) >= 86400;
        break;
    case 'weekly':
        $shouldClear = ($now - $lastClearTs) >= 604800;
        break;
    case 'monthly':
        $shouldClear = ($now - $lastClearTs) >= 2592000;
        break;
    case 'custom':
        // Support custom_value and custom_unit in config
        $customValue = isset($config['custom_value']) ? (int)$config['custom_value'] : 1;
        $customUnit = isset($config['custom_unit']) ? strtolower($config['custom_unit']) : 'days';
        $unitSeconds = [
            'seconds' => 1,
            'minutes' => 60,
            'hours'   => 3600,
            'days'    => 86400,
            'weeks'   => 604800,
            'months'  => 2592000 // 30 days
        ];
        $intervalSeconds = isset($unitSeconds[$customUnit]) ? $customValue * $unitSeconds[$customUnit] : 86400;
        $shouldClear = ($now - $lastClearTs) >= $intervalSeconds;
        break;
    default:
        $shouldClear = true;
}

if ($shouldClear) {
    // DB
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

    foreach ($logTables as $table) {
        $db->exec("TRUNCATE TABLE `$table`");
    }
    foreach ($logFiles as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
    file_put_contents($lastClearFile, date('Y-m-d H:i:s'));
}
