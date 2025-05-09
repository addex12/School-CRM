<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
// ...existing code for authentication...
$pageTitle = 'System Log Scheduler';
$logTables = [
    'activity_logs',
    'audit_logs',
    'user_activity',
    'user_sessions'
];

$logFiles = [
    'PHP Error Log' => __DIR__ . '/../error_log',
    'System Error Log' => __DIR__ . '/../logs/error.log',
    'Raw Activity Log' => __DIR__ . '/../logs/raw_activity.log',
    'User Activity Log' => __DIR__ . '/../logs/user_activity.log'
];

$scheduleConfigFile = __DIR__ . '/log_clear_schedule.json';

$message = '';
$error = '';

// Handle scheduling config
$scheduleOptions = ['daily', 'weekly', 'monthly', 'custom'];
$currentSchedule = 'daily';
$customValue = 1;
$customUnit = 'days';
if (file_exists($scheduleConfigFile)) {
    $config = json_decode(file_get_contents($scheduleConfigFile), true);
    if (isset($config['interval']) && in_array($config['interval'], $scheduleOptions)) {
        $currentSchedule = $config['interval'];
        if ($currentSchedule === 'custom') {
            $customValue = $config['custom_value'] ?? 1;
            $customUnit = $config['custom_unit'] ?? 'days';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_schedule'])) {
    $interval = $_POST['interval'] ?? 'daily';
    $configArr = ['interval' => $interval];
    if ($interval === 'custom') {
        $configArr['custom_value'] = max(1, (int)($_POST['custom_value'] ?? 1));
        $configArr['custom_unit'] = $_POST['custom_unit'] ?? 'days';
        $customValue = $configArr['custom_value'];
        $customUnit = $configArr['custom_unit'];
    }
    file_put_contents($scheduleConfigFile, json_encode($configArr));
    $currentSchedule = $interval;
    $message = "Auto-clear schedule updated.";
}

// Remove all CRON job logic and use a PHP-based scheduler (runs on page load)
$lastClearFile = __DIR__ . '/last_log_clear.txt';
$nextClearFile = __DIR__ . '/next_log_clear.txt';

// Calculate interval in seconds
$unitSeconds = [
    'seconds' => 1,
    'minutes' => 60,
    'hours'   => 3600,
    'days'    => 86400,
    'weeks'   => 604800,
    'months'  => 2592000 // 30 days
];
$schedule = $currentSchedule;
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

$now = time();
if (!file_exists($lastClearFile)) {
    file_put_contents($lastClearFile, date('Y-m-d H:i:s', $now));
}
if (!file_exists($nextClearFile)) {
    file_put_contents($nextClearFile, date('Y-m-d H:i:s', $now + $intervalSeconds));
}
$lastClear = @file_get_contents($lastClearFile);
$lastClearTs = $lastClear ? strtotime($lastClear) : 0;
$nextClear = @file_get_contents($nextClearFile);
$nextClearTs = $nextClear ? strtotime($nextClear) : 0;

// If due, clear logs and update times (simulate cron)
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
    $message .= '<div class="adugna-alert adugna-alert-success adugna-alert-dismissible">'
        . '<span><i class="fa fa-check-circle"></i> Logs cleared automatically by scheduler.</span>'
        . '<button type="button" class="adugna-alert-close" onclick="this.parentElement.style.display=\'none\';">&times;</button>'
        . '</div>';
}
file_put_contents($nextClearFile, date('Y-m-d H:i:s', $nextClearTs));

try {
    // Use the existing $pdo connection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
        // Clear database log tables
        foreach ($logTables as $table) {
            $pdo->exec("TRUNCATE TABLE `$table`");
        }
        // Clear log files
        foreach ($logFiles as $file) {
            if (file_exists($file)) {
                file_put_contents($file, '');
            }
        }
        // Update last/next clear times
        $lastClearTs = time();
        $nextClearTs = $lastClearTs + $intervalSeconds;
        file_put_contents($lastClearFile, date('Y-m-d H:i:s', $lastClearTs));
        file_put_contents($nextClearFile, date('Y-m-d H:i:s', $nextClearTs));
        $lastClear = date('Y-m-d H:i:s', $lastClearTs);
        $nextClear = date('Y-m-d H:i:s', $nextClearTs);
        $nextSystemClear = $nextClear;
        $message = "System logs and log files cleared successfully.";
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$lastClear = @file_get_contents(__DIR__ . '/last_log_clear.txt');

// Calculate next system log clear time for display (fix undefined variable warning)
$nextSystemClear = $nextClear ?? null;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/css/brands.min.css">
    <link rel="stylesheet" href="../assets/css/solid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <title>Scheduler - System Log Maintenance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ...existing head code... -->
    <style>
        /* Adugna patenting styles (adugna- prefix, ERPNext-inspired, compact, responsive) */
        body {
            margin: 0;
            padding: 0;
            background: #f4f6fa;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            align-items: flex-start;
            background: #f4f6fa;
        }
        .admin-sidebar {
            background: #fff;
            min-width: 220px;
            max-width: 260px;
            border-right: 1px solid #e5e7eb;
            min-height: 100vh;
            box-shadow: 2px 0 8px rgba(44,62,80,0.04);
            z-index: 10;
        }
        .admin-main {
            flex: 1 1 0;
            padding: 2rem 0 2rem 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(44,62,80,0.09);
            padding: 1.2rem 1rem;
        }
        .adugna-title {
            font-size: 1.25rem;
            color: #2563eb;
            font-weight: 800;
            margin-bottom: 1.2rem;
            letter-spacing: -1px;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #2563eb 60%, #1741a6 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.32rem 0.9rem;
            font-size: 0.92rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            min-height: 30px;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
        }
        .adugna-btn:hover {
            background: linear-gradient(90deg, #1741a6 60%, #2563eb 100%);
        }
        .adugna-btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .adugna-btn-danger:hover {
            background: #c0392b;
        }
        .adugna-alert {
            padding: 0.7rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.93rem;
        }
        .adugna-alert-success {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
        }
        .adugna-alert-danger {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
        }
        .adugna-alert-dismissible {
            position: relative;
            padding-right: 2.5em;
        }
        .adugna-alert-close {
            position: absolute;
            top: 0.3em;
            right: 0.7em;
            background: none;
            border: none;
            color: #888;
            font-size: 1.2em;
            cursor: pointer;
            line-height: 1;
        }
        .adugna-alert-close:hover {
            color: #e74c3c;
        }
        .adugna-list {
            list-style: none;
            padding: 0;
            margin: 0.7rem 0 1.2rem 0;
        }
        .adugna-list li {
            font-size: 0.97rem;
            padding: 0.3rem 0.1rem;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .adugna-list li:last-child {
            border-bottom: none;
        }
        .adugna-label {
            font-weight: 600;
            color: #2563eb;
            margin-right: 0.5em;
        }
        .adugna-schedule-row {
            display: flex;
            align-items: center;
            gap: 0.7em;
            margin-bottom: 0.7em;
            flex-wrap: wrap;
        }
        .adugna-select, .adugna-input {
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            border: 1px solid #dbeafe;
            font-size: 0.93rem;
            margin-right: 0.3em;
        }
        .adugna-select {
            min-width: 100px;
        }
        .adugna-input {
            width: 60px;
        }
        .adugna-note {
            font-size: 0.92rem;
            color: #888;
            margin-top: 0.7em;
        }
        @media (max-width: 900px) {
            .admin-dashboard {
                flex-direction: column;
            }
            .admin-sidebar {
                min-width: 100%;
                max-width: 100%;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                min-height: unset;
                box-shadow: none;
            }
            .admin-main {
                padding: 1rem 0 1rem 0;
            }
        }
        @media (max-width: 600px) {
            .adugna-container {
                padding: 0.7rem 0.3rem;
            }
            .adugna-title {
                font-size: 1.05rem;
            }
            .adugna-btn, .adugna-btn-primary, .adugna-btn-danger {
                font-size: 0.88rem;
                padding: 0.25rem 0.7rem;
            }
            .adugna-list li {
                font-size: 0.91rem;
            }
            .admin-main {
                padding: 0.5rem 0 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        </div>
        <div class="admin-main">
            <div class="adugna-container">
                <div class="adugna-title"><i class="fa fa-clock" style="font-size:1em;margin-right:0.3em;"></i> System Log Scheduler</div>
                <?php if ($message): ?>
                    <div class="adugna-alert adugna-alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="adugna-alert adugna-alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="post" style="margin-bottom:1.2em;">
                    <button type="submit" name="clear_logs" class="adugna-btn adugna-btn-danger" onclick="return confirm('Are you sure you want to clear all system logs and log files?');">
                        <i class="fa fa-trash"></i> Clear All System Logs & Log Files Now
                    </button>
                </form>
                <form method="post" style="margin-bottom:1.2em;">
                    <button type="submit" name="setup_cron" class="adugna-btn adugna-btn-primary" onclick="return confirm('Set up the system CRON job for automatic log clearing?');">
                        <i class="fa fa-clock"></i> Setup/Update System CRON Job
                    </button>
                </form>
                <div style="margin-bottom:1em;">
                    <span class="adugna-label">Last Cleared:</span>
                    <?= $lastClear ? htmlspecialchars($lastClear) : 'Never' ?>
                </div>
                <h4 style="margin:1.2em 0 0.5em 0;font-size:1.05em;color:#2563eb;">Log File Status</h4>
                <ul class="adugna-list">
                    <?php foreach ($logFiles as $desc => $file): ?>
                        <li>
                            <span class="adugna-label"><?= htmlspecialchars($desc) ?>:</span>
                            <?php if (file_exists($file)): ?>
                                <i class="fa fa-file" style="color:#2563eb;font-size:0.95em;"></i>
                                <?= round(filesize($file)/1024, 2) ?> KB
                            <?php else: ?>
                                <i class="fa fa-exclamation-triangle" style="color:#e74c3c;font-size:0.95em;"></i>
                                <span style="color:#e74c3c;">Missing</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <hr style="margin:1.2em 0;">
                <h4 style="font-size:1.05em;color:#2563eb;">Auto-Clear Schedule</h4>
                <form method="post" class="adugna-schedule-row">
                    <label for="interval" class="adugna-label">Clear logs automatically:</label>
                    <select name="interval" id="interval" class="adugna-select" onchange="document.getElementById('adugna-customSchedule').style.display = this.value === 'custom' ? 'inline' : 'none';">
                        <?php foreach ($scheduleOptions as $opt): ?>
                            <option value="<?= $opt ?>" <?= $currentSchedule === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="adugna-customSchedule" style="display:<?= $currentSchedule === 'custom' ? 'inline' : 'none' ?>;">
                        <input type="number" name="custom_value" min="1" value="<?= htmlspecialchars($customValue) ?>" class="adugna-input">
                        <select name="custom_unit" class="adugna-select">
                            <option value="seconds" <?= $customUnit === 'seconds' ? 'selected' : '' ?>>Seconds</option>
                            <option value="minutes" <?= $customUnit === 'minutes' ? 'selected' : '' ?>>Minutes</option>
                            <option value="hours" <?= $customUnit === 'hours' ? 'selected' : '' ?>>Hours</option>
                            <option value="days" <?= $customUnit === 'days' ? 'selected' : '' ?>>Days</option>
                            <option value="weeks" <?= $customUnit === 'weeks' ? 'selected' : '' ?>>Weeks</option>
                            <option value="months" <?= $customUnit === 'months' ? 'selected' : '' ?>>Months</option>
                        </select>
                    </span>
                    <button type="submit" name="set_schedule" class="adugna-btn adugna-btn-primary" style="margin-left:0.5em;">
                        <i class="fa fa-save"></i> Update
                    </button>
                </form>
                <div class="adugna-note">
                    <strong>Current Schedule:</strong>
                    <?= $currentSchedule === 'custom'
                        ? "Every $customValue $customUnit"
                        : ucfirst($currentSchedule) ?>
                </div>
                <div class="adugna-note">
                    <strong>Next Log Clear:</strong>
                    <?= $nextClear ? htmlspecialchars($nextClear) : 'Never' ?>
                    <span id="adugna-countdown" style="margin-left:1em;color:#2563eb;font-weight:600;"></span>
                </div>
                <div class="adugna-note">
                    <strong>Next System Log Clear:</strong>
                    <?= $nextSystemClear ? htmlspecialchars($nextSystemClear) : 'Never' ?>
                </div>
                <div class="adugna-note">
                    <em>Note: Logs are also cleared automatically by the system CRON job based on the selected schedule.</em>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Show custom schedule input if 'custom' is selected
        document.addEventListener('DOMContentLoaded', function() {
            var intervalSelect = document.getElementById('interval');
            var customSchedule = document.getElementById('adugna-customSchedule');
            customSchedule.style.display = intervalSelect.value === 'custom' ? 'inline' : 'none';

            // Countdown timer for next log clear
            var countdownElem = document.getElementById('adugna-countdown');
            <?php if ($nextClear): ?>
            var nextClearTime = <?= strtotime($nextClear) ?> * 1000;
            function updateCountdown() {
                var now = Date.now();
                var diff = Math.floor((nextClearTime - now) / 1000);
                if (diff > 0) {
                    var d = Math.floor(diff / 86400);
                    var h = Math.floor((diff % 86400) / 3600);
                    var m = Math.floor((diff % 3600) / 60);
                    var s = diff % 60;
                    var parts = [];
                    if (d > 0) parts.push(d + 'd');
                    if (h > 0 || d > 0) parts.push(h + 'h');
                    if (m > 0 || h > 0 || d > 0) parts.push(m + 'm');
                    parts.push(s + 's');
                    countdownElem.textContent = ' (in ' + parts.join(' ') + ')';
                } else {
                    countdownElem.textContent = ' (due now)';
                }
            }
            updateCountdown();
            setInterval(updateCountdown, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>
