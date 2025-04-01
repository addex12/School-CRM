<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info with role validation
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM surveys s
    JOIN survey_roles sr ON s.id = sr.survey_id
    WHERE s.id = ? 
    AND s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND sr.role_id = ?
");
$stmt->execute([$survey_id, $_SESSION['role_id']]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}

// Display survey details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> - Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="survey-container">
        <h1><?= htmlspecialchars($survey['title']) ?></h1>
        <p><?= htmlspecialchars($survey['description']) ?></p>
        <a href="survey_response.php?id=<?= $survey['id'] ?>" class="btn">Take Survey</a>
    </div>
</body>
</html>