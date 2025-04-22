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

// Fetch subjects with curriculum info (no class_levels join)
$stmt = $pdo->query("
    SELECT s.id, s.subject_name, cu.name AS curriculum
    FROM subjects s
    LEFT JOIN curriculums cu ON s.curriculum_id = cu.id
    ORDER BY cu.name, s.subject_name
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
        .subjects-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .subjects-header .btn:hover {
            background: #217dbb;
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
        @media (max-width: 900px) {
            .subjects-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .subjects-table th, .subjects-table td {
                padding: 8px 6px;
            }
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
                        <table class="subjects-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject Name</th>
                                    <th>Curriculum</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?= esc($subject['id']) ?></td>
                                            <td><?= esc($subject['subject_name']) ?></td>
                                            <td><?= esc($subject['curriculum'] ?? '-') ?></td>
                                            <td class="subject-actions">
                                                <a href="edit_subject.php?id=<?= esc($subject['id']) ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_subject.php?id=<?= esc($subject['id']) ?>" title="Delete" onclick="return confirm('Delete this subject?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No subjects found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <?php include 'includes/footer.php'; ?>

</body>
</html>
