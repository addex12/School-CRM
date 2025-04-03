<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = "My Surveys";

// Fetch surveys assigned to the user's role
$role_id = $_SESSION['role_id'];
$surveys = $pdo->prepare("
    SELECT s.*
    FROM surveys s
    JOIN survey_roles sr ON s.id = sr.survey_id
    WHERE sr.role_id = ? AND s.is_active = 1
");
$surveys->execute([$role_id]);
$surveys = $surveys->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <nav>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php if (count($surveys) > 0): ?>
            <ul>
                <?php foreach ($surveys as $survey): ?>
                    <li>
                        <a href="survey_response.php?id=<?= $survey['id'] ?>">
                            <?= htmlspecialchars($survey['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No surveys assigned to you.</p>
        <?php endif; ?>
    </main>
</body>
</html>