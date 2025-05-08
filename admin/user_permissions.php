<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$pageTitle = "User Permissions";

// Fetch users and their roles
$users = [];
$sql = "SELECT users.id, users.username, roles.id AS role_id, roles.role_name AS role_name
        FROM users
        LEFT JOIN roles ON users.role_id = roles.id";
$result = $pdo->query($sql);
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $row['id'],
            'name' => $row['username'],
            'role_id' => $row['role_id'],
            'role' => $row['role_name']
        ];
    }
}

// Fetch all roles
$roles = [];
$res = $pdo->query("SELECT id, role_name FROM roles");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $roles[] = $row;
    }
}

// Fetch permissions for each role
$role_permissions = [];
$res = $pdo->query("SELECT role_id, permissions.label FROM role_permissions JOIN permissions ON role_permissions.permission_id = permissions.id");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $role_permissions[$row['role_id']][] = $row['label'];
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $user_id = intval($_POST['user_id']);
    $role_id = intval($_POST['role_id']);
    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$role_id, $user_id]);
    header("Location: user_permissions.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
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
            max-width: 900px;
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
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 1em;
        }
        .adugna-table th, .adugna-table td {
            padding: 12px 10px;
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
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.18rem 1.1rem;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.95em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        select.adugna-select {
            border-radius: 0.4em;
            border: 1.2px solid #e5e7eb;
            background: #f3f4f6;
            padding: 0.35em 0.8em;
            font-size: 0.97em;
            margin-right: 0.5em;
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 8px 4px; font-size: 0.97em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
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
            <header class="admin-header" style="width:100%;max-width:900px;margin:0 auto 1.5rem auto;">
                <h1 style="color:#215967;font-weight:700;font-size:clamp(1.3rem,2.5vw,2rem);text-align:center;">User Permissions</h1>
            </header>
            <div class="adugna-card">
                <h2>User Roles & Permissions</h2>
                <div class="adugna-table-responsive">
                    <table class="adugna-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th>Change Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['role'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $perms = $role_permissions[$user['role_id']] ?? [];
                                    echo $perms ? implode(', ', array_map('htmlspecialchars', $perms)) : '<span style="color:#aaa;">No permissions</span>';
                                    ?>
                                </td>
                                <td>
                                    <form method="post" action="" style="display:flex;align-items:center;gap:0.5em;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="role_id" class="adugna-select">
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= $role['id'] ?>"<?= $role['id'] == $user['role_id'] ? ' selected' : '' ?>>
                                                    <?= htmlspecialchars($role['role_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
