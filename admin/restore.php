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
        Description: Responsive, interactive backup restore page with sidebar toggle and submenu logic.
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Backup - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: Sidebar and layout styles with adugna- prefix for patenting */
        .adugna-sidebar {
            width: 220px;
            background: #232f3e;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: transform 0.3s;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .adugna-sidebar.adugna-closed {
            transform: translateX(-100%);
        }
        .adugna-sidebar .adugna-logo {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 1rem 1.2rem;
            letter-spacing: 1px;
            background: #1a222c;
            margin-bottom: 0.5rem;
        }
        .adugna-sidebar ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .adugna-sidebar li {
            position: relative;
        }
        .adugna-sidebar a {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            color: #cfd8dc;
            font-size: 0.93rem;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .adugna-sidebar a.adugna-active, .adugna-sidebar a:hover {
            background: #1a222c;
            color: #fff;
            border-left: 3px solid #3498db;
        }
        .adugna-sidebar .adugna-icon {
            font-size: 1rem;
            margin-right: 0.7rem;
            width: 1.2rem;
            text-align: center;
        }
        .adugna-sidebar .adugna-submenu-toggle {
            margin-left: auto;
            font-size: 0.8rem;
            transition: transform 0.2s;
        }
        .adugna-sidebar .adugna-submenu {
            display: none;
            background: #25344a;
        }
        .adugna-sidebar .adugna-submenu.adugna-open {
            display: block;
        }
        .adugna-sidebar .adugna-submenu a {
            padding-left: 2.2rem;
            font-size: 0.89rem;
        }
        .adugna-sidebar .adugna-submenu-toggle.adugna-rotated {
            transform: rotate(90deg);
        }
        .adugna-sidebar-toggle-btn {
            display: none;
            position: fixed;
            left: 1rem;
            top: 1rem;
            background: #232f3e;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 2.2rem;
            height: 2.2rem;
            z-index: 200;
            font-size: 1.2rem;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: pointer;
        }
        /* Adugna Gizaw: Responsive layout for admin dashboard */
        .adugna-admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: #f4f6fa;
        }
        .adugna-admin-main {
            flex: 1;
            margin-left: 220px;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            transition: margin-left 0.3s;
        }
        @media (max-width: 900px) {
            .adugna-admin-main {
                margin-left: 0;
            }
            .adugna-sidebar {
                position: fixed;
                height: 100vh;
                z-index: 100;
            }
            .adugna-sidebar-toggle-btn {
                display: flex;
            }
        }
        /* Adugna Gizaw: Compact ERPNext-inspired card and button styles */
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
        .adugna-btn.adugna-green {
            background: #27ae60;
        }
        .adugna-btn.adugna-blue {
            background: #3498db;
        }
        .adugna-btn.adugna-grey {
            background: #7f8c8d;
        }
        .adugna-btn:hover {
            background: #217dbb;
        }
        .adugna-btn.adugna-green:hover {
            background: #218838;
        }
        .adugna-btn.adugna-grey:hover {
            background: #636e72;
        }
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
        /* Adugna Gizaw: Responsive tweaks */
        @media (max-width: 600px) {
            .adugna-admin-main {
                padding: 1rem 0.3rem;
            }
            .adugna-card {
                padding: 0.7rem 0.6rem;
            }
            .adugna-sidebar {
                width: 170px;
            }
        }
    </style>
