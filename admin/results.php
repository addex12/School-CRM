<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// No output before redirects!
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

// Pagination
$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Get total responses
$stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses sr WHERE $whereClause");
$stmt->execute($params);
$total_responses = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_responses / $per_page));

// Get paginated responses with user data
$stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name
    FROM survey_responses sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$params2 = array_merge($params, [$per_page, $offset]);
$stmt->execute($params2);
$responses = $stmt->fetchAll();

// Prepare analytics data for charts
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
    <style>
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .survey-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #ddd;
        }
    </style>
</head>
<body>
    <?