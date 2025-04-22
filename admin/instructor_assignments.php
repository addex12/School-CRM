<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Instructor Assignments";

// Fetch teachers, courses, batches for dropdowns
$teachers = $pdo->query("SELECT t.id, u.username FROM teachers t LEFT JOIN users u ON t.user_id = u.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT c.id, s.subject_name FROM courses c LEFT JOIN subjects s ON c.subject_id = s.id ORDER BY s.subject_name")->fetchAll(PDO::FETCH_ASSOC);
$batches = $pdo->query("SELECT id, name FROM batches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $teacher_id = $_POST['teacher_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    if ($teacher_id && $course_id) {
        $stmt = $pdo->prepare("INSERT INTO instructor_assignments (teacher_id, course_id, batch_id) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_id, $course_id, $batch_id ?: null]);
        $success = "Assignment added!";
    } else {
        $error = "Teacher and course required.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM instructor_assignments WHERE id=?")->execute([intval($_GET['delete_id'])]);
    $success = "Assignment deleted!";
}

// Fetch all assignments
$stmt = $pdo->query("
    SELECT ia.id, u.username AS teacher, s.subject_name AS course, b.name AS batch
    FROM instructor_assignments ia
    LEFT JOIN teachers t ON ia.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN courses c ON ia.course_id = c.id
    LEFT JOIN subjects s ON c.subject_id = s.id
    LEFT JOIN batches b ON ia.batch_id = b.id
    ORDER BY u.username, s.subject_name
");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
</head>
<body>
<div class="admin-dashboard">
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-header">
            <h1><?= esc($pageTitle) ?></h1>
        </header>
        <div class="content">
            <div class="dashboard-section" style="max-width:600px;">
                <h2>Add Assignment</h2>
                <?php if ($error): ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success"><?= esc($success) ?></div><?php endif; ?>
                <form method="post">
                    <label>Teacher:
                        <select name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= esc($t['id']) ?>"><?= esc($t['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Course:
                        <select name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= esc($c['id']) ?>"><?= esc($c['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Batch (optional):
                        <select name="batch_id">
                            <option value="">-- None --</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= esc($b['id']) ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" name="add_assignment" class="btn">Add Assignment</button>
                </form>
            </div>
            <div class="dashboard-section">
                <h2>All Assignments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>Batch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><?= esc($a['id']) ?></td>
                                <td><?= esc($a['teacher']) ?></td>
                                <td><?= esc($a['course']) ?></td>
                                <td><?= esc($a['batch']) ?></td>
                                <td>
                                    <a href="?delete_id=<?= esc($a['id']) ?>" onclick="return confirm('Delete this assignment?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="5">No assignments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</div>
</body>
</html>
