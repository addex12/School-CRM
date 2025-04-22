<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

$pageTitle = "View Teacher";

// Enable error reporting for debugging (remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get teacher ID from URL
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$teacher_id) {
    header("Location: teachers.php?error=Invalid+teacher+ID");
    exit;
}

// Fetch teacher data
$teacher_stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.role_id, u.active
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$teacher_stmt->execute([$teacher_id]);
$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: teachers.php?error=Teacher+not+found");
    exit;
}

// Fetch subjects taught by this teacher
$teacher_subjects_stmt = $pdo->prepare("
    SELECT 
        cs.subject_id,
        cs.class_id,
        s.subject_name,
        c.class_name,
        ts.section_id,
        sec.section_name
    FROM teacher_subjects ts
    JOIN class_subjects cs ON ts.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN classes c ON cs.class_id = c.id
    LEFT JOIN sections sec ON ts.section_id = sec.id
    WHERE ts.teacher_id = ?
    ORDER BY c.class_name, sec.section_name, s.subject_name
");
$teacher_subjects_stmt->execute([$teacher_id]);
$teacher_subjects = $teacher_subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* ...existing code... */
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <p>Viewing: <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></p>
            </header>
            <div class="content">
                <div class="form-container">
                    <h3>Teacher Details</h3>
                    <table>
                        <tr><th>Full Name</th><td><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></td></tr>
                        <tr><th>Username</th><td><?= htmlspecialchars($teacher['username']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($teacher['email']) ?></td></tr>
                        <tr><th>Qualification</th><td><?= htmlspecialchars($teacher['qualification']) ?></td></tr>
                        <tr><th>Subject Specialization</th><td><?= htmlspecialchars($teacher['subject_specialization']) ?></td></tr>
                        <tr><th>Date of Birth</th><td><?= htmlspecialchars($teacher['date_of_birth']) ?></td></tr>
                        <tr><th>Gender</th><td><?= htmlspecialchars($teacher['gender']) ?></td></tr>
                        <tr><th>Address</th><td><?= htmlspecialchars($teacher['address']) ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($teacher['status']) ?></td></tr>
                        <tr><th>Created At</th><td><?= htmlspecialchars($teacher['created_at']) ?></td></tr>
                    </table>
                    <h3 style="margin-top:2rem;">Subject Assignments</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($teacher_subjects)): ?>
                            <?php foreach ($teacher_subjects as $ts): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ts['class_name']) ?></td>
                                    <td><?= htmlspecialchars($ts['section_name']) ?></td>
                                    <td><?= htmlspecialchars($ts['subject_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No subject assignments found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="margin-top:2rem;">
                        <a href="edit_teacher.php?edit_id=<?= $teacher_id ?>" class="btn btn-primary">Edit</a>
                        <a href="teachers.php" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>