<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['dashboard_path'] ?? '/default-dashboard.php'));
    exit();
}

// Load UI configuration
$ui_config = json_decode(file_get_contents(__DIR__ . '/assets/js/ui.json'), true);
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.dashboard_path 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = :id OR u.email = :id)
            AND u.active = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['dashboard_path'] = $user['dashboard_path'] ?? '/default-dashboard.php';

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            // Redirect to dashboard
            header("Location: " . $_SESSION['dashboard_path']);
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'System error. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($ui_config['site_name'] ?? 'School CRM') ?></title>
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <form method="POST" class="login-form">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <label for="username">Username/Email</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>