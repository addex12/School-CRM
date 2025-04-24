<?php
require_once '../includes/db_connect.php'; // adjust path if needed

// Fetch users and their roles
$users = [];
$sql = "SELECT users.id, users.username, roles.id AS role_id, roles.name AS role_name
        FROM users
        LEFT JOIN roles ON users.role_id = roles.id";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
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
$res = $conn->query("SELECT id, name FROM roles");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $user_id = intval($_POST['user_id']);
    $role_id = intval($_POST['role_id']);
    $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $role_id, $user_id);
    $stmt->execute();
    header("Location: user_permissions.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ERPNext-inspired card and table styling */
        body { font-family: Inter, Arial, sans-serif; background: #f5f7fa; margin: 0; }
        .container { margin-left: 270px; padding: 2rem; }
        h2 { color: #215967; margin-bottom: 1.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        }
        th, td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #f4f8fb;
            color: #215967;
            font-weight: 600;
        }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #f8fafc; }
        select {
            padding: 0.4rem 0.7rem;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 1rem;
        }
        button[type="submit"] {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.4rem 1rem;
            font-size: 0.97rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s;
            margin-left: 0.5rem;
        }
        button[type="submit"]:hover { background: #215967; }
        @media (max-width: 900px) {
            .container { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .container { margin-left: 0; padding: 0.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="container">
        <div class="erp-card">
            <h2><i class="fas fa-user-lock"></i> User Permissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role_id">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"<?= $role['id'] == $user['role_id'] ? ' selected' : '' ?>>
                                            <?= htmlspecialchars($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"><i class="fas fa-save"></i> Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
