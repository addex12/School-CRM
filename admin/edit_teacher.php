<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Teacher";

// Fetch users with Teacher role who are not yet in teachers table
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.first_name, u.last_name
    FROM users u
    INNER JOIN roles r ON u.role_id = r.id
    LEFT JOIN teachers t ON t.user_id = u.id
    WHERE LOWER(r.role_name) = 'teacher' AND t.id IS NULL
    ORDER BY u.first_name, u.last_name, u.username
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

$preselect_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-dashboard { display: flex; min-height: 100vh; background: #f4f6fa; }
        .admin-main { flex: 1; padding: 2rem; }
        .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        select, input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 4px; text-decoration: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .error { color: #dc3545; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div>
                            <label for="user_id">Select Teacher</label>
                            <select name="user_id" id="user_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($preselect_user_id == $user['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
                                        (<?= htmlspecialchars($user['username']) ?>, <?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="class_id">Assign to Class (optional)</label>
                            <select name="class_id" id="class_id">
                                <option value="">-- None --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Teacher</button>
                        <a href="teachers.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>