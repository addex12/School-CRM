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
        .users-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
        }
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .users-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .users-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .users-header .btn:hover {
            background: #217dbb;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .users-table tr:hover {
            background: #f4f8fb;
        }
        .user-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .user-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .users-container {
                padding: 1rem 0.5rem;
            }
            .users-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .users-table th, .users-table td {
                padding: 8px 6px;
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
                <div class="users-container">
                    <div class="users-header">
                        <h2>User List</h2>
                        <a href="add_users.php" class="btn"><i class="fas fa-user-plus"></i> Add User</a>
                    </div>
                    <div class="table-responsive">
                        <table class="users-table">
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

</body>
</html>