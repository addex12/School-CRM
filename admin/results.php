<?php
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

// Date range filter
$whereClause = "sr.survey_id = ?";
$params = [$survey_id];
if (!empty($_GET['start_date'])) {
    $whereClause .= " AND sr.submitted_at >= ?";
    $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $whereClause .= " AND sr.submitted_at <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
}

// Get responses with pagination
$per_page = 20;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM survey_responses sr 
    WHERE $whereClause
");
$stmt->execute($params);
$total_responses = $stmt->fetchColumn();
$total_pages = ceil($total_responses / $per_page);

$stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$responses = $stmt->fetchAll();

// Prepare data for charts
$analytics = [];
foreach ($fields as $field) {
    $stmt = $pdo->prepare("
        SELECT field_value, COUNT(*) as count 
        FROM response_data 
        WHERE field_id = ? 
        GROUP BY field_value
        ORDER BY count DESC
    ");
    $stmt->execute([$field['id']]);
    $analytics[$field['id']] = $stmt->fetchAll();
}

// Prepare JSON data for JavaScript
$chart_data = [
    'survey' => $survey,
    'fields' => $fields,
    'analytics' => $analytics,
    'total_responses' => $total_responses
];
$chart_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results: <?= htmlspecialchars($survey['title']) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="export_csv.php?survey_id=<?= $survey_id ?>">CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export-pdf">PDF</a></li>
                            <li><a class="dropdown-item" href="export_json.php?survey_id=<?= $survey_id ?>">JSON</a></li>
                        </ul>
                    </div>
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>

            <div class="survey-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_responses ?></div>
                    <div class="stat-label">Total Responses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= date('M j, Y', strtotime($survey['starts_at'])) ?></div>
                    <div class="stat-label">Start Date</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= date('M j, Y', strtotime($survey['ends_at'])) ?></div>
                    <div class="stat-label">End Date</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></div>
                    <div class="stat-label">Anonymous</div>
                </div>
            </div>

            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
                    <div class="form-group">
                        <label for="start_date">From:</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">To:</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="results.php?survey_id=<?= $survey_id ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </form>
            </div>

            <div class="chart-section" id="chart-section">
                <div class="chart-container" id="summary-chart"></div>
                <?php foreach ($fields as $field): ?>
                    <div class="chart-container" id="chart-<?= $field['id'] ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="response-table-section">
                <h3>Individual Responses</h3>
                <?php if ($total_responses > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Respondent</th>
                                    <th>Role</th>
                                    <?php foreach ($fields as $field): ?>
                                        <th><?= htmlspecialchars($field['field_label']) ?></th>
                                    <?php endforeach; ?>
                                    <th>Submitted At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $index => $response): ?>
                                    <tr>
                                        <td><?= $index + 1 + $offset ?></td>
                                        <td>
                                            <?php if ($survey['is_anonymous']): ?>
                                                Anonymous
                                            <?php else: ?>
                                                <?= htmlspecialchars($response['username'] ?? 'N/A') ?>
                                                <?php if ($response['email']): ?>
                                                    <br><small><?= htmlspecialchars($response['email']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                                        <?php 
                                        $stmt = $pdo->prepare("
                                            SELECT d.field_value, f.field_label 
                                            FROM response_data d
                                            JOIN survey_fields f ON d.field_id = f.id
                                            WHERE d.response_id = ?
                                        ");
                                        $stmt->execute([$response['id']]);
                                        $response_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                        
                                        foreach ($fields as $field): ?>
                                            <td>
                                                <?= isset($response_data[$field['field_label']]) ? 
                                                    htmlspecialchars($response_data[$field['field_label']]) : 'N/A' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                        <td>
                                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $i ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page + 1 ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        No responses found for this survey. Please check back later.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const chartData = <?= $chart_json ?>;
    </script>
    <script src="../assets/js/results-charts.js"></script>
    <script src="../assets/js/results-export.js"></script>
</body>
</html>