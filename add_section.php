<?php
// Database connection (adjust as needed)
require_once '../includes/db.php'; // Include your database configuration file
require_once '../includes/config.php'; // Include your database configuration file
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch class levels
$class_levels = $db->query("SELECT id, level_name FROM class_levels")->fetchAll(PDO::FETCH_ASSOC);
// Fetch class names
$class_names = $db->query("SELECT id, grade FROM class_names")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_level_id = $_POST['class_level_id'];
    $class_name_id = $_POST['class_name_id'];
    $section = $_POST['section'];

    // Get grade name from class_names
    $grade_stmt = $db->prepare("SELECT grade FROM class_names WHERE id = ?");
    $grade_stmt->execute([$class_name_id]);
    $grade = $grade_stmt->fetchColumn();

    if ($grade) {
        // Combine grade and section for class_name
        $class_name = $grade . ' ' . strtoupper($section);

        // Insert into classes table
        $stmt = $db->prepare("INSERT INTO classes (class_level_id, class_name) VALUES (?, ?)");
        if ($stmt->execute([$class_level_id, $class_name])) {
            $message = "Section added successfully!";
        } else {
            $message = "Failed to add section.";
        }
    } else {
        $message = "Invalid class name selected.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Section</title>
</head>
<body>
    <h2>Add Section</h2>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="class_level_id">Class Level:</label>
        <select name="class_level_id" id="class_level_id" required>
            <?php foreach ($class_levels as $level): ?>
                <option value="<?= $level['id'] ?>"><?= htmlspecialchars($level['level_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <label for="class_name_id">Class Name:</label>
        <select name="class_name_id" id="class_name_id" required>
            <?php foreach ($class_names as $name): ?>
                <option value="<?= $name['id'] ?>"><?= htmlspecialchars($name['grade']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <label for="section">Section:</label>
        <input type="text" name="section" id="section" maxlength="1" required placeholder="A">
        <br><br>
        <button type="submit">Add Section</button>
    </form>
</body>
</html>