<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header("Location: sections.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch section info
$stmt = $pdo->prepare("
    SELECT s.*, cl.class_name, cl.id as class_id, cu.id as curriculum_id, lv.id as level_id, cu.name as curriculum, lv.level_name
    FROM sections s
    LEFT JOIN classes cl ON s.class_id = cl.id
    LEFT JOIN curriculums cu ON cl.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON cl.class_level_id = lv.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    header("Location: sections.php");
    exit;
}

// Fetch all classes, curriculums, and levels for dropdowns
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT id, level_name FROM class_levels ORDER BY level_order")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = $_POST['section_name'];
    $class_id = $_POST['class_id'];
    $curriculum_id = $_POST['curriculum_id'];
    $level_id = $_POST['level_id'];
    $created_at = $_POST['created_at'];

    // Update class's curriculum and level if changed
    $pdo->prepare("UPDATE classes SET curriculum_id = ?, class_level_id = ? WHERE id = ?")
        ->execute([$curriculum_id, $level_id, $class_id]);

    // Update section
    $stmt = $pdo->prepare("UPDATE sections SET section_name = ?, class_id = ?, created_at = ? WHERE id = ?");
    $stmt->execute([$section_name, $class_id, $created_at, $id]);

    header("Location: sections.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Section</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Section</h1>
            </header>
            <div class="content">
                <form method="post" class="form-section-edit">
                    <label>Section Name:
                        <input type="text" name="section_name" value="<?= htmlspecialchars($section['section_name']) ?>" required>
                    </label>
                    <label>Class:
                        <select name="class_id" required>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $cl['id'] == $section['class_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cl['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Curriculum:
                        <select name="curriculum_id" required>
                            <?php foreach ($curriculums as $cu): ?>
                                <option value="<?= $cu['id'] ?>" <?= $cu['id'] == $section['curriculum_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cu['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Class Level:
                        <select name="level_id" required>
                            <?php foreach ($levels as $lv): ?>
                                <option value="<?= $lv['id'] ?>" <?= $lv['id'] == $section['level_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lv['level_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Created At:
                        <input type="datetime-local" name="created_at" value="<?= date('Y-m-d\TH:i', strtotime($section['created_at'])) ?>" required>
                    </label>
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="sections.php" class="btn" style="background:#aaa;">Cancel</a>
                </form>
            </div>
        </div>
    </div>
            <?php include 'includes/footer.php'; ?>

</body>
</html>