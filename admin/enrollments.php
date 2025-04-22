<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Student Enrollments";

// Fetch students and batches for dropdowns
$students = $pdo->query("SELECT s.id, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$batches = $pdo->query("SELECT b.id, b.name FROM batches b ORDER BY b.name")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_enrollment'])) {
    $student_id = $_POST['student_id'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    if ($student_id && $batch_id) {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?, ?)");
        $stmt->execute([$student_id, $batch_id]);
        $success = "Enrollment added!";
    } else {
        $error = "All fields required.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM enrollments WHERE id=?")->execute([intval($_GET['delete_id'])]);
    $success = "Enrollment deleted!";
}

// AJAX: Fetch batches for a selected program (for dynamic dropdowns)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'batches' && isset($_GET['program_id'])) {
    $program_id = intval($_GET['program_id']);
    $stmt = $pdo->prepare("SELECT id, name FROM batches WHERE program_id = ? ORDER BY name");
    $stmt->execute([$program_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Fetch all enrollments
$stmt = $pdo->query("
    SELECT e.id, u.username AS student, b.name AS batch, e.enrolled_on
    FROM enrollments e
    LEFT JOIN students s ON e.student_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN batches b ON e.batch_id = b.id
    ORDER BY e.enrolled_on DESC
");
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <script>
    // AJAX: Update batches dropdown when program is selected
    function updateBatches(programId) {
        var batchSelect = document.getElementById('batch_id');
        batchSelect.innerHTML = '<option value="">Loading...</option>';
        fetch('enrollments.php?ajax=batches&program_id=' + programId)
            .then(response => response.json())
            .then(data => {
                batchSelect.innerHTML = '<option value="">Select Batch</option>';
                data.forEach(function(batch) {
                    var opt = document.createElement('option');
                    opt.value = batch.id;
                    opt.text = batch.name;
                    batchSelect.appendChild(opt);
                });
            });
    }
    </script>
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
                <h2>Add Enrollment</h2>
                <?php if ($error): ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success"><?= esc($success) ?></div><?php endif; ?>
                <form method="post">
                    <label>Student:
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= esc($s['id']) ?>"><?= esc($s['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Program:
                        <select name="program_id" id="program_id" onchange="updateBatches(this.value)" required>
                            <option value="">Select Program</option>
                            <?php
                            $programs = $pdo->query("SELECT id, name FROM programs ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($programs as $p): ?>
                                <option value="<?= esc($p['id']) ?>"><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Batch:
                        <select name="batch_id" id="batch_id" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= esc($b['id']) ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" name="add_enrollment" class="btn">Add Enrollment</button>
                </form>
            </div>
            <div class="dashboard-section">
                <h2>All Enrollments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Batch</th>
                            <th>Enrolled On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td><?= esc($e['id']) ?></td>
                                <td><?= esc($e['student']) ?></td>
                                <td><?= esc($e['batch']) ?></td>
                                <td><?= esc($e['enrolled_on']) ?></td>
                                <td>
                                    <a href="?delete_id=<?= esc($e['id']) ?>" onclick="return confirm('Delete this enrollment?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($enrollments)): ?>
                            <tr><td colspan="5">No enrollments found.</td></tr>
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
