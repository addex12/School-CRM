<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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

// --- Remove all CRON/cronScript logic ---

// Ensure last/next clear files exist
$now = time();
if (!file_exists($lastClearFile)) {
    file_put_contents($lastClearFile, date('Y-m-d H:i:s', $now));
}
if (!file_exists($nextClearFile)) {
    file_put_contents($nextClearFile, date('Y-m-d H:i:s', $now + $intervalSeconds));
}
if (!file_exists($scheduleConfigFile)) {
    file_put_contents($scheduleConfigFile, json_encode(['interval' => $schedule]));
}

// Read last/next clear times
$lastClear = @file_get_contents($lastClearFile);
$lastClearTs = $lastClear ? strtotime($lastClear) : 0;
$nextClear = @file_get_contents($nextClearFile);
$nextClearTs = $nextClear ? strtotime($nextClear) : 0;

// Calculate next clear time if not set
if ($lastClearTs > 0 && $nextClearTs < $now) {
    $nextClearTs = $lastClearTs + $intervalSeconds;
}

// Only clear logs if the scheduled time has passed
if ($now >= $nextClearTs) {
    foreach ($logTables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
    foreach ($logFiles as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
    $lastClearTs = $now;
    $nextClearTs = $lastClearTs + $intervalSeconds;
    file_put_contents($lastClearFile, date('Y-m-d H:i:s', $lastClearTs));
}

// Always update next clear time file for UI countdown
file_put_contents($nextClearFile, date('Y-m-d H:i:s', $nextClearTs));

// Adugna Gizaw: ERPNext-inspired, responsive, adugna- UI for log clear status and manual trigger
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clear Logs - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 500px;
            margin: 38px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 22px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-danger {
            background: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .adugna-btn-danger:hover { background: #c82333; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
        .adugna-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1em;
            gap: 1em;
            flex-wrap: wrap;
        }
        .adugna-status-label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-status-value {
            font-size: 0.97em;
            color: #1976d2;
            font-weight: 600;
        }
        .adugna-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
        }
    </style>
    <script>
        // Adugna Gizaw: Countdown timer for next log clear
        document.addEventListener('DOMContentLoaded', function() {
            var countdownEl = document.getElementById('adugna-next-clear-countdown');
            if (!countdownEl) return;
            var nextClearTs = parseInt(countdownEl.getAttribute('data-next-ts'));
            function updateCountdown() {
                var now = Math.floor(Date.now() / 1000);
                var diff = nextClearTs - now;
                if (diff < 0) diff = 0;
                var d = Math.floor(diff / 86400);
                var h = Math.floor((diff % 86400) / 3600);
                var m = Math.floor((diff % 3600) / 60);
                var s = diff % 60;
                countdownEl.textContent =
                    (d > 0 ? d + 'd ' : '') +
                    (h > 0 ? h + 'h ' : '') +
                    (m > 0 ? m + 'm ' : '') +
                    s + 's';
            }
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-broom"></i> Clear System Logs
            </div>
            <div class="adugna-card">
                <div class="adugna-status-row"></div>
                    <span class="adugna-status-label"><i class="fas fa-history"></i> Last Cleared:</span>
                    <span class="adugna-status-value"><?= htmlspecialchars($lastClear ? $lastClear : '-') ?></span>
                </div>
                <div class="adugna-status-row"></div>
                    <span class="adugna-status-label"><i class="fas fa-clock"></i> Next Scheduled Clear:</span>
                    <span class="adugna-status-value" id="adugna-next-clear-countdown" data-next-ts="<?= $nextClearTs ?>">
                        <?= htmlspecialchars($nextClear ? $nextClear : '-') ?>
                    </span>
                </div>
                <div style="margin:1.2em 0 0.5em 0;"></div>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to clear all logs now?');"></form>
                        <button type="submit" name="clear_now" class="adugna-btn adugna-btn-danger adugna-btn-sm">
                            <i class="fas fa-trash"></i> Clear Logs Now
                        </button>
                    </form>
                </div>
                <div style="margin-top:1.2em;font-size:0.93em;color:#888;"></div>
                    <i class="fas fa-info-circle"></i> Logs are cleared automatically based on the configured schedule. You can also clear them manually above.
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
