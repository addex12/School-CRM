<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$user_id = $_GET['id'] ?? null;
$pageTitle = "Edit User";
// Fetch user details
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: users.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: users.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role_id'];

    // Check for duplicate username or email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $user_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['error'] = "Username or email already exists.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role_id, $id]);
        $_SESSION['success'] = "User updated successfully.";
        header("Location: users.php");
        exit();
    }
}
$roles = $pdo->query("SELECT role_name FROM roles ORDER BY role_name")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit User</h1>
            </header>
            <div class="content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
    <label for="role">Role:</label>
    <select name="role_id" required>
    <?php foreach ($roles as $role): ?>
    <option value="<?= $role['id'] ?>" 
        <?= $user['role_id'] == $role['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($role['role_name']) ?>
    </option>
    <?php endforeach; ?>
</select>
</div>
                    <div class="form-group"></div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</body>
</html>
