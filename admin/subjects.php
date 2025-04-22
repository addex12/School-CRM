<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Subjects";

// Fetch curriculums and class levels for dropdowns
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$class_levels = $pdo->query("SELECT id, curriculum_id, level_name FROM class_levels ORDER BY curriculum_id, level_order, level_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle add subject
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $class_level_id = $_POST['class_level_id'] ?? null;
    $subject_name = trim($_POST['subject_name'] ?? '');
    if ($curriculum_id && $subject_name) {
        $stmt = $pdo->prepare("INSERT INTO subjects (curriculum_id, class_level_id, subject_name) VALUES (?, ?, ?)");
        $stmt->execute([$curriculum_id, $class_level_id ?: null, $subject_name]);
        header("Location: subjects.php?msg=Subject+added");
        exit;
    } else {
        $error = "Curriculum and subject name are required.";
    }
}

// Fetch all subjects with curriculum and class level
$stmt = $pdo->query("
    SELECT s.id, s.subject_name, cu.name AS curriculum, lv.level_name, s.created_at
    FROM subjects s
    LEFT JOIN curriculums cu ON s.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON s.class_level_id = lv.id
    ORDER BY cu.name, lv.level_name, s.subject_name
");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subjects - Admin Panel</title>
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
                <div class="dashboard-section" style="max-width:700px;">
                    <h2>Add Subject</h2>
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" style="margin-bottom:2rem;">
                        <div style="margin-bottom:1rem;">
                            <label for="curriculum_id">Curriculum</label>
                            <select name="curriculum_id" id="curriculum_id" required>
                                <option value="">-- Select Curriculum --</option>
                                <?php foreach ($curriculums as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="class_level_id">Class Level (optional)</label>
                            <select name="class_level_id" id="class_level_id">
                                <option value="">-- Any Level --</option>
                                <?php foreach ($class_levels as $l): ?>
                                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['level_name']) ?> (<?= htmlspecialchars($curriculums[array_search($l['curriculum_id'], array_column($curriculums, 'id'))]['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="subject_name">Subject Name</label>
                            <input type="text" name="subject_name" id="subject_name" required>
                        </div>
                        <button type="submit" name="add_subject" class="btn" style="background:#3498db;color:#fff;">Add Subject</button>
                    </form>
                    <h2>Subject List</h2>
                    <div class="table-responsive">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Curriculum</th>
                                    <th>Class Level</th>
                                    <th>Subject Name</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($subject['id']) ?></td>
                                            <td><?= htmlspecialchars($subject['curriculum'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($subject['level_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($subject['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No subjects found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
