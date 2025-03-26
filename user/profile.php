<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle normal form submission
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$name, $email, $userId])) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $error = "Failed to update profile";
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

include __DIR__ . '/includes/header.php';
?>

<h1>Manage Your Profile</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<form method="POST">
    <label for="name">Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
    
    <label for="email">Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
    
    <button type="submit">Update Profile</button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>