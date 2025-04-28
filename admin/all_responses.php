<?php
ob_start();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

$pageTitle = "All Survey Responses";

// Fetch all surveys for filter dropdown
$surveyStmt = $pdo->prepare("SELECT id, title FROM surveys WHERE is_active = TRUE OR is_public = 1 ORDER BY created_at DESC");
$surveyStmt->execute();
$allSurveys = $surveyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get optional survey filter from GET
$filter_survey_id = filter_input(INPUT_GET, 'survey_id', FILTER_VALIDATE_INT);

$whereClause = "1=1";
$params = [];
$survey_filter = '';

if ($filter_survey_id) {
    $whereClause .= " AND sr.survey_id = ?";
    $params[] = $filter_survey_id;
    $survey_filter = "&survey_id=" . urlencode($filter_survey_id);
}

// Pagination setup
$per_page = 20;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));
$offset = ($page - 1) * $per_page;

// Get total responses count
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses sr WHERE $whereClause");
$total_stmt->execute($params);
$total_responses = $total_stmt->fetchColumn();
$total_pages = max(1, ceil($total_responses / $per_page));

// Get paginated responses with survey info
$response_stmt = $pdo->prepare("
    SELECT sr.*, s.title AS survey_title
    FROM survey_responses sr
    JOIN surveys s ON sr.survey_id = s.id
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$params_for_execute = $params;
$params_for_execute[] = $per_page;
$params_for_execute[] = $offset;
$response_stmt->execute($params_for_execute);
$responses = $response_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <style>
        .response-table {
            width: 100%;
            border-collapse: collapse;
        }
        .response-table th, .response-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .response-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .response-table tr:hover {
            background-color: #f8f9fa;
        }
        .filter-form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .erpnext-btn {
            background: #007bfc;
            color: #fff;
            border: 1px solid #007bfc;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .erpnext-btn:hover {
            background: #0056b3;
        }
        .erpnext-btn-secondary {
            background: #6c757d;
            color: #fff;
            border: 1px solid #6c757d;
        }
        .erpnext-btn-secondary:hover {
            background: #5a6268;
        }
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }
        .page-item.active .page-link {
            background-color: #007bfc;
            border-color: #007bfc;
            color: white;
        }
        .page-link {
            position: relative;
            display: block;
            padding: 0.4rem 0.6rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #007bfc;
            background-color: #fff;
            border: 1px solid #dee2e6;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .page-link:hover {
            background-color: #e9ecef;
            color: #007bfc;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        .card-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .card-body {
            font-size: 0.9rem;
            color: #555;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 3px;
        }
        .btn-outline-primary {
            color: #007bfc;
            border: 1px solid #007bfc;
        }
        .btn-outline-primary:hover {
            background: #007bfc;
            color: #fff;
        }
        .btn-warning {
            background: #ffc107;
            color: #fff;
            border: 1px solid #ffc107;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>

            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="row">
                        <div class="col-md-10">
                            <label for="survey_id">Filter by Survey</label>
                            <select name="survey_id" id="survey_id" class="form-control">
                                <option value="">All Surveys</option>
                                <?php foreach ($allSurveys as $surveyOption): ?>
                                    <option value="<?= htmlspecialchars($surveyOption['id']) ?>" <?= ($filter_survey_id == $surveyOption['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($surveyOption['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="erpnext-btn">Search</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($total_responses > 0): ?>
                <div class="card">
                    <div class="card-header">Survey Responses</div>
                    <div class="card-body">
                        <table class="response-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Survey</th>
                                    <th>Respondent</th>
                                    <th>Submitted At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $index => $response): ?>
                                    <tr>
                                        <td><?= $index + 1 + $offset ?></td>
                                        <td><?= htmlspecialchars($response['survey_title']) ?></td>
                                        <td>Anonymous</td>
                                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                        <td>
                                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="results.php?survey_id=<?= $response['survey_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-arrow-left"></i> Results
                                            </a>
                                            <a href="edit_survey.php?id=<?= $response['survey_id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_survey.php?id=<?= $response['survey_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this survey?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $survey_filter ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . $survey_filter . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $survey_filter ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $survey_filter . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $survey_filter ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No survey responses found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>
