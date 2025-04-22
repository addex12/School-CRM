<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Section";

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
        $stmt = $pdo->prepare("INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
        if ($stmt->execute([$class_id, $section_name])) {
            header("Location: sections.php?msg=Section+added+successfully");
            exit;
        } else {
            $error = "Failed to add section.";
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
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="section_name">Section Name</label>
                            <input type="text" name="section_name" id="section_name" required>
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Add Section</button>
                        <a href="sections.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
