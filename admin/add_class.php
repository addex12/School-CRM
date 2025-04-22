<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Add Class";

// Define curriculum options and their class structure
$curriculums = [
    'Cambridge' => ['Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5', 'Year 6', 'Year 7', 'Year 8', 'Year 9', 'Year 10', 'Year 11', 'Year 12'],
    'Ethiopian' => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
    'American'  => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
    'Standard'  => ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5', 'Level 6']
];

$error = '';
$success = '';

// --- Database schema update hint ---
// You should update your classes table to support curriculum info:
// ALTER TABLE classes ADD COLUMN curriculum VARCHAR(50) NOT NULL AFTER id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curriculum = $_POST['curriculum'] ?? '';
    $class_name = trim($_POST['class_name'] ?? '');

    if (!$curriculum || !$class_name) {
        $error = "All fields are required.";
    } else {
        // Insert both curriculum and class_name
        $stmt = $pdo->prepare("INSERT INTO classes (curriculum, class_name) VALUES (?, ?)");
        if ($stmt->execute([$curriculum, $class_name])) {
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
        // JavaScript to update class_name options based on curriculum
        function updateClassOptions() {
            var curriculum = document.getElementById('curriculum').value;
            var classNameSelect = document.getElementById('class_name');
            var options = {
                <?php foreach ($curriculums as $key => $levels): ?>
                "<?= $key ?>": <?= json_encode($levels) ?>,
                <?php endforeach; ?>
            };
            classNameSelect.innerHTML = '';
            if (curriculum && options[curriculum]) {
                options[curriculum].forEach(function(level) {
                    var opt = document.createElement('option');
                    opt.value = level;
                    opt.text = level;
                    classNameSelect.appendChild(opt);
                });
            } else {
                var opt = document.createElement('option');
                opt.value = '';
                opt.text = '-- Select Curriculum First --';
                classNameSelect.appendChild(opt);
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
                            <label for="curriculum">Curriculum</label>
                            <select name="curriculum" id="curriculum" required onchange="updateClassOptions()">
                                <option value="">-- Select Curriculum --</option>
                                <?php foreach ($curriculums as $key => $levels): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($key) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="class_name">Class Name</label>
                            <select name="class_name" id="class_name" required>
                                <option value="">-- Select Curriculum First --</option>
                            </select>
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
        // Initialize options if curriculum is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            updateClassOptions();
        });
    </script>
</body>
</html>
