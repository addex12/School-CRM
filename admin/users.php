<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Users";

// Fetch all users with roles
$stmt = $pdo->query("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mobile-friendly adjustments */
        .admin-dashboard {
            display: flex;
            flex-direction: column;
        }
        .admin-main {
            padding: 1rem;
        }
        .users-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .users-header h2 {
            font-size: 1.5rem;
            margin: 0;
        }
        .users-header .erpnext-btn {
            align-self: flex-start;
        }
        .erpnext-search-bar {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.5rem;
            font-size: 1rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .users-table th, .users-table td {
            padding: 0.5rem;
            text-align: left;
        }
        .users-table th {
            background-color: #f8f9fa;
        }
        .user-actions a {
            margin-right: 0.5rem;
        }
        @media (max-width: 768px) {
            .users-table th, .users-table td {
                font-size: 0.8rem;
                padding: 0.3rem;
            }
            .users-header {
                flex-direction: column;
                align-items: flex-start;
            }
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
            <div class="content">
                <div class="erpnext-card">
                    <div class="users-header">
                        <h2>User List</h2>
                        <a href="add_users.php" class="erpnext-btn erpnext-btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
                    </div>
                    <input type="text" id="userSearch" class="erpnext-search-bar" placeholder="Search users...">
                    <div class="table-responsive">
                        <table class="users-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['id']) ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <?php if (isset($user['active']) && $user['active'] == 1): ?>
                                                    <span style="color:#27ae60;font-weight:500;">Active</span>
                                                <?php else: ?>
                                                    <span style="color:#e74c3c;font-weight:500;">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="user-actions">
                                                <a href="edit_user.php?id=<?= $user['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
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
    <?php include 'includes/footer.php'; ?>
    <script>
        // Real-time search for users table
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('userSearch');
            var table = document.getElementById('usersTable');
            if (searchInput && table) {
                searchInput.addEventListener('keyup', function() {
                    var filter = searchInput.value.toLowerCase();
                    var rows = table.querySelectorAll('tbody tr');
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>