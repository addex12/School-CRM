<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
* All custom styles use adugna- prefix for patenting.
* Sidebar toggle, submenu logic, compact ERPNext-inspired cards/buttons, and active page highlight.
* Outstanding, consistent, and visually interactive sidebar for all admin pages.
*/
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "Users";

// Fetch all users with roles
$stmt = $pdo->query("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        /* Adugna Gizaw: Compact ERPNext-inspired grid and card styles with adugna- prefix */
        .adugna-form-grid {
            flex: 1 1 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin: 0 auto;
        }
        .adugna-card {
            width: 95%;
            max-width: 900px;
            padding: 1.2rem 1.2rem 1.2rem 1.2rem;
            box-shadow: 0 1px 4px rgba(52,152,219,0.04);
            background: #fff;
            border-radius: 7px;
            margin: 0 auto 1.2rem auto;
            border: 1px solid #e3e6eb;
        }
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.1rem;
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
        .adugna-btn:hover {
            background: #217dbb;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.93rem;
        }
        .users-table th, .users-table td {
            padding: 0.5rem 0.7rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .users-table th {
            background: #f7fafd;
            color: #3498db;
            font-weight: 600;
        }
        .user-actions a {
            margin-right: 0.4rem;
            color: #3498db;
            font-size: 1em;
        }
        .user-actions a:hover {
            color: #217dbb;
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 0.7rem; }
            .users-table th, .users-table td { font-size: 0.85rem; padding: 0.3rem; }
        }
        @media (max-width: 600px) {
            .adugna-card { padding: 0.4rem; }
            .users-table th, .users-table td { font-size: 0.75rem; padding: 0.2rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="admin-content">
                <div class="adugna-form-grid">
                    <div class="adugna-card">
                        <div class="table-responsive">
                            <table class="users-table" id="usersTable">
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="user-actions">
                                                    <a href="delete_user.php?id=<?= $user['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fas fa-trash-alt"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7">No users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
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
                const submenu = link.nextElementSibling;
                const toggleIcon = link.querySelector('.adugna-submenu-toggle');
                if (submenu && submenu.classList.contains('adugna-submenu')) {
                    submenu.classList.toggle('adugna-open');
                    if (toggleIcon) toggleIcon.classList.toggle('adugna-rotated');
                }
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