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

// Add a date range filter form
?>
<form method="GET" class="filter-form">
    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
    <label for="end_date">End Date:</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
<?php
// Modify the query to filter responses by date range
$whereClause = "sr.survey_id = ?";
$params = [$survey_id];
if (!empty($_GET['start_date'])) {
    $whereClause .= " AND sr.submitted_at >= ?";
    $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $whereClause .= " AND sr.submitted_at <= ?";
    $params[] = $_GET['end_date'];
}
$stmt = $pdo->prepare("
    SELECT sr.*, u.username, sr.answers 
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id 
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
");
$stmt->execute($params);
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
        <div class="admin-content">
            <h1><?= $pageTitle ?></h1>
            <p>Results for the survey: <strong><?= htmlspecialchars($survey['title']) ?></strong></p>
            <p><?= htmlspecialchars($survey['description']) ?></p>
            <p>Created on: <?= date('M j, Y', strtotime($survey['created_at'])) ?></p>
            <p>Available from <?= date('M j, Y g:i A', strtotime($survey['starts_at'])) ?> to <?= date('M j, Y g:i A', strtotime($survey['ends_at'])) ?></p>
            <p>Respondents: <?= count($responses) ?></p>
            <p>Anonymous: <?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></p>
            <p>Allow Multiple Responses: <?= $survey['allow_multiple'] ? 'Yes' : 'No' ?></p>
        </div>
        <div class="admin-main">
        <header class="admin-header">
    <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
    <div class="export-dropdown">
        <button class="btn btn-primary" onclick="toggleExportMenu()">Export Results ▼</button>
        <div class="export-menu" id="exportMenu">
            <a href="export.php?survey_id=<?= $survey_id ?>" class="export-option">Export as CSV</a>
            <a href="#" id="export-pdf" class="export-option">Export as PDF</a>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
        <script src="../js/export.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../js/chart.js"></script>
        <script src="../js/survey-results.js"></script>
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
                                <?php if ($survey['is_anonymous'] && !$response['username']) {
                                    $response['username'] = 'Anonymous';
                                } ?>
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
                                <?php if ($survey['is_anonymous'] && !$response['username']) {
                                    $response['username'] = 'Anonymous';
                                } ?>
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
                <?php else: ?>     ‬
                    <p>No responses found for this survey.</p> ‬
                <?php endif; ?>
            </div> ‬
        </div> ‬
    </div> ‬
<?php include 'includes/footer.php'; ?> ‬