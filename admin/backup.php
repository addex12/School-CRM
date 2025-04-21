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
            // Skip excluded directories
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
        unlink($dbDumpFile); // Remove the temporary database dump file

        $_SESSION['success'] = "System backup created successfully!";
        header("Location: backup.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Ensure the backups directory exists
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true); // Create the directory if it doesn't exist
}

// Fetch existing backups
$backups = is_dir($backupDir) ? array_diff(scandir($backupDir), ['.', '..']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <section class="form-section">
                    <h2>Create Full System Backup</h2>
                    <form id="backupForm" method="POST">
                        <button type="submit" id="backupBtn" name="backup_system" class="btn btn-primary">Backup Now</button>
                    </form>
                    <div id="backupProgress" style="display:none; margin-top:10px;">
                        <div style="width:100%;background:#eee;border-radius:4px;overflow:hidden;">
                            <div id="progressBar" style="width:0%;height:20px;background:#007bff;"></div>
                        </div>
                        <div id="progressStatus" style="margin-top:5px;font-size:14px;color:#333;">Starting backup...</div>
                    </div>
                </section>

                <section class="table-section">
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
                                        <td>
                                            <a href="../backups/<?= urlencode($backup) ?>" class="btn btn-secondary" download>Download</a>
                                            <form method="POST" action="restore.php" style="display:inline;">
                                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup) ?>">
                                                <button type="submit" name="restore_backup" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore this backup?')">Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No backups found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="../assets/js/backup.js"></script>
</body>
</html>
