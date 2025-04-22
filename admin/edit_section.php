<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Edit Section";

$section_id = $_GET['id'] ?? '';
if (!$section_id) {
    header("Location: sections.php?error=Section+ID+missing");
    exit;
}

// Fetch section info
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    header("Location: sections.php?error=Section+not+found");
    exit;
}

// Fetch available classes
$class_stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $section_name = trim($_POST['section_name'] ?? '');

    if (!$class_id || !$section_name) {
        $error = "All fields are required.";
    } else {
        $update_stmt = $pdo->prepare("UPDATE sections SET class_id = ?, section_name = ? WHERE id = ?");
        if ($update_stmt->execute([$class_id, $section_name, $section_id])) {
            header("Location: sections.php?msg=Section+updated+successfully");
            exit;
        } else {
            $error = "Failed to update section.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
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
                <div class="dashboard-section" style="max-width:500px;">
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div style="margin-bottom:1rem;">
                            <label for="class_id">Select Class</label>
                            <select name="class_id" id="class_id" required>
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= $class['id'] == $section['class_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="section_name">Section Name</label>
                            <input type="text" name="section_name" id="section_name" value="<?= htmlspecialchars($section['section_name']) ?>" required>
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Update Section</button>
                        <a href="sections.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
