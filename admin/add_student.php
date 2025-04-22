<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Student";

// Fetch users with Student role who are not yet in students table
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN students s ON s.user_id = u.id
    WHERE LOWER(r.role_name) = 'student' AND s.id IS NULL
    ORDER BY u.username
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');

    if (!$user_id || !$enrollment_no) {
        $error = "All fields are required.";
    } else {
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $check->execute([$user_id]);
        if ($check->fetch()) {
            $error = "Student already enrolled.";
        } else {
            $insert = $pdo->prepare("INSERT INTO students (user_id, enrollment_no) VALUES (?, ?)");
            if ($insert->execute([$user_id, $enrollment_no])) {
                header("Location: students.php?msg=Student+added+successfully");
                exit;
            } else {
                $error = "Failed to add student.";
            }
        }
    }
}

// If coming from students.php with user_id param, pre-select user
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
        <?php include 'includes/admin_sidebar.php'; ?>
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
                            <label for="user_id">Select Student User</label>
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
                            <label for="enrollment_no">Enrollment No</label>
                            <input type="text" name="enrollment_no" id="enrollment_no" required>
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Add Student</button>
                        <a href="students.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
