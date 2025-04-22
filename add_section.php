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

// Fetch all grades/classes from class_names
$class_names = $db->query("SELECT id, grade FROM class_names")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name_id = $_POST['class_name_id'];
    $section = strtoupper($_POST['section']);

    // Check if section already exists for this grade
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM sections WHERE class_name_id = ? AND section = ?");
    $check_stmt->execute([$class_name_id, $section]);
    if ($check_stmt->fetchColumn() > 0) {
        $message = "Section already exists for this grade.";
    } else {
        // Insert into sections table
        $stmt = $db->prepare("INSERT INTO sections (class_name_id, section) VALUES (?, ?)");
        if ($stmt->execute([$class_name_id, $section])) {
            $message = "Section added successfully!";
        } else {
            $message = "Failed to add section.";
        }
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
        <label for="class_name_id">Grade Name:</label>
        <select name="class_name_id" id="class_name_id" required>
            <?php foreach ($class_names as $class): ?>
                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['grade']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <label for="section">Section:</label>
        <input type="text" name="section" id="section" maxlength="1" required placeholder="A">
        <br><br>
        <button type="submit">Add Section</button>
    </form>

    <h3>All Classes/Grades</h3>
    <ul>
        <?php foreach ($class_names as $class): ?>
            <li><?= htmlspecialchars($class['grade']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>