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
    <link rel="stylesheet" href="../assets/css/add_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 2.2rem 2vw 2vw 2vw;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            letter-spacing: 0.01em;
        }
        .adugna-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .adugna-header-bar h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #215967;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.4em;
            padding: 0.13rem 0.7rem;
            font-size: 0.92em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.92em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 0.98em;
        }
        .adugna-table th, .adugna-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-table td:last-child, .adugna-table th:last-child { text-align: right; }
        .adugna-user-actions a {
            margin-right: 7px;
            color: #2563eb;
            text-decoration: none;
            font-size: 1em;
        }
        .adugna-user-actions a:last-child { margin-right: 0; }
        .adugna-search-bar {
            font-size: 0.97em;
            padding: 0.5em 1em;
            border-radius: 0.4em;
            border: 1.2px solid #e5e7eb;
            background: #f9fafb;
            margin-bottom: 1.2rem;
            width: 100%;
            max-width: 320px;
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 7px 4px; font-size: 0.93em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
            .adugna-header-bar { flex-direction: column; gap: 0.7rem; align-items: flex-start; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="admin-header" style="width:100%;max-width:1100px;margin:0 auto 1.5rem auto;">
                <h1 style="color:#215967;font-weight:700;font-size:clamp(1.3rem,2.5vw,2rem);text-align:center;">Manage Users</h1>
            </header>
            <div class="adugna-card">
                <div class="adugna-header-bar">
                    <h2>User List</h2>
                    <a href="add_users.php" class="adugna-btn"><i class="fas fa-user-plus"></i> Add User</a>
                </div>
                <input type="text" id="adugnaUserSearch" class="adugna-search-bar" placeholder="Search users...">
                <div class="adugna-table-responsive">
                    <table class="adugna-table" id="adugnaUsersTable">
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
                                        <td class="adugna-user-actions">
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
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Adugna Gizaw: Real-time search for users table (content/screen-size aware)
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('adugnaUserSearch');
            var table = document.getElementById('adugnaUsersTable');
            if (searchInput && table) {
                searchInput.addEventListener('input', function() {
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