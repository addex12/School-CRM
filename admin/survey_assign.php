<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/functions.php';

$pageTitle = "Assign Survey";

// Fetch survey details
$survey_id = $_GET['id'] ?? null;
$survey = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$survey->execute([$survey_id]);
$survey = $survey->fetch();

if (!$survey) {
    header("Location: surveys.php?error=Survey not found");
    exit();
}

// Fetch all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned_roles = $_POST['roles'] ?? [];
    $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?")->execute([$survey_id]);

    foreach ($assigned_roles as $role_id) {
        $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)")
            ->execute([$survey_id, $role_id]);
    }

    header("Location: surveys.php?success=Survey assigned successfully");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <form method="POST">
                    <h2>Assign Survey: <?= htmlspecialchars($survey['title']) ?></h2>
                    <div class="form-group">
                        <label for="roles">Assign to Roles</label>
                        <select name="roles[]" id="roles" multiple>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
