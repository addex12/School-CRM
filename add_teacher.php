<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */

require_once '../includes/db.php';
require_once '../includes/config.php';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch teacher role id from role table
$role_stmt = $db->prepare("SELECT id FROM role WHERE name = ?");
$role_stmt->execute(['teacher']);
$teacher_role_id = $role_stmt->fetchColumn();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check if user exists
    $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check_stmt->execute([$email, $username]);
    $user_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_exists) {
        $message = "A user with this email or username already exists.";
    } else {
        // Insert into users table with teacher role
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($user_stmt->execute([$username, $email, $hashed_password, $teacher_role_id])) {
            // Insert into teachers table
            $teacher_stmt = $db->prepare("INSERT INTO teachers (name, email, username) VALUES (?, ?, ?)");
            $teacher_stmt->execute([$name, $email, $username]);
            $message = "Teacher and user account created successfully!";
        } else {
            $message = "Failed to create user account.";
        }
    }
}

// Fetch all users for display (refresh after possible insert)
$users = $db->query("SELECT id, username, email, role FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Teacher</title>
</head>
<body>
    <h2>Register New Teacher</h2>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" required>
        <br><br>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br><br>
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br><br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br><br>
        <button type="submit">Register Teacher</button>
    </form>

    <h3>Existing Users</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role ID</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
