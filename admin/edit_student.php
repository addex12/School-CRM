<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$student = null;

// Fetch classes for dropdown
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        header('Location: students.php');
        exit;
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $student_id = intval($_POST['student_id']);
    $address = $_POST['address'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $status = $_POST['status'] ?? null;

    // Update students table
    $stmt = $pdo->prepare("UPDATE students SET address=?, date_of_birth=?, gender=?, class_id=?, status=? WHERE id=?");
    $stmt->execute([
        $address,
        $date_of_birth,
        $gender,
        $class_id,
        $status,
        $student_id
    ]);
    $message = "Student details updated successfully!";
    // Refresh student data
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function
function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Student</h1>
            </header>
            <div class="content">
                <?php if ($message): ?>
                    <p class="success"><?= esc($message) ?></p>
                <?php endif; ?>
                <?php if ($student): ?>
                <div class="dashboard-section">
                    <form method="post">
                        <input type="hidden" name="student_id" value="<?= esc($student['id']) ?>">
                        <label>Username:</label>
                        <input type="text" value="<?= esc($student['username']) ?>" disabled>
                        <label>Email:</label>
                        <input type="text" value="<?= esc($student['email']) ?>" disabled>
                        <label>Address:</label>
                        <input type="text" name="address" value="<?= esc($student['address'] ?? '') ?>">
                        <label>Date of Birth:</label>
                        <input type="date" name="date_of_birth" value="<?= esc($student['date_of_birth'] ?? '') ?>">
                        <label>Gender:</label>
                        <select name="gender">
                            <option value="">Select</option>
                            <option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($student['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <label>Class:</label>
                        <select name="class_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= esc($class['id']) ?>" <?= ($student['class_id'] ?? null) == $class['id'] ? 'selected' : '' ?>>
                                    <?= esc($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Status:</label>
                        <input type="text" name="status" value="<?= esc($student['status'] ?? '') ?>">
                        <button type="submit" name="update_student" class="btn">Update</button>
                        <a href="students.php" class="btn">Cancel</a>
                    </form>
                </div>
                <?php else: ?>
                    <div class="dashboard-section">
                        <p>No student selected or found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
