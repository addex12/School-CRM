<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$message = '';
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_curriculum'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO curriculums (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $message = "Curriculum added successfully!";
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_curriculum'])) {
    $curriculum_id = intval($_POST['curriculum_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name && $curriculum_id) {
        $stmt = $pdo->prepare("UPDATE curriculums SET name=?, description=? WHERE id=?");
        $stmt->execute([$name, $description, $curriculum_id]);
        $message = "Curriculum updated successfully!";
        $edit_id = 0;
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM curriculums WHERE id=?");
    $stmt->execute([$delete_id]);
    $message = "Curriculum deleted successfully!";
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

// Fetch all curriculums
$curriculums = $pdo->query("SELECT * FROM curriculums ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch curriculum for editing
$edit_curriculum = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM curriculums WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_curriculum = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch curriculums and their levels
$levels = $pdo->query("SELECT * FROM class_levels ORDER BY curriculum_id, level_order, level_name")->fetchAll(PDO::FETCH_ASSOC);
$levelsByCurriculum = [];
foreach ($levels as $level) {
    $levelsByCurriculum[$level['curriculum_id']][] = $level;
}

// Helper
function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Curriculums Management</title>
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
                <h1>Curriculums</h1>
            </header>
            <div class="content">
                <?php if ($message): ?>
                    <p class="success"><?= esc($message) ?></p>
                <?php endif; ?>

                <div class="dashboard-section">
                    <h2><?= $edit_curriculum ? 'Edit Curriculum' : 'Add Curriculum' ?></h2>
                    <form method="post">
                        <?php if ($edit_curriculum): ?>
                            <input type="hidden" name="curriculum_id" value="<?= esc($edit_curriculum['id']) ?>">
                        <?php endif; ?>
                        <label for="name">Name:</label>
                        <input type="text" name="name" id="name" value="<?= esc($edit_curriculum['name'] ?? '') ?>" required>
                        <label for="description">Description:</label>
                        <textarea name="description" id="description"><?= esc($edit_curriculum['description'] ?? '') ?></textarea>
                        <?php if ($edit_curriculum): ?>
                            <button type="submit" name="update_curriculum" class="btn">Update</button>
                            <a href="curriculums.php" class="btn">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_curriculum" class="btn">Add</button>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="dashboard-section">
                    <h2>All Curriculums</h2>
                    <div class="table-responsive">
                        <table class="teachers-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Class Levels</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($curriculums as $curriculum): ?>
                                    <tr>
                                        <td><?= esc($curriculum['id']) ?></td>
                                        <td><?= esc($curriculum['name']) ?></td>
                                        <td><?= esc($curriculum['description']) ?></td>
                                        <td>
                                            <ul class="level-list">
                                                <?php if (!empty($levelsByCurriculum[$curriculum['id']])): ?>
                                                    <?php foreach ($levelsByCurriculum[$curriculum['id']] as $level): ?>
                                                        <li>
                                                            <?= esc($level['level_name']) ?>
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
                                        <td>
                                            <a href="curriculums.php?edit_id=<?= esc($curriculum['id']) ?>" class="btn">Edit</a>
                                            <a href="curriculums.php?delete_id=<?= esc($curriculum['id']) ?>" class="btn" onclick="return confirm('Delete this curriculum?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($curriculums)): ?>
                                    <tr>
                                        <td colspan="5">No curriculums found.</td>
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
