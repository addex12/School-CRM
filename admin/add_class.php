<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Class";

// Fetch curriculums
$curriculum_stmt = $pdo->query("SELECT id, name FROM curriculums ORDER BY name");
$curriculums = $curriculum_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch class levels for all curriculums
$class_levels_stmt = $pdo->query("SELECT id, curriculum_id, level_name FROM class_levels ORDER BY curriculum_id, level_order, level_name");
$class_levels = $class_levels_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sections for all classes (if needed for display, not for selection here)
$sections_stmt = $pdo->query("SELECT id, class_id, section_name FROM sections ORDER BY class_id, section_name");
$sections = $sections_stmt ? $sections_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curriculum_id = $_POST['curriculum_id'] ?? '';
    $class_level_id = $_POST['class_level_id'] ?? '';
    $class_name = trim($_POST['class_name'] ?? '');
    $section_name = trim($_POST['section_name'] ?? '');

    if (!$curriculum_id || !$class_level_id || !$class_name) {
        $error = "All fields are required.";
    } else {
        // Insert class
        $stmt = $pdo->prepare("INSERT INTO classes (curriculum_id, class_level_id, class_name) VALUES (?, ?, ?)");
        if ($stmt->execute([$curriculum_id, $class_level_id, $class_name])) {
            $class_id = $pdo->lastInsertId();
            // If section is provided, insert section
            if ($section_name) {
                $section_stmt = $pdo->prepare("INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
                $section_stmt->execute([$class_id, $section_name]);
            }
            header("Location: classes.php?msg=Class+added+successfully");
            exit;
        } else {
            $error = "Failed to add class.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Class - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>
        // Pass PHP arrays to JS
        var classLevels = <?php echo json_encode($class_levels); ?>;
        function updateClassLevels() {
            var curriculumId = document.getElementById('curriculum_id').value;
            var classLevelSelect = document.getElementById('class_level_id');
            classLevelSelect.innerHTML = '';
            var found = false;
            classLevels.forEach(function(level) {
                if (level.curriculum_id == curriculumId) {
                    var opt = document.createElement('option');
                    opt.value = level.id;
                    opt.text = level.level_name;
                    classLevelSelect.appendChild(opt);
                    found = true;
                }
            });
            if (!found) {
                var opt = document.createElement('option');
                opt.value = '';
                opt.text = '-- No Levels Available --';
                classLevelSelect.appendChild(opt);
            }
        }
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Add Class</h1>
            </header>
            <div class="content">
                <div class="dashboard-section" style="max-width:500px;">
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div style="margin-bottom:1rem;">
                            <label for="curriculum_id">Curriculum</label>
                            <select name="curriculum_id" id="curriculum_id" required onchange="updateClassLevels()">
                                <option value="">-- Select Curriculum --</option>
                                <?php foreach ($curriculums as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="class_level_id">Class Level</label>
                            <select name="class_level_id" id="class_level_id" required>
                                <option value="">-- Select Curriculum First --</option>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="class_name">Class Name</label>
                            <input type="text" name="class_name" id="class_name" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="section_name">Section (optional, e.g. A, B, C)</label>
                            <input type="text" name="section_name" id="section_name" maxlength="10">
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Add Class</button>
                        <a href="classes.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        document.getElementById('curriculum_id').addEventListener('change', updateClassLevels);
    </script>
</body>
</html>
