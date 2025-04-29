<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "User List";

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
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <?= htmlspecialchars($pageTitle) ?>
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
        <?php include './includes/footer.php'; ?>
    </div>
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