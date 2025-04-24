<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Teacher";

// Fetch users with Teacher role who are not yet in teachers table
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN teachers t ON t.user_id = u.id
    WHERE LOWER(r.role_name) = 'teacher' AND t.id IS NULL
    ORDER BY u.username
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available classes
$class_stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $class_id = $_POST['class_id'] ?? null;

    if (!$user_id) {
        $error = "User selection is required.";
    } else {
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $check->execute([$user_id]);
        if ($check->fetch()) {
            $error = "Teacher already exists.";
        } else {
            $insert = $pdo->prepare("INSERT INTO teachers (user_id, class_id) VALUES (?, ?)");
            if ($insert->execute([$user_id, $class_id ?: null])) {
                header("Location: teachers.php?msg=Teacher+added+successfully");
                exit;
            } else {
                $error = "Failed to add teacher.";
            }
        }
    }
}

// If coming from teachers.php with user_id param, pre-select user
$preselect_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section" style="max-width:500px;">
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div style="margin-bottom:1rem;">
                            <label for="user_id">Select Teacher User</label>
                            <select name="user_id" id="user_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($preselect_user_id == $user['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="class_id">Assign to Class (optional)</label>
                            <select name="class_id" id="class_id">
                                <option value="">-- None --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Add Teacher</button>
                        <a href="teachers.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
