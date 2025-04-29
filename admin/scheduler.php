<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
session_start();
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

try {
    // Use the existing $pdo connection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
        include __DIR__ . '/clear_logs.php';
        $message = "System logs and log files cleared successfully.";
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$lastClear = @file_get_contents(__DIR__ . '/last_log_clear.txt');
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
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="adugna-container">
                <div class="adugna-title"><i class="fa fa-clock" style="font-size:1em;margin-right:0.3em;"></i> System Log Scheduler</div>
                <?php if ($message): ?>
                    <div class="adugna-alert adugna-alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="adugna-alert adugna-alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" style="margin-bottom:1.2em;">
                    <button type="submit" name="clear_logs" class="adugna-btn adugna-btn-danger" onclick="return confirm('Are you sure you want to clear all system logs and log files?');">
                        <i class="fa fa-trash"></i> Clear All System Logs & Log Files Now
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
                    <em>Note: Logs are also cleared automatically by the system CRON job based on the selected schedule.</em>
                </div>
            </div>
        </div>
    </div>
            <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.getElementById('interval').addEventListener('change', function() {
            document.getElementById('adugna-customSchedule').style.display = this.value === 'custom' ? 'inline' : 'none';
        });
    </script>
</body>
</html>
