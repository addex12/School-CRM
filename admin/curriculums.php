<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Curriculums";

// Handle add curriculum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_curriculum'])) {
    $name = trim($_POST['curriculum_name'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO curriculums (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: curriculums.php?msg=Curriculum+added");
        exit;
    }
}

// Handle add class level
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $level_name = trim($_POST['level_name'] ?? '');
    $level_order = intval($_POST['level_order'] ?? 0);
    if ($curriculum_id && $level_name) {
        $stmt = $pdo->prepare("INSERT INTO class_levels (curriculum_id, level_name, level_order) VALUES (?, ?, ?)");
        $stmt->execute([$curriculum_id, $level_name, $level_order]);
        header("Location: curriculums.php?msg=Level+added");
        exit;
    }
}

// Fetch curriculums and their levels
$curriculums = $pdo->query("SELECT * FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM class_levels ORDER BY curriculum_id, level_order, level_name")->fetchAll(PDO::FETCH_ASSOC);
$levelsByCurriculum = [];
foreach ($levels as $level) {
    $levelsByCurriculum[$level['curriculum_id']][] = $level;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Curriculums - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .curriculum-section { margin-bottom: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(44,62,80,0.07); padding: 2rem 1.5rem; }
        .curriculum-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .curriculum-header h2 { margin: 0; font-size: 1.5rem; color: #34495e; }
        .curriculum-table { width: 100%; border-collapse: collapse; }
        .curriculum-table th, .curriculum-table td { padding: 12px 16px; border-bottom: 1px solid #f0f2f5; text-align: left; }
        .curriculum-table th { background: #f8f9fa; font-weight: 600; color: #34495e; }
        .curriculum-table tr:hover { background: #f4f8fb; }
        .level-list { margin: 0; padding-left: 1.2em; }
        .level-list li { margin-bottom: 0.2em; }
        .curriculum-actions a { margin-right: 8px; color: #3498db; text-decoration: none; font-size: 1.1em; }
        .curriculum-actions a:last-child { margin-right: 0; }
        @media (max-width: 600px) {
            .curriculum-table th, .curriculum-table td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="curriculum-section">
                    <div class="curriculum-header">
                        <h2>Curriculums</h2>
                    </div>
                    <form method="post" style="margin-bottom:2rem;">
                        <label for="curriculum_name">Add New Curriculum:</label>
                        <input type="text" name="curriculum_name" id="curriculum_name" required>
                        <button type="submit" name="add_curriculum" class="btn" style="background:#3498db;color:#fff;">Add Curriculum</button>
                    </form>
                    <table class="curriculum-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Class Levels</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($curriculums)): ?>
                            <?php foreach ($curriculums as $curriculum): ?>
                                <tr>
                                    <td><?= htmlspecialchars($curriculum['id']) ?></td>
                                    <td><?= htmlspecialchars($curriculum['name']) ?></td>
                                    <td>
                                        <ul class="level-list">
                                            <?php if (!empty($levelsByCurriculum[$curriculum['id']])): ?>
                                                <?php foreach ($levelsByCurriculum[$curriculum['id']] as $level): ?>
                                                    <li>
                                                        <?= htmlspecialchars($level['level_name']) ?>
                                                        <a href="edit_level.php?id=<?= $level['id'] ?>" title="Edit Level"><i class="fas fa-edit"></i></a>
                                                        <a href="delete_level.php?id=<?= $level['id'] ?>" title="Delete Level" onclick="return confirm('Delete this level?')"><i class="fas fa-trash-alt"></i></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li><em>No levels</em></li>
                                            <?php endif; ?>
                                        </ul>
                                        <form method="post" style="margin-top:0.5em;">
                                            <input type="hidden" name="curriculum_id" value="<?= $curriculum['id'] ?>">
                                            <input type="text" name="level_name" placeholder="Add Level" required>
                                            <input type="number" name="level_order" placeholder="Order" style="width:70px;">
                                            <button type="submit" name="add_level" class="btn" style="background:#16a085;color:#fff;padding:2px 10px;">+</button>
                                        </form>
                                    </td>
                                    <td class="curriculum-actions">
                                        <a href="edit_curriculum.php?id=<?= $curriculum['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_curriculum.php?id=<?= $curriculum['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this curriculum?')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No curriculums found.</td>
                            </tr>
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
