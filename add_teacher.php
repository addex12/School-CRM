<?php
require_once '../includes/db.php';
require_once '../includes/config.php';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Should be hashed in production

    // Check if user exists
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
    $check_stmt->execute([$email, $username]);
    if ($check_stmt->fetchColumn() > 0) {
        $message = "A user with this email or username already exists.";
    } else {
        // Insert into teachers table
        $teacher_stmt = $db->prepare("INSERT INTO teachers (name, email, username) VALUES (?, ?, ?)");
        $teacher_stmt->execute([$name, $email, $username]);

        // Insert into users table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($user_stmt->execute([$username, $email, $hashed_password, 'teacher'])) {
            $message = "Teacher and user account created successfully!";
        } else {
            $message = "Failed to create user account.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Teacher</title>
</head>
<body>
    <h2>Add Teacher</h2>
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
        <button type="submit">Add Teacher</button>
    </form>
</body>
</html>
