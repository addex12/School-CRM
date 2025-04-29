<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Set timezone for backup/restore
date_default_timezone_set('Africa/Nairobi');

require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "System Backup & Restore";

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_system'])) {
    try {
        $backupDir = __DIR__ . '/../backups';
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/full_backup_$timestamp.zip";

        // Create a zip archive
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
            throw new Exception("Failed to create backup archive.");
        }

        // Add database dump to the archive
        $dbDumpFile = $backupDir . "/db_backup_$timestamp.sql";
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            escapeshellarg($dbDumpFile)
        );
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to create database dump.");
        }
        $zip->addFile($dbDumpFile, "db_backup_$timestamp.sql");

        // Add system files to the archive
        $rootDir = realpath(__DIR__ . '/..');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $excludeDirs = [
            realpath($backupDir),
            realpath(__DIR__ . '/../vendor'),
            realpath(__DIR__ . '/../uploads'),
            realpath(__DIR__ . '/../logs'),
        ];
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            foreach ($excludeDirs as $excluded) {
                if ($excluded && strpos($filePath, $excluded) === 0) {
                    continue 2;
                }
            }
            if ($file->isFile()) {
                $relativePath = substr($filePath, strlen($rootDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        unlink($dbDumpFile);

        $_SESSION['success'] = "System backup created successfully!";
        header("Location: backup.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backups = is_dir($backupDir) ? array_diff(scandir($backupDir), ['.', '..']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }

        .adugna-main {
            min-height: 100vh;
            background: #f7f9fb;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .adugna-content-container {
            max-width: 900px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 20px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
            letter-spacing: 0.01em;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-btn-warning {
            background: #ffc107;
            color: #fff;
            border: 1px solid #ffc107;
        }
        .adugna-btn-warning:hover {
            background: #e0a800;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-table-actions {
            display: flex;
            gap: 0.5rem;
        }
        .adugna-alert-success {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        @media (max-width: 1100px) {
            .adugna-content-container {
                max-width: 99vw;
                margin: 18px 2vw 0 2vw;
                padding: 10px 4px 18px 4px;
            }
        }
        @media (max-width: 900px) {
            .adugna-main, .adugna-content-container {
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
            .adugna-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main, .adugna-content-container {
                padding: 0.5rem 0.2rem 0.7rem 0.2rem;
            }
            .adugna-card { padding: 0.7rem; }
            .adugna-header-title { font-size: 1.1em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-content-container">
                <div class="adugna-header-title">
                    <i class="fas fa-database"></i> <?= htmlspecialchars($pageTitle) ?>
                </div>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="adugna-alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="adugna-alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="adugna-card">
                        <div class="adugna-card-header"><i class="fas fa-download"></i> Create Backup</div>
                        <p style="margin-bottom:1em;">Click the button below to create a full system backup.</p>
                        <button type="submit" name="backup_system" class="adugna-btn">
                            <i class="fas fa-download"></i> Backup Now
                        </button>
                    </div>
                </form>
                <div class="adugna-card">
                    <div class="adugna-card-header"><i class="fas fa-archive"></i> Existing Backups</div>
                    <?php if (count($backups) > 0): ?>
                        <table class="adugna-table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($backup) ?></td>
                                        <td><?= date('M j, Y g:i A', filemtime($backupDir . '/' . $backup)) ?></td>
                                        <td class="adugna-table-actions">
                                            <a href="../backups/<?= urlencode($backup) ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <form method="POST" action="restore.php" style="display:inline;">
                                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup) ?>">
                                                <button type="submit" name="restore_backup" class="adugna-btn adugna-btn-warning adugna-btn-sm" onclick="return confirm('Are you sure you want to restore this backup?')">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color:#888;">No backups found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