</head>
<body>
    <!-- Adugna Gizaw: Sidebar toggle button for mobile -->
    <button class="adugna-sidebar-toggle-btn" id="adugnaSidebarToggle" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="adugna-admin-dashboard">
        <!-- Adugna Gizaw: Sidebar with submenu logic -->
        <nav class="adugna-sidebar" id="adugnaSidebar">
            <div class="adugna-logo">
                <i class="fas fa-school"></i> School CRM
            </div>
            <ul>
                <li>
                    <a href="dashboard.php" class="adugna-sidebar-link" data-page="dashboard.php">
                        <span class="adugna-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="backup.php" class="adugna-sidebar-link" data-page="backup.php">
                        <span class="adugna-icon"><i class="fas fa-database"></i></span> Backup
                    </a>
                </li>
                <li>
                    <a href="restore.php" class="adugna-sidebar-link" data-page="restore.php">
                        <span class="adugna-icon"><i class="fas fa-upload"></i></span> Restore
                    </a>
                </li>
                <li>
                    <a href="#" class="adugna-sidebar-link adugna-has-submenu" data-submenu="settings">
                        <span class="adugna-icon"><i class="fas fa-cogs"></i></span> Settings
                        <span class="adugna-submenu-toggle"><i class="fas fa-chevron-right"></i></span>
                    </a>
                    <ul class="adugna-submenu" data-submenu="settings">
                        <li>
                            <a href="users.php" class="adugna-sidebar-link" data-page="users.php">
                                <span class="adugna-icon"><i class="fas fa-users"></i></span> Users
                            </a>
                        </li>
                        <li>
                            <a href="roles.php" class="adugna-sidebar-link" data-page="roles.php">
                                <span class="adugna-icon"><i class="fas fa-user-shield"></i></span> Roles
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div class="adugna-admin-main">
            <div class="adugna-restore-container">
                <div class="adugna-restore-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:1rem;">
                    <h1 style="font-size:1.1rem;color:#34495e;margin:0;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fas fa-database"></i> Restore Backup
                    </h1>
                    <a href="backup.php" class="adugna-btn adugna-grey" style="font-size:0.85rem;">
                        <i class="fas fa-arrow-left"></i> Back to Backup
                    </a>
                </div>
                <form method="POST">
                    <div class="adugna-card">
                        <h2><i class="fas fa-file-archive"></i> Select Backup File</h2>
                        <p>Choose a backup file to restore your system.</p>
                        <input type="text" name="backup_file" placeholder="Enter backup file name" required style="width:100%;padding:0.4rem 0.7rem;font-size:0.93rem;border:1px solid #e3e6eb;border-radius:4px;margin-bottom:0.7rem;">
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
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
    /**
     * Adugna Gizaw: Sidebar toggle, submenu logic, active page highlight, and progress fetch.
     */
    (function() {
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('adugnaSidebar');
        const toggleBtn = document.getElementById('adugnaSidebarToggle');
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('adugna-closed');
        });

        // Keep sidebar open on desktop, close on mobile navigation
        function handleSidebarOnResize() {
            if (window.innerWidth > 900) {
                sidebar.classList.remove('adugna-closed');
            }
        }
        window.addEventListener('resize', handleSidebarOnResize);
        handleSidebarOnResize();

        // Submenu logic
        document.querySelectorAll('.adugna-has-submenu').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const submenuName = link.getAttribute('data-submenu');
                const submenu = document.querySelector('.adugna-submenu[data-submenu="' + submenuName + '"]');
                const toggleIcon = link.querySelector('.adugna-submenu-toggle');
                submenu.classList.toggle('adugna-open');
                toggleIcon.classList.toggle('adugna-rotated');
            });
        });

        // Highlight active page
        const currentPage = location.pathname.split('/').pop();
        document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
            if (link.getAttribute('data-page') === currentPage) {
                link.classList.add('adugna-active');
                // Open parent submenu if inside submenu
                const submenu = link.closest('.adugna-submenu');
                if (submenu) {
                    submenu.classList.add('adugna-open');
                    const parentToggle = submenu.parentElement.querySelector('.adugna-submenu-toggle');
                    if (parentToggle) parentToggle.classList.add('adugna-rotated');
                }
            }
        });

        // Close sidebar on mobile after navigation
        document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 900) {
                    sidebar.classList.add('adugna-closed');
                }
            });
        });

        // Restore progress fetch logic
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

