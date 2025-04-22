<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Subjects Management";

// Fetch curriculums and class levels for dropdowns
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$class_levels = $pdo->query("SELECT id, curriculum_id, level_name FROM class_levels ORDER BY curriculum_id, level_order, level_name")->fetchAll(PDO::FETCH_ASSOC);

// Group levels by curriculum for JS
$levelsByCurriculum = [];
foreach ($class_levels as $level) {
    $levelsByCurriculum[$level['curriculum_id']][] = $level;
}

// Handle Add/Edit/Delete
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$delete_id = isset($_GET['delete_id']) ? intval($_GET['delete_id']) : 0;
$message = '';
$error = '';

// Delete
if ($delete_id) {
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->execute([$delete_id]);
    $message = "Subject deleted successfully!";
}

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $class_level_id = $_POST['class_level_id'] ?? '';
    $subject_name = trim($_POST['subject_name'] ?? '');

    if (!$curriculum_id || !$class_level_id || !$subject_name) {
        $error = "All fields are required.";
    } else {
        // Prevent duplicate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE curriculum_id=? AND class_level_id=? AND subject_name=?");
        $stmt->execute([$curriculum_id, $class_level_id, $subject_name]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Subject already exists for this curriculum and class level.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (curriculum_id, class_level_id, subject_name) VALUES (?, ?, ?)");
            $stmt->execute([$curriculum_id, $class_level_id, $subject_name]);
            $message = "Subject added successfully!";
        }
    }
}

// Edit
$edit_subject = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_subject = $stmt->fetch(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $class_level_id = $_POST['class_level_id'] ?? '';
    $subject_name = trim($_POST['subject_name'] ?? '');

    if (!$curriculum_id || !$class_level_id || !$subject_name) {
        $error = "All fields are required.";
    } else {
        // Prevent duplicate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE curriculum_id=? AND class_level_id=? AND subject_name=? AND id!=?");
        $stmt->execute([$curriculum_id, $class_level_id, $subject_name, $subject_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Subject already exists for this curriculum and class level.";
        } else {
            $stmt = $pdo->prepare("UPDATE subjects SET curriculum_id=?, class_level_id=?, subject_name=? WHERE id=?");
            $stmt->execute([$curriculum_id, $class_level_id, $subject_name, $subject_id]);
            $message = "Subject updated successfully!";
            $edit_id = 0;
            $edit_subject = null;
        }
    }
}

// Fetch all subjects with curriculum and class level info
$stmt = $pdo->query("
    SELECT s.id, s.subject_name, cu.name AS curriculum, lv.level_name
    FROM subjects s
    LEFT JOIN curriculums cu ON s.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON s.class_level_id = lv.id
    ORDER BY cu.name, lv.level_name, s.subject_name
");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        .subjects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .subjects-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
        }
        .subjects-table th, .subjects-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .subjects-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .subjects-table tr:hover {
            background: #f4f8fb;
        }
        .subject-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .subject-actions a:last-child {
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
            .subjects-table th, .subjects-table td { padding: 8px 6px; }
        }
    </style>
    <script>
    // For curriculum-level dynamic dropdown
    const levelsByCurriculum = <?= json_encode($levelsByCurriculum) ?>;
    function updateLevelsDropdown(curriculumSelectId, levelSelectId, selectedLevelId) {
        var curriculumId = document.getElementById(curriculumSelectId).value;
        var levelSelect = document.getElementById(levelSelectId);
        levelSelect.innerHTML = '<option value="">-- Select Level --</option>';
        if (levelsByCurriculum[curriculumId]) {
            levelsByCurriculum[curriculumId].forEach(function(lv) {
                var opt = document.createElement('option');
                opt.value = lv.id;
                opt.text = lv.level_name;
                if (selectedLevelId && lv.id == selectedLevelId) opt.selected = true;
                levelSelect.appendChild(opt);
            });
        }
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
                <div class="dashboard-section">
                    <div class="subjects-header">
                        <h2><?= $edit_subject ? 'Edit Subject' : 'Add Subject' ?></h2>
                    </div>
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= esc($error) ?></div>
                    <?php elseif ($message): ?>
                        <div style="color:#27ae60;"><?= esc($message) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <?php if ($edit_subject): ?>
                            <input type="hidden" name="subject_id" value="<?= esc($edit_subject['id']) ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div>
                                <label for="curriculum_id">Curriculum</label>
                                <select name="curriculum_id" id="curriculum_id" required onchange="updateLevelsDropdown('curriculum_id','class_level_id')">
                                    <option value="">-- Select Curriculum --</option>
                                    <?php foreach ($curriculums as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($edit_subject['curriculum_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= esc($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="class_level_id">Class Level</label>
                                <select name="class_level_id" id="class_level_id" required>
                                    <option value="">-- Select Level --</option>
                                    <?php
                                    $selectedCurr = $edit_subject['curriculum_id'] ?? '';
                                    $selectedLevel = $edit_subject['class_level_id'] ?? '';
                                    if ($selectedCurr && !empty($levelsByCurriculum[$selectedCurr])) {
                                        foreach ($levelsByCurriculum[$selectedCurr] as $lv) {
                                            echo '<option value="' . esc($lv['id']) . '"' . ($selectedLevel == $lv['id'] ? ' selected' : '') . '>' . esc($lv['level_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label for="subject_name">Subject Name</label>
                                <input type="text" name="subject_name" id="subject_name" value="<?= esc($edit_subject['subject_name'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div style="margin-top:1.2rem;">
                            <?php if ($edit_subject): ?>
                                <button type="submit" name="update_subject" class="btn">Update</button>
                                <a href="subjects.php" class="btn" style="background:#aaa;">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_subject" class="btn">Add Subject</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="dashboard-section">
                    <div class="subjects-header">
                        <h2>Subject List</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="subjects-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Curriculum</th>
                                    <th>Class Level</th>
                                    <th>Subject Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?= esc($subject['id']) ?></td>
                                            <td><?= esc($subject['curriculum'] ?? '-') ?></td>
                                            <td><?= esc($subject['level_name'] ?? '-') ?></td>
                                            <td><?= esc($subject['subject_name']) ?></td>
                                            <td class="subject-actions">
                                                <a href="subjects.php?edit_id=<?= esc($subject['id']) ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="subjects.php?delete_id=<?= esc($subject['id']) ?>" title="Delete" onclick="return confirm('Delete this subject?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
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
    <script>
    // On edit, pre-populate levels
    <?php if ($edit_subject): ?>
        document.addEventListener('DOMContentLoaded', function() {
            updateLevelsDropdown('curriculum_id','class_level_id', <?= json_encode($edit_subject['class_level_id']) ?>);
        });
    <?php endif; ?>
    </script>
</body>
</html>
