<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid survey ID.";
    header("Location: surveys.php");
    exit();
}

$survey_id = $_GET['id'];

// Fetch survey details
$stmt = $pdo->prepare("
    SELECT s.*, GROUP_CONCAT(sr.role_id) AS target_roles
    FROM surveys s
    LEFT JOIN survey_roles sr ON s.id = sr.survey_id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch all roles
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $target_roles = $_POST['target_roles'] ?? [];
        $starts_at = $_POST['starts_at'];
        $ends_at = $_POST['ends_at'];

        // Update survey details
        $stmt = $pdo->prepare("
            UPDATE surveys 
            SET title = ?, description = ?, category_id = ?, starts_at = ?, ends_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $category_id, $starts_at, $ends_at, $survey_id]);

        // Update survey roles
        $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        foreach ($target_roles as $role_id) {
            $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
            $stmt->execute([$survey_id, $role_id]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Survey updated successfully!";
        header("Location: surveys.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating survey: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Survey</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="title">Survey Title:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($pdo->query("SELECT * FROM survey_categories") as $category): ?>
                        <option value="<?= $category['id']; ?>" <?= $category['id'] == $survey['category_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Target Roles:</label>
                <?php foreach ($roles as $role): ?>
                    <label>
                        <input type="checkbox" name="target_roles[]" value="<?= $role['id']; ?>" <?= in_array($role['id'], explode(',', $survey['target_roles'])) ? 'checked' : ''; ?>>
                        <?= htmlspecialchars($role['role_name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label for="starts_at">Start Date/Time:</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="ends_at">End Date/Time:</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'])); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Survey</button>
            <a href="surveys.php" class="btn">Cancel</a>
        </form>
    </div>
</body>
</html>
