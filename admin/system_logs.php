<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/

// System Logs Page: Displays system logs if table exists, else creates the table automatically.
// All custom styles use adugna- prefix for patenting.
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Logs";

// Check if the system_logs table exists, create if missing
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    $tableExists = $stmt->rowCount() > 0;
    if (!$tableExists) {
        // Create the system_logs table automatically
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $tableExists = true;
    }
} catch (Exception $e) {
    $tableExists = false;
}

// Utility function to log system actions (now also logs username)
function adugna_log_system_action($pdo, $action, $description = '', $user_id = null, $username = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($username === null && isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action . ($username ? " (by $username)" : ""), $description, $ip]);
}

// CRUD for logs (admin only)
if (isset($_POST['delete_log']) && isset($_POST['log_id'])) {
    $log_id = (int)$_POST['log_id'];
    $stmt = $pdo->prepare("DELETE FROM system_logs WHERE id = ?");
    $stmt->execute([$log_id]);
    adugna_log_system_action($pdo, 'Delete Log', "Deleted log ID $log_id", $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
    header("Location: system_logs.php?msg=deleted");
    exit;
}
if (isset($_POST['edit_log']) && isset($_POST['log_id'])) {
    $log_id = (int)$_POST['log_id'];
    $action = trim($_POST['action']);
    $description = trim($_POST['description']);
    $stmt = $pdo->prepare("UPDATE system_logs SET action=?, description=? WHERE id=?");
    $stmt->execute([$action, $description, $log_id]);
    adugna_log_system_action($pdo, 'Update Log', "Updated log ID $log_id", $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
    header("Location: system_logs.php?msg=updated");
    exit;
}
if (isset($_POST['add_log'])) {
    $action = trim($_POST['action']);
    $description = trim($_POST['description']);
    $user_id = $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
    $username = $_POST['username'] ?? ($_SESSION['username'] ?? null);
    adugna_log_system_action($pdo, $action, $description, $user_id, $username);
    header("Location: system_logs.php?msg=added");
    exit;
}

// Bulk delete (clear all logs)
if (isset($_POST['clear_all_logs'])) {
    $pdo->exec("TRUNCATE TABLE system_logs");
    adugna_log_system_action($pdo, 'Clear All Logs', 'All system logs cleared', $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
    header("Location: system_logs.php?msg=cleared");
    exit;
}

// REMOVE or COMMENT OUT this block to avoid logging visits to this page
// if (isset($_SESSION['user_id'])) {
//     adugna_log_system_action(
//         $pdo,
//         'View System Logs',
//         'Admin viewed the system logs page.',
//         $_SESSION['user_id'],
//         $_SESSION['username'] ?? null
//     );
// }

$logs = [];
$error = '';
if ($tableExists) {
    try {
        // Fetch logs from system_logs table (no username column in your schema)
        $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 100");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to fetch system logs: " . $e->getMessage();
    }
} else {
    $error = "System logs table could not be created. Please check your database permissions.";
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
        Purpose: System Logs page, responsive, interactive, adugna- prefix for all custom styles.
    -->
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            background: #f5f7fa;
        }
        .adugna-main {
            flex: 1;
            margin-left: 220px;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            transition: margin-left 0.3s;
        }
        @media (max-width: 900px) {
            .adugna-main {
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
        /* Adugna Gizaw: ERPNext-inspired, compact, responsive, adugna- prefix */
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.2rem;
            max-width: 900px;
            border: 1px solid #e5e7eb;
            margin-left: auto;
            margin-right: auto;
        }
        .adugna-card h2 {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 0.9rem;
            font-size: 1.1rem;
            letter-spacing: 0.01em;
        }
        .adugna-table-container {
            overflow-x: auto;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.93rem;
        }
        .adugna-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2563eb;
        }
        .adugna-table tr:hover {
            background: #f4f8fb;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.28rem 0.8rem;
            font-size: 0.93em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 0.7rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
        }
        .adugna-btn i {
            font-size: 0.93em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        @media (max-width: 900px) {
            .adugna-main { margin-left: 70px; padding: 0.7rem 0.3rem; }
            .adugna-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-main { margin-left: 0; padding: 0.3rem; }
            .adugna-card { padding: 0.4rem; }
            .adugna-card h2 { font-size: 1em; }
            .adugna-table th, .adugna-table td { font-size: 0.85em; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="adugna-main">
        <header class="adugna-admin-header">
            <h1 style="color:#2563eb;font-weight:700;"><i class="fas fa-file-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
        </header>
        <div class="adugna-card">
            <h2><i class="fas fa-database"></i> System Logs</h2>
            <?php if ($error): ?>
                <div class="adugna-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <?php
                $msg = $_GET['msg'];
                $alert = '';
                if ($msg === 'deleted') {
                    $alert = '<div class="adugna-alert-success" style="background:#e2efda;color:#215967;border:1px solid #b7e4c7;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-check-circle"></i> Log deleted successfully.</div>';
                } elseif ($msg === 'updated') {
                    $alert = '<div class="adugna-alert-success" style="background:#e2efda;color:#215967;border:1px solid #b7e4c7;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-check-circle"></i> Log updated successfully.</div>';
                } elseif ($msg === 'added') {
                    $alert = '<div class="adugna-alert-success" style="background:#e2efda;color:#215967;border:1px solid #b7e4c7;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-check-circle"></i> Log added successfully.</div>';
                } elseif ($msg === 'cleared') {
                    $alert = '<div class="adugna-alert-success" style="background:#e2efda;color:#215967;border:1px solid #b7e4c7;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-check-circle"></i> All logs cleared.</div>';
                } elseif ($msg === 'delete_failed') {
                    $alert = '<div class="adugna-alert-error" style="background:#ffeaea;color:#e74c3c;border:1px solid #f5c6cb;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-times-circle"></i> Failed to delete log.</div>';
                } elseif ($msg === 'invalid') {
                    $alert = '<div class="adugna-alert-error" style="background:#ffeaea;color:#e74c3c;border:1px solid #f5c6cb;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-exclamation-circle"></i> Invalid log ID.</div>';
                }
                echo $alert;
                ?>
            <?php endif; ?>

            <!-- Clear All Logs Button -->
            <form method="post" style="margin-bottom:1em;">
                <button type="submit" name="clear_all_logs" class="adugna-btn" style="background:#e74c3c;" onclick="return confirm('Are you sure you want to clear ALL system logs?');">
                    <i class="fa fa-trash"></i> Clear All Logs
                </button>
            </form>

            <!-- Add Log Form (Admin only) -->
            <form method="post" style="margin-bottom:1em;display:flex;gap:0.5em;flex-wrap:wrap;">
                <input type="hidden" name="add_log" value="1">
                <input type="text" name="action" placeholder="Action" required class="adugna-input" style="min-width:120px;">
                <input type="text" name="description" placeholder="Description" class="adugna-input" style="min-width:180px;">
                <input type="number" name="user_id" placeholder="User ID (optional)" class="adugna-input" style="width:90px;">
                <input type="text" name="username" placeholder="Username (optional)" class="adugna-input" style="width:120px;">
                <button type="submit" class="adugna-btn"><i class="fa fa-plus"></i> Add Log</button>
            </form>

            <?php if (!empty($logs)): ?>
                <div class="adugna-table-container">
                    <table class="adugna-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Action (with Username)</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <form method="post" style="display:contents;">
                                        <td><?= htmlspecialchars($log['id']) ?></td>
                                        <td><?= htmlspecialchars($log['user_id']) ?></td>
                                        <td>
                                            <input type="text" name="action" value="<?= htmlspecialchars($log['action']) ?>" style="width:160px;">
                                        </td>
                                        <td>
                                            <input type="text" name="description" value="<?= htmlspecialchars($log['description']) ?>" style="width:170px;">
                                        </td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        <td>
                                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                            <button type="submit" name="edit_log" class="adugna-btn" style="padding:2px 8px;"><i class="fa fa-save"></i></button>
                                            <button type="submit" name="delete_log" class="adugna-btn" style="background:#e74c3c;padding:2px 8px;" onclick="return confirm('Delete this log?');"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:#888;">No system logs found.</p>
            <?php endif; ?>
        </div>
        <div class="adugna-card" style="font-size:0.95em;color:#888;">
            <b>How to log all activities?</b><br>
            <code>adugna_log_system_action($pdo, $action, $description, $user_id);</code><br>
            Call this function in <b>every</b> PHP file where you want to log an action (login, logout, create, update, delete, view, etc).<br>
            For frontend (JS) actions, use AJAX to call a PHP endpoint that calls this function.
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <!-- Move ob_end_flush() here if you use output buffering -->
    <?php if (function_exists('ob_end_flush')) { ob_end_flush(); } ?>
    <script>
    /**
     * Adugna Gizaw: Sidebar toggle, submenu logic, active page highlight, and responsive sidebar.
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
    })();
    </script>
</body>
</html>
