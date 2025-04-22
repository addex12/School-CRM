<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Grading Scales";

// Fetch curriculums for dropdown
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle add grading scale
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_scale'])) {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $scale_name = trim($_POST['scale_name'] ?? '');
    $min_score = $_POST['min_score'] ?? '';
    $max_score = $_POST['max_score'] ?? '';
    $grade_letter = trim($_POST['grade_letter'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    if ($curriculum_id && $scale_name && $min_score !== '' && $max_score !== '' && $grade_letter) {
        $stmt = $pdo->prepare("INSERT INTO grading_scales (curriculum_id, scale_name, min_score, max_score, grade_letter, remark) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$curriculum_id, $scale_name, $min_score, $max_score, $grade_letter, $remark]);
        header("Location: grading_scales.php?msg=Scale+added");
        exit;
    } else {
        $error = "All fields except remark are required.";
    }
}

// Fetch all grading scales
$stmt = $pdo->query("
    SELECT gs.id, cu.name AS curriculum, gs.scale_name, gs.min_score, gs.max_score, gs.grade_letter, gs.remark, gs.created_at
    FROM grading_scales gs
    LEFT JOIN curriculums cu ON gs.curriculum_id = cu.id
    ORDER BY cu.name, gs.scale_name, gs.min_score DESC
");
$scales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grading Scales - Admin Panel</title>
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
                <div class="dashboard-section" style="max-width:900px;">
                    <h2>Add Grading Scale</h2>
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
                            <label for="scale_name">Scale Name</label>
                            <input type="text" name="scale_name" id="scale_name" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="min_score">Min Score</label>
                            <input type="number" step="0.01" name="min_score" id="min_score" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="max_score">Max Score</label>
                            <input type="number" step="0.01" name="max_score" id="max_score" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="grade_letter">Grade Letter</label>
                            <input type="text" name="grade_letter" id="grade_letter" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="remark">Remark</label>
                            <input type="text" name="remark" id="remark">
                        </div>
                        <button type="submit" name="add_scale" class="btn" style="background:#3498db;color:#fff;">Add Scale</button>
                    </form>
                    <h2>Grading Scales List</h2>
                    <div class="table-responsive">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Curriculum</th>
                                    <th>Scale Name</th>
                                    <th>Min Score</th>
                                    <th>Max Score</th>
                                    <th>Grade Letter</th>
                                    <th>Remark</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($scales)): ?>
                                    <?php foreach ($scales as $scale): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($scale['id']) ?></td>
                                            <td><?= htmlspecialchars($scale['curriculum'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($scale['scale_name']) ?></td>
                                            <td><?= htmlspecialchars($scale['min_score']) ?></td>
                                            <td><?= htmlspecialchars($scale['max_score']) ?></td>
                                            <td><?= htmlspecialchars($scale['grade_letter']) ?></td>
                                            <td><?= htmlspecialchars($scale['remark']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($scale['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No grading scales found.</td>
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
