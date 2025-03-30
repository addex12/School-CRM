<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? null;

if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Survey - <?= htmlspecialchars($survey['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <h1>Survey: <?= htmlspecialchars($survey['title']) ?></h1>
            <p><?= htmlspecialchars($survey['description']) ?></p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Field Label</th>
                        <th>Field Type</th>
                        <th>Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?= htmlspecialchars($field['field_label']) ?></td>
                        <td><?= htmlspecialchars($field['field_type']) ?></td>
                        <td><?= $field['is_required'] ? 'Yes' : 'No' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
