<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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
    <!-- Adugna Gizaw: Responsive, compact, ERPNext-inspired all responses page with adugna- prefix -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }

        .adugna-main {
            min-height: 100vh;
            background: #f7f9fb;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .adugna-content-container {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 20px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
            letter-spacing: 0.01em;
        }
        .adugna-card-body {
            font-size: 0.97em;
            color: #444;
        }
        .adugna-response-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-response-table th, .adugna-response-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-response-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-response-table tr:hover {
            background-color: #f8f9fa;
        }
        .adugna-filter-form {
            background: #fff;
            padding: 1rem 1.2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .adugna-filter-form label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
            margin-bottom: 2px;
        }
        .adugna-filter-form select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 4px 12px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-btn-warning {
            background: #ffc107;
            color: #fff;
            border: 1px solid #ffc107;
        }
        .adugna-btn-warning:hover {
            background: #e0a800;
        }
        .adugna-btn-danger {
            background: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .adugna-btn-danger:hover {
            background: #c82333;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
            gap: 2px;
        }
        .adugna-page-item.active .adugna-page-link {
            background-color: #1976d2;
            border-color: #1976d2;
            color: white;
        }
        .adugna-page-link {
            display: block;
            padding: 0.4rem 0.6rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #1976d2;
            background-color: #fff;
            border: 1px solid #dee2e6;
            text-decoration: none;
            font-size: 0.95em;
            border-radius: 3px;
            transition: background 0.15s;
        }
        .adugna-page-link:hover {
            background-color: #e9ecef;
            color: #1976d2;
        }
        .adugna-alert-info {
            background: #f5f7fa;
            color: #1976d2;
            border: 1px solid #e3eafc;
            border-radius: 5px;
            padding: 12px 16px;
            margin: 18px 0;
            font-size: 0.98em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 1100px) {
            .adugna-content-container {
                max-width: 99vw;
                margin: 18px 2vw 0 2vw;
                padding: 10px 4px 18px 4px;
            }
        }
        @media (max-width: 900px) {
            .adugna-card, .adugna-filter-form, .adugna-content-container {
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
            .adugna-response-table th, .adugna-response-table td {
                padding: 5px 4px;
                font-size: 0.95em;
            }
        }
        @media (max-width: 600px) {
            .adugna-card, .adugna-filter-form, .adugna-content-container {
                padding: 0.5rem 0.2rem 0.7rem 0.2rem;
            }
            .adugna-response-table th, .adugna-response-table td {
                padding: 4px 2px;
                font-size: 0.93em;
            }
            .adugna-header-title {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <!-- Adugna Gizaw: Main admin dashboard layout, content and screen size aware -->
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-content-container">
                <header class="adugna-header-title">
                    <?= htmlspecialchars($pageTitle) ?>
                </header>
                <!-- Adugna Gizaw: Filter form for survey selection -->
                <div class="adugna-filter-section">
                    <form method="GET" class="adugna-filter-form">
                        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                            <div>
                                <label for="survey_id">Filter by Survey</label>
                                <select name="survey_id" id="survey_id">
                                    <option value="">All Surveys</option>
                                    <?php foreach ($allSurveys as $surveyOption): ?>
                                        <option value="<?= htmlspecialchars($surveyOption['id']) ?>" <?= ($filter_survey_id == $surveyOption['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($surveyOption['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="adugna-btn"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($total_responses > 0): ?>
                    <!-- Adugna Gizaw: Card for survey responses table -->
                    <div class="adugna-card">
                        <div class="adugna-card-header">Survey Responses</div>
                        <div class="adugna-card-body">
                            <table class="adugna-response-table">
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
                                                <a href="response_view.php?id=<?= $response['id'] ?>" class="adugna-btn adugna-btn-sm adugna-btn-secondary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="results.php?survey_id=<?= $response['survey_id'] ?>" class="adugna-btn adugna-btn-sm" title="Results">
                                                    <i class="bi bi-arrow-left"></i>
                                                </a>
                                                <a href="edit_survey.php?id=<?= $response['survey_id'] ?>" class="adugna-btn adugna-btn-sm adugna-btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete_survey.php?id=<?= $response['survey_id'] ?>" class="adugna-btn adugna-btn-sm adugna-btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this survey?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Adugna Gizaw: Pagination for survey responses -->
                    <nav class="mt-4">
                        <ul class="adugna-pagination" style="justify-content:center;">
                            <?php if ($page > 1): ?>
                                <li class="adugna-page-item">
                                    <a class="adugna-page-link" href="?page=<?= $page - 1 ?><?= $survey_filter ?>">
                                        <i class="fas fa-chevron-left"></i> Prev
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="adugna-page-item"><a class="adugna-page-link" href="?page=1' . $survey_filter . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="adugna-page-item disabled"><span class="adugna-page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="adugna-page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="adugna-page-link" href="?page=<?= $i ?><?= $survey_filter ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="adugna-page-item disabled"><span class="adugna-page-link">...</span></li>';
                                }
                                echo '<li class="adugna-page-item"><a class="adugna-page-link" href="?page=' . $total_pages . $survey_filter . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="adugna-page-item">
                                    <a class="adugna-page-link" href="?page=<?= $page + 1 ?><?= $survey_filter ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <!-- Adugna Gizaw: Info alert if no responses found -->
                    <div class="adugna-alert-info">
                        <i class="fas fa-info-circle"></i> No survey responses found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>
