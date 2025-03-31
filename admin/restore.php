<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    try {
        $backupFile = __DIR__ . '/../backups/' . basename($_POST['backup_file']);
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found.");
        }

        // Extract the backup archive
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            throw new Exception("Failed to open backup archive.");
        }
        $restoreDir = __DIR__ . '/../restore_temp';
        $zip->extractTo($restoreDir);
        $zip->close();

        // Restore the database
        $dbDumpFile = $restoreDir . '/db_backup.sql';
        if (!file_exists($dbDumpFile)) {
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
            throw new Exception("Failed to restore database.");
        }

        // Restore system files
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($restoreDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($restoreDir) + 1);
            $destination = __DIR__ . '/../' . $relativePath;
            if ($file->isDir()) {
                mkdir($destination, 0755, true);
            } else {
                copy($filePath, $destination);
            }
        }

        // Clean up temporary files
        array_map('unlink', glob("$restoreDir/*"));
        rmdir($restoreDir);

        $_SESSION['success'] = "System restored successfully!";
        header("Location: backup.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: backup.php");
        exit();
    }
}
?>
