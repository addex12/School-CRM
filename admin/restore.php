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

