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
    __DIR__ . '/../logs/raw_activity/log',
    __DIR__ . '/../logs/user_activity.log'
];

$scheduleConfigFile = __DIR__ . '/log_clear_schedule.json';
$lastClearFile = __DIR__ . '/last_log_clear.txt';
$nextClearFile = __DIR__ . '/next_log_clear.txt';

// Load schedule config
$schedule = 'daily';
$customValue = 1;
$customUnit = 'days';
if (file_exists($scheduleConfigFile)) {
    $config = json_decode(file_get_contents($scheduleConfigFile), true);
    if (isset($config['interval'])) {
        $schedule = $config['interval'];
        if ($schedule === 'custom') {
            $customValue = (isset($config['custom_value']) && is_numeric($config['custom_value']) && $config['custom_value'] > 0)
                ? (int)$config['custom_value'] : 1;
            $customUnit = (isset($config['custom_unit']) && in_array(strtolower($config['custom_unit']), [
                'seconds','minutes','hours','days','weeks','months'
            ])) ? strtolower($config['custom_unit']) : 'days';
        }
    }
}

// Calculate interval in seconds
$unitSeconds = [
    'seconds' => 1,
    'minutes' => 60,
    'hours'   => 3600,
    'days'    => 86400,
    'weeks'   => 604800,
    'months'  => 2592000 // 30 days
];
switch ($schedule) {
    case 'daily':
        $intervalSeconds = 86400;
        break;
    case 'weekly':
        $intervalSeconds = 604800;
        break;
    case 'monthly':
        $intervalSeconds = 2592000;
        break;
    case 'custom':
        $intervalSeconds = isset($unitSeconds[$customUnit]) ? $customValue * $unitSeconds[$customUnit] : 86400;
        if ($intervalSeconds < 1) $intervalSeconds = 1;
        break;
    default:
        $intervalSeconds = 86400;
}

// Update CRON job if called directly (e.g., after schedule change)
if (php_sapi_name() === 'cli' || isset($_SERVER['REQUEST_METHOD'])) {
    $cronScript = realpath(__DIR__ . '/../create_clear_logs_cron.sh');
    if ($cronScript && is_executable($cronScript)) {
        // Run in background to avoid blocking web requests
        exec("bash " . escapeshellarg($cronScript) . " >/dev/null 2>&1 &");
    }
}

// Get last clear time
$now = time();
$lastClear = @file_get_contents($lastClearFile);
$lastClearTs = $lastClear ? strtotime($lastClear) : 0;

// Loop: clear logs for every missed interval until next clear is in the future
while ($now >= $lastClearTs + $intervalSeconds) {
    // Clear logs
    foreach ($logTables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
    foreach ($logFiles as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
    // Update last clear time
    $lastClearTs += $intervalSeconds;
    file_put_contents($lastClearFile, date('Y-m-d H:i:s', $lastClearTs));
    file_put_contents($nextClearFile, date('Y-m-d H:i:s', $lastClearTs + $intervalSeconds));
}
