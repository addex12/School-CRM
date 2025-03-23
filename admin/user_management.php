<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

// Handle user management actions (create, read, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...handle form submissions for user management...
}

$users = $conn->query("SELECT * FROM users")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
</head>
<body>
    <h1>User Management</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= $user['username'] ?></td>
            <td><?= $user['role'] ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <select name="role">
                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                        <option value="parent" <?= $user['role'] === 'parent' ? 'selected' : '' ?>>Parent</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <button type="submit">Update Role</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
