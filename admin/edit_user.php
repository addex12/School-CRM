<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found!";
    header("Location: users.php");
    exit();
}

// Fetch all roles for the form select
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];

    // Check if username or email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['error'] = "Username or email already exists!";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role_id, $id]);
        $_SESSION['success'] = "User updated successfully!";
        header("Location: users.php");
        exit();
    }
}
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
                    <input type="hidden" name="update_user">
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
