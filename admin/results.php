<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

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

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Fetch survey responses
$stmt = $pdo->prepare("
    SELECT sr.*, u.username, sr.answers 
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id 
    WHERE sr.survey_id = ?
    ORDER BY sr.submitted_at DESC
");
$stmt->execute([$survey_id]);
$responses = $stmt->fetchAll();

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
    <div class="export-dropdown">
        <button class="btn btn-primary" onclick="toggleExportMenu()">Export Results ▼</button>
        <div class="export-menu" id="exportMenu">
            <a href="export.php?survey_id=<?= $survey_id ?>" class="export-option">Export as CSV</a>
            <a href="#" id="export-pdf" class="export-option">Export as PDF</a>
        </div>
    </div>
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
                                    <td><?= $survey['is_anonymous'] ? 'Anonymous' : htmlspecialchars($response['username'] ?? 'Anonymous') ?></td>
                                    <?php 
                                    $answers = safe_json_decode($response['answers']); // Use helper function
                                    foreach ($fields as $field): ?>
                                        <td><?= isset($answers[$field['field_name']]) ? htmlspecialchars($answers[$field['field_name']]) : 'N/A' ?></td>
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
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    function toggleExportMenu() {
        document.getElementById('exportMenu').style.display = 
            document.getElementById('exportMenu').style.display === 'block' ? 'none' : 'block';
    }
</script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const chartContainer = document.getElementById('chart-container');
    const table = document.querySelector('.table');
    function toggleExportMenu() {
        document.getElementById('exportMenu').style.display = 
            document.getElementById('exportMenu').style.display === 'block' ? 'none' : 'block';
    }
    const imgWidth = 190; // Adjust to fit PDF width
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
</script>
<!-- Keep the existing scripts that follow -->0, imgWidth, imgHeight);
<script id="survey-results-data" type="application/json">
    <?= json_encode(['fields' => $fields, 'responses' => $responses]) ?>
</script>
<script src="../assets/js/results.js"></script>
</body>ep the existing scripts that follow -->
</html> id="survey-results-data" type="application/json">
    <?= json_encode(['fields' => $fields, 'responses' => $responses]) ?>
</script>
<script src="../assets/js/results.js"></script>
</body>
</html>