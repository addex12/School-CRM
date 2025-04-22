<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Edit Teacher";

// Get teacher ID
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$teacher_id) {
    header("Location: teachers.php");
    exit;
}

// Fetch teacher info with user
$stmt = $pdo->prepare("
    SELECT t.id AS teacher_id, t.user_id, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, 
           u.username, u.email
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: teachers.php");
    exit;
}

// Handle update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    // Only update columns that exist in your teachers table
    $qualification = trim($_POST['qualification'] ?? '');
    $subject_specialization = trim($_POST['subject_specialization'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $status = trim($_POST['status'] ?? '');

    // Update teacher info
    $stmt = $pdo->prepare("UPDATE teachers SET qualification=?, subject_specialization=?, address=?, date_of_birth=?, gender=?, status=? WHERE id=?");
    $stmt->execute([$qualification, $subject_specialization, $address, $date_of_birth, $gender, $status, $teacher_id]);

    $success = "Teacher details updated successfully!";
    // Refresh teacher data
    $stmt = $pdo->prepare("
        SELECT t.id AS teacher_id, t.user_id, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, 
               u.username, u.email
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper
function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .dashboard-section { max-width: 600px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(44,62,80,0.07); padding: 2rem 1.5rem; }
        label { display: block; margin-top: 1rem; font-weight: 500; }
        input, select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-top: 4px; }
        .btn { margin-top: 1.2rem; }
        .success { color: #27ae60; margin-bottom: 1rem; }
        .error { color: #e74c3c; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= esc($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <?php if ($success): ?>
                        <div class="success"><?= esc($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="error"><?= esc($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <label>Username:
                            <input type="text" value="<?= esc($teacher['username']) ?>" disabled>
                        </label>
                        <label>Email:
                            <input type="text" value="<?= esc($teacher['email']) ?>" disabled>
                        </label>
                        <label>Qualification:
                            <input type="text" name="qualification" value="<?= esc($teacher['qualification'] ?? '') ?>">
                        </label>
                        <label>Subject Specialization:
                            <input type="text" name="subject_specialization" value="<?= esc($teacher['subject_specialization'] ?? '') ?>">
                        </label>
                        <label>Address:
                            <input type="text" name="address" value="<?= esc($teacher['address'] ?? '') ?>">
                        </label>
                        <label>Date of Birth:
                            <input type="date" name="date_of_birth" value="<?= esc($teacher['date_of_birth'] ?? '') ?>">
                        </label>
                        <label>Gender:
                            <select name="gender">
                                <option value="">Select</option>
                                <option value="Male" <?= ($teacher['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($teacher['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($teacher['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </label>
                        <label>Status:
                            <input type="text" name="status" value="<?= esc($teacher['status'] ?? '') ?>">
                        </label>
                        <button type="submit" name="update_teacher" class="btn">Update</button>
                        <a href="teachers.php" class="btn" style="background:#aaa;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
