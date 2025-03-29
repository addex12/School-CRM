<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? null;

if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey responses
$stmt = $pdo->prepare("
    SELECT sr.*, u.username 
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id 
    WHERE sr.survey_id = ?
    ORDER BY sr.submitted_at DESC
"); // Ensure 'submitted_at' is the correct column name in the database
$stmt->execute([$survey_id]);
$responses = $stmt->fetchAll();

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

$pageTitle = "Results: " . htmlspecialchars($survey['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <a href="export.php?survey_id=<?= $survey_id ?>" class="btn btn-primary">Export Results</a>
                <a href="surveys.php" class="btn btn-secondary">Back to Surveys</a>
            </header>
            <div class="content">
                <?php if (count($responses) > 0): ?>
                    <div id="chart-container"></div> <!-- Container for charts -->
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Respondent</th>
                                <?php foreach ($fields as $field): ?>
                                    <th><?= htmlspecialchars($field['field_label']) ?></th>
                                <?php endforeach; ?>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses as $response): ?>
                                <tr>
                                    <td><?= htmlspecialchars($response['username'] ?? 'Anonymous') ?></td>
                                    <?php 
                                    $answers = !empty($response['answers']) ? json_decode($response['answers'], true) : [];
                                    foreach ($fields as $field): 
                                    ?>
                                        <td><?= htmlspecialchars($answers[$field['field_name']] ?? 'N/A') ?></td>
                                    <?php endforeach; ?>
                                    <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No responses found for this survey.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Embed survey data for JavaScript -->
    <script id="survey-results-data" type="application/json">
        <?= json_encode(['fields' => $fields, 'responses' => $responses]) ?>
    </script>
    <script src="../assets/js/results.js"></script>
</body>
</html>