<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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
    <!--
        Developer: Adugna Gizaw
        Email: gizawadugna@gmail.com
        LinkedIn: https://www.linkedin.com/in/eleganceict
        Twitter: https://twitter.com/eleganceict1
        GitHub: https://github.com/addex12
        Description: Responsive, interactive backup restore page with adugna-compact, ERPNext-inspired styles.
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Backup - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .adugna-admin-main {
            max-width: 600px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(25, 118, 210, 0.09);
            padding: 28px 18px 38px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-restore-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .adugna-restore-header h1 {
            font-size: 1.1rem;
            color: #34495e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .adugna-card {
            background: #fff;
            border: 1px solid #e3e6eb;
            border-radius: 6px;
            padding: 1.1rem 1.2rem;
            margin-bottom: 1.1rem;
            box-shadow: 0 1px 4px rgba(52,152,219,0.04);
        }
        .adugna-card h2 {
            font-size: 1.05rem;
            color: #34495e;
            margin: 0 0 0.4rem 0;
            font-weight: 600;
        }
        .adugna-card p {
            font-size: 0.89rem;
            color: #7f8c8d;
            margin: 0 0 0.7rem 0;
        }
        .adugna-btn {
            font-size: 0.87rem;
            padding: 0.32rem 0.7rem;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.18s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
        }
        .adugna-btn.adugna-green { background: #27ae60; }
        .adugna-btn.adugna-blue { background: #3498db; }
        .adugna-btn.adugna-grey { background: #7f8c8d; }
        .adugna-btn:hover { background: #217dbb; }
        .adugna-btn.adugna-green:hover { background: #218838; }
        .adugna-btn.adugna-grey:hover { background: #636e72; }
        .adugna-progress-bar {
            width: 100%;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 0.7rem;
            height: 7px;
        }
        .adugna-progress-bar .adugna-progress {
            height: 100%;
            background: #3498db;
            width: 0;
            transition: width 0.3s;
        }
        @media (max-width: 600px) {
            .adugna-admin-main { padding: 1rem 0.3rem; }
            .adugna-card { padding: 0.7rem 0.6rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-admin-main">
            <div class="adugna-restore-header">
                <h1>
                    <i class="fas fa-database"></i> Restore Backup
                </h1>
                <a href="backup.php" class="adugna-btn adugna-grey" style="font-size:0.85rem;">
                    <i class="fas fa-arrow-left"></i> Back to Backup
                </a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="adugna-card">
                    <h2><i class="fas fa-file-archive"></i> Select Backup File</h2>
                    <p>Choose a backup file to restore your system.</p>
                    <input type="text" name="backup_file" placeholder="Enter backup file name" required style="width:100%;padding:0.4rem 0.7rem;font-size:0.93rem;border:1px solid #e3e6eb;border-radius:4px;margin-bottom:0.7rem;">
                    <div style="margin-bottom:0.7rem;">
                        <label for="upload_file" style="font-size:0.95em;color:#34495e;">Or upload backup file:</label>
                        <input type="file" name="upload_file" id="upload_file" accept=".zip,.sql" style="margin-top:0.3em;">
                        <button type="submit" name="upload_and_restore" class="adugna-btn adugna-blue" style="margin-left:0.7em;">
                            <i class="fas fa-upload"></i> Upload & Restore
                        </button>
                    </div>
                    <button type="submit" name="restore_backup" class="adugna-btn adugna-green">
                        <i class="fas fa-upload"></i> Restore
                    </button>
                </div>
            </form>
            <div class="adugna-card">
                <h2><i class="fas fa-tasks"></i> Restore Progress</h2>
                <div class="adugna-progress-bar">
                    <div class="adugna-progress" id="progress"></div>
                </div>
                <p id="progress-message" style="margin-top: 0.5rem; font-size: 0.9rem; color: #7f8c8d;">No progress yet.</p>
                <form method="post" style="margin-top:1em;">
                    <button type="submit" name="clear_restore_cache" class="adugna-btn adugna-grey" style="font-size:0.92em;">
                        <i class="fas fa-trash"></i> Clear Failed Cache
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
    /**
     * Adugna Gizaw: Progress fetch for restore, adugna-compact, responsive.
     */
    (function() {
        const progressBar = document.getElementById('progress');
        const progressMessage = document.getElementById('progress-message');
        function fetchProgress() {
            fetch('restore_status.php?t=' + new Date().getTime())
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
<?php
// Clear failed restore cache if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_restore_cache'])) {
    $progressFile = __DIR__ . '/../restore_progress.txt';
    if (file_exists($progressFile)) {
        unlink($progressFile);
    }
    // Show success message after clearing cache, then do nothing else
    echo "<script>alert('Restore progress cache cleared successfully!');window.location='restore.php';</script>";
    exit();
}
?>

