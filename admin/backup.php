<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Database Backup";

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_database'])) {
    try {
        $backupFile = __DIR__ . '/../backups/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($config['DB_USER']),
            escapeshellarg($config['DB_PASSWORD']),
            escapeshellarg($config['DB_HOST']),
            escapeshellarg($config['DB_NAME']),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Failed to create database backup. Please check server permissions.");
        }

        $_SESSION['success'] = "Database backup created successfully!";
        header("Location: backup.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Fetch existing backups
$backupDir = __DIR__ . '/../backups';
$backups = array_diff(scandir($backupDir), ['.', '..']);
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
                    <h2>Create New Backup</h2>
                    <form method="POST">
                        <button type="submit" name="backup_database" class="btn btn-primary">Backup Now</button>
                    </form>
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
                                            <form method="POST" action="delete_backup.php" style="display:inline;">
                                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup) ?>">
                                                <button type="submit" name="delete_backup" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this backup?')">Delete</button>
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
</body>
</html>
