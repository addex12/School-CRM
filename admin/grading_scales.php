<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Grading Scales";

// Fetch curriculums for dropdown
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_scale'])) {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $scale_name = trim($_POST['scale_name'] ?? '');
    $min_score = $_POST['min_score'] ?? '';
    $max_score = $_POST['max_score'] ?? '';
    $grade_letter = trim($_POST['grade_letter'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    if (!$curriculum_id || $scale_name === '' || $min_score === '' || $max_score === '' || $grade_letter === '') {
        $error = "All fields except remark are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO grading_scales (curriculum_id, scale_name, min_score, max_score, grade_letter, remark) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$curriculum_id, $scale_name, $min_score, $max_score, $grade_letter, $remark]);
        $success = "Grading scale added successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM grading_scales WHERE id=?")->execute([$delete_id]);
    $success = "Grading scale deleted successfully!";
}

// Handle Edit
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_scale = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_scale = $stmt->fetch(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_scale'])) {
    $scale_id = intval($_POST['scale_id']);
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $scale_name = trim($_POST['scale_name'] ?? '');
    $min_score = $_POST['min_score'] ?? '';
    $max_score = $_POST['max_score'] ?? '';
    $grade_letter = trim($_POST['grade_letter'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    if (!$curriculum_id || $scale_name === '' || $min_score === '' || $max_score === '' || $grade_letter === '') {
        $error = "All fields except remark are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE grading_scales SET curriculum_id=?, scale_name=?, min_score=?, max_score=?, grade_letter=?, remark=? WHERE id=?");
        $stmt->execute([$curriculum_id, $scale_name, $min_score, $max_score, $grade_letter, $remark, $scale_id]);
        $success = "Grading scale updated successfully!";
        $edit_id = 0;
        $edit_scale = null;
    }
}

// Fetch all grading scales
$stmt = $pdo->query("
    SELECT gs.id, gs.scale_name, gs.min_score, gs.max_score, gs.grade_letter, gs.remark, cu.name AS curriculum
    FROM grading_scales gs
    LEFT JOIN curriculums cu ON gs.curriculum_id = cu.id
    ORDER BY cu.name, gs.scale_name, gs.min_score DESC
");
$scales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grading-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .grading-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .grading-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grading-table th, .grading-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .grading-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .grading-table tr:hover {
            background: #f4f8fb;
        }
        .grading-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .grading-actions a:last-child {
            margin-right: 0;
        }
        .dashboard-section {
            max-width: 700px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .form-row > div {
            flex: 1 1 160px;
        }
        @media (max-width: 900px) {
            .dashboard-section { max-width: 98vw; padding: 1rem 0.5rem; }
            .form-row { flex-direction: column; gap: 0.5rem; }
        }
        @media (max-width: 600px) {
            .dashboard-section { padding: 0.5rem 0.2rem; }
            .grading-table th, .grading-table td { padding: 8px 6px; }
        }
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
                    <div class="grading-header">
                        <h2><?= $edit_scale ? 'Edit Grading Scale' : 'Add Grading Scale' ?></h2>
                    </div>
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= esc($error) ?></div>
                    <?php elseif ($success): ?>
                        <div style="color:#27ae60;"><?= esc($success) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <?php if ($edit_scale): ?>
                            <input type="hidden" name="scale_id" value="<?= esc($edit_scale['id']) ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div>
                                <label for="curriculum_id">Curriculum</label>
                                <select name="curriculum_id" id="curriculum_id" required>
                                    <option value="">-- Select Curriculum --</option>
                                    <?php foreach ($curriculums as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($edit_scale['curriculum_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= esc($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="scale_name">Scale Name</label>
                                <input type="text" name="scale_name" id="scale_name" value="<?= esc($edit_scale['scale_name'] ?? '') ?>" required>
                            </div>
                            <div>
                                <label for="min_score">Min Score</label>
                                <input type="number" step="0.01" name="min_score" id="min_score" value="<?= esc($edit_scale['min_score'] ?? '') ?>" required>
                            </div>
                            <div>
                                <label for="max_score">Max Score</label>
                                <input type="number" step="0.01" name="max_score" id="max_score" value="<?= esc($edit_scale['max_score'] ?? '') ?>" required>
                            </div>
                            <div>
                                <label for="grade_letter">Grade Letter</label>
                                <input type="text" name="grade_letter" id="grade_letter" value="<?= esc($edit_scale['grade_letter'] ?? '') ?>" required>
                            </div>
                            <div>
                                <label for="remark">Remark</label>
                                <input type="text" name="remark" id="remark" value="<?= esc($edit_scale['remark'] ?? '') ?>">
                            </div>
                        </div>
                        <div style="margin-top:1.2rem;">
                            <?php if ($edit_scale): ?>
                                <button type="submit" name="update_scale" class="btn">Update</button>
                                <a href="grading_scales.php" class="btn" style="background:#aaa;">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_scale" class="btn">Add Grading Scale</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="dashboard-section">
                    <div class="grading-header">
                        <h2>Grading Scales List</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="grading-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Curriculum</th>
                                    <th>Scale Name</th>
                                    <th>Min Score</th>
                                    <th>Max Score</th>
                                    <th>Grade Letter</th>
                                    <th>Remark</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($scales)): ?>
                                    <?php foreach ($scales as $scale): ?>
                                        <tr>
                                            <td><?= esc($scale['id']) ?></td>
                                            <td><?= esc($scale['curriculum'] ?? '-') ?></td>
                                            <td><?= esc($scale['scale_name']) ?></td>
                                            <td><?= esc($scale['min_score']) ?></td>
                                            <td><?= esc($scale['max_score']) ?></td>
                                            <td><?= esc($scale['grade_letter']) ?></td>
                                            <td><?= esc($scale['remark']) ?></td>
                                            <td class="grading-actions">
                                                <a href="grading_scales.php?edit_id=<?= esc($scale['id']) ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="grading_scales.php?delete_id=<?= esc($scale['id']) ?>" title="Delete" onclick="return confirm('Delete this grading scale?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
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
