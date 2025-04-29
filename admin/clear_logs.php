<?php
require_once '../includes/config.php';

$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$logFiles = [
    __DIR__ . '/../error_log',
    __DIR__ . '/../logs/error.log',
    __DIR__ . '/../logs/raw_activity.log',
    __DIR__ . '/../logs/user_activity.log'
];

$scheduleConfigFile = __DIR__ . '/log_clear_schedule.json';
$lastClearFile = __DIR__ . '/last_log_clear.txt';

// Determine schedule
$schedule = 'daily';
$customValue = 1;
$customUnit = 'days';
if (file_exists($scheduleConfigFile)) {
    $config = json_decode(file_get_contents($scheduleConfigFile), true);
    if (isset($config['interval'])) {
        $schedule = $config['interval'];
        if ($schedule === 'custom') {
            $customValue = isset($config['custom_value']) && is_numeric($config['custom_value']) && $config['custom_value'] > 0
                ? (int)$config['custom_value'] : 1;
            $customUnit = isset($config['custom_unit']) && in_array(strtolower($config['custom_unit']), [
                'seconds','minutes','hours','days','weeks','months'
            ]) ? strtolower($config['custom_unit']) : 'days';
        }
    }
}

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
        $unitSeconds = [
            'seconds' => 1,
            'minutes' => 60,
            'hours'   => 3600,
            'days'    => 86400,
            'weeks'   => 604800,
            'months'  => 2592000 // 30 days
        ];
        $intervalSeconds = isset($unitSeconds[$customUnit]) ? $customValue * $unitSeconds[$customUnit] : 86400;
        if ($intervalSeconds < 1) $intervalSeconds = 86400;
        $shouldClear = ($now - $lastClearTs) >= $intervalSeconds;
        break;
    default:
        $shouldClear = true;
}

if ($shouldClear) {
    foreach ($logTables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
    foreach ($logFiles as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
    file_put_contents($lastClearFile, date('Y-m-d H:i:s'));
}
