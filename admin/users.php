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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/erpnext.css">
    <link rel="stylesheet" href="../assets/css/erpnext_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .erpnext-form-grid {
            flex: 1 1 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin: 0 auto;
        }

        .erpnext-card {
            width: 90%;
            max-width: 800px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background: white;
            border-radius: 8px;
            margin: 0 auto;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .users-table th,
        .users-table td {
            padding: 0.4rem;
            text-align: left;
        }

        .users-table th {
            background-color: #f8f9fa;
        }

        .user-actions a {
            margin-right: 0.4rem;
        }

        @media (max-width: 768px) {
            .erpnext-card {
                width: 100%;
            }

            .users-table th,
            .users-table td {
                font-size: 0.75rem;
                padding: 0.3rem;
            }
        }

        @media (max-width: 480px) {
            .erpnext-card {
                width: 95%;
            }

            .users-table th,
            .users-table td {
                font-size: 0.7rem;
                padding: 0.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <?= htmlspecialchars($pageTitle) ?>
            </header>

            <div class="admin-content">
                <div class="erpnext-form-grid">
                    <div class="erpnext-card">
                        <div class="users-header">
                            <h2>User List</h2>
                            <a href="add_users.php" class="erpnext-btn erpnext-btn-primary">
                                <i class="fas fa-user-plus"></i> Add User
                            </a>
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
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
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