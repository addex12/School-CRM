<?php
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .backup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .backup-header h1 {
            font-size: 1.5rem;
            color: #34495e;
            margin: 0;
        }
        .backup-header .btn {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }
        .backup-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .backup-card h2 {
            font-size: 1.2rem;
            color: #34495e;
            margin: 0 0 0.5rem 0;
        }
        .backup-card p {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin: 0;
        }
        .backup-card .btn {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background: #f4f6f9;
            color: #34495e;
        }
        .table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        .progress-bar {
            width: 100%;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 1rem;
        }
        .progress-bar .progress {
            height: 8px;
            background: #3498db;
            width: 0;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <div class="backup-header">
            <h1><i class="fas fa-database"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <form method="POST">
            <div class="backup-card">
                <h2>Create Backup</h2>
                <p>Click the button below to create a full system backup.</p>
                <button type="submit" name="backup_system" class="btn btn-primary">
                    <i class="fas fa-download"></i> Backup Now
                </button>
            </div>
        </form>
        <div class="backup-card">
            <h2>Existing Backups</h2>
            <?php if (count($backups) > 0): ?>
                <table class="table">
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
                                <td class="table-actions">
                                    <a href="../backups/<?= urlencode($backup) ?>" class="btn btn-secondary" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <form method="POST" action="restore.php" style="display:inline;">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup) ?>">
                                        <button type="submit" name="restore_backup" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore this backup?')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No backups found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
