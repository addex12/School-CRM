<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['dashboard']);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <?php if (isset($_GET['error'])): ?>
        <div style="color: red">Invalid username or password</div>
    <?php endif; ?>
    
    <form method="POST" action="auth.php">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>