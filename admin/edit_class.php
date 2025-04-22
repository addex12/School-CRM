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
    header("Location: classes.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch class info
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header("Location: classes.php");
    exit;
}

// Fetch all curriculums and levels for dropdowns
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT id, level_name FROM class_levels ORDER BY level_order")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = $_POST['class_name'];
    $curriculum_id = $_POST['curriculum_id'];
    $level_id = $_POST['level_id'];

    $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, curriculum_id = ?, class_level_id = ? WHERE id = ?");
    $stmt->execute([$class_name, $curriculum_id, $level_id, $id]);

    header("Location: classes.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Class</h1>
            </header>
            <div class="content">
                <form method="post" class="form-class-edit">
                    <label>Class Name:
                        <input type="text" name="class_name" value="<?= htmlspecialchars($class['class_name']) ?>" required>
                    </label>
                    <label>Curriculum:
                        <select name="curriculum_id" required>
                            <?php foreach ($curriculums as $cu): ?>
                                <option value="<?= $cu['id'] ?>" <?= $cu['id'] == $class['curriculum_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cu['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Class Level:
                        <select name="level_id" required>
                            <?php foreach ($levels as $lv): ?>
                                <option value="<?= $lv['id'] ?>" <?= $lv['id'] == $class['class_level_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lv['level_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="classes.php" class="btn" style="background:#aaa;">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
