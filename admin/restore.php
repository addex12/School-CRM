<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    $progressFile = __DIR__ . '/../restore_progress.txt';
    function write_restore_progress($percent, $message, $progressFile) {
        file_put_contents($progressFile, json_encode([
            'percent' => $percent,
            'message' => $message,
            'timestamp' => time()
        ]));
    }
    try {
        write_restore_progress(5, 'Validating backup file...', $progressFile);
        $backupFile = __DIR__ . '/../backups/' . basename($_POST['backup_file']);
        if (!file_exists($backupFile)) {
            write_restore_progress(100, 'Backup file not found.', $progressFile);
            throw new Exception("Backup file not found.");
        }

        write_restore_progress(15, 'Extracting backup archive...', $progressFile);
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            write_restore_progress(100, 'Failed to open backup archive.', $progressFile);
            throw new Exception("Failed to open backup archive.");
        }
        $restoreDir = __DIR__ . '/../restore_temp';
        $zip->extractTo($restoreDir);
        $zip->close();

        write_restore_progress(35, 'Restoring database...', $progressFile);
        $dbDumpFile = $restoreDir . '/db_backup.sql';
        if (!file_exists($dbDumpFile)) {
            write_restore_progress(100, 'Database dump file not found in the backup.', $progressFile);
            throw new Exception("Database dump file not found in the backup.");
        }
        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s %s < %s',
            escapeshellarg($config['DB_USER']),
            escapeshellarg($config['DB_PASSWORD']),
            escapeshellarg($config['DB_HOST']),
            escapeshellarg($config['DB_NAME']),
            escapeshellarg($dbDumpFile)
        );
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            write_restore_progress(100, 'Failed to restore database.', $progressFile);
            throw new Exception("Failed to restore database.");
        }

        write_restore_progress(60, 'Restoring system files...', $progressFile);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($restoreDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $totalFiles = iterator_count($files);
        $files->rewind();
        $processed = 0;
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($restoreDir) + 1);
            $destination = __DIR__ . '/../' . $relativePath;
            if ($file->isDir()) {
                mkdir($destination, 0755, true);
            } else {
                copy($filePath, $destination);
            }
            $processed++;
            if ($totalFiles > 0 && $processed % 10 === 0) {
                $percent = 60 + intval(35 * ($processed / $totalFiles));
                write_restore_progress($percent, 'Restoring system files... (' . $processed . '/' . $totalFiles . ')', $progressFile);
            }
        }

        write_restore_progress(97, 'Cleaning up temporary files...', $progressFile);
        array_map('unlink', glob("$restoreDir/*"));
        rmdir($restoreDir);

        write_restore_progress(100, 'System restored successfully!', $progressFile);
        $_SESSION['success'] = "System restored successfully!";
        header("Location: backup.php");
        exit();
    } catch (Exception $e) {
        write_restore_progress(100, 'Restore failed: ' . $e->getMessage(), $progressFile);
        $_SESSION['error'] = $e->getMessage();
        header("Location: backup.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Backup - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .restore-container {
            max-width: 100%;
            margin: 2rem auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            width: 90%;
        }
        .restore-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .restore-header h1 {
            font-size: 1.4rem;
            color: #34495e;
            margin: 0;
        }
        .restore-header .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .restore-header .btn:hover {
            background: #0056b3;
        }
        .restore-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        .restore-card h2 {
            font-size: 1.1rem;
            color: #34495e;
            margin: 0 0 0.5rem 0;
        }
        .restore-card p {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin: 0;
        }
        .restore-card .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .restore-card .btn:hover {
            background: #218838;
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
        @media (max-width: 768px) {
            .restore-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .restore-header h1 {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="restore-container">
                <div class="restore-header">
                    <h1><i class="fas fa-database"></i> Restore Backup</h1>
                    <a href="backup.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Backup</a>
                </div>
                <form method="POST">
                    <div class="restore-card">
                        <h2>Select Backup File</h2>
                        <p>Choose a backup file to restore your system.</p>
                        <input type="text" name="backup_file" placeholder="Enter backup file name" required>
                        <button type="submit" name="restore_backup" class="btn">
                            <i class="fas fa-upload"></i> Restore
                        </button>
                    </div>
                </form>
                <div class="restore-card">
                    <h2>Restore Progress</h2>
                    <div class="progress-bar">
                        <div class="progress" id="progress"></div>
                    </div>
                    <p id="progress-message" style="margin-top: 0.5rem; font-size: 0.9rem; color: #7f8c8d;">No progress yet.</p>
                </div>
            </div>
        </div>
    </div>
            <?php include 'includes/footer.php'; ?>

    <script>
        (function() {
            const progressBar = document.getElementById('progress');
            const progressMessage = document.getElementById('progress-message');
            const progressFile = '../restore_progress.txt';

            function fetchProgress() {
                fetch(progressFile + '?t=' + new Date().getTime())
                    .then(response => response.json())
                    .then(data => {
                        progressBar.style.width = data.percent + '%';
                        progressMessage.textContent = data.message;
                        if (data.percent < 100) {
                            setTimeout(fetchProgress, 1000);
                        }
                    })
                    .catch(() => {
                        progressMessage.textContent = 'Unable to fetch progress.';
                    });
            }

            fetchProgress();
        })();
    </script>
</body>
</html>

