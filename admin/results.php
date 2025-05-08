<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

// --- Validate and handle survey_id parameter ---
$survey_id = filter_input(INPUT_GET, 'survey_id', FILTER_VALIDATE_INT);
if (!$survey_id) {
    // If survey_id is not set or invalid, redirect to default survey (id=1)
    header("Location: results.php?survey_id=2");
    exit();
}

// Fetch survey details
$survey = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$survey->execute([$survey_id]);
$survey = $survey->fetch();

if (!$survey) {
    // If survey not found, redirect to default survey (id=1)
    $_SESSION['error'] = "Survey not found.";
    header("Location: results.php?survey_id=?");
    exit();
}

// Check if there are any responses for this survey
$responseCountStmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE survey_id = ?");
$responseCountStmt->execute([$survey_id]);
$responseCount = $responseCountStmt->fetchColumn();

if ($responseCount == 0) {
    // No responses yet, show message but do not redirect away from results page
    $no_responses = true;
} else {
    $no_responses = false;
}


// Fetch survey fields
$fields = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$fields->execute([$survey_id]);
$fields = $fields->fetchAll();

// Prepare date filter
$whereClause = "sr.survey_id = ?";
$params = [$survey_id];
$date_filter = '';

if (!empty($_GET['start_date'])) {
    $whereClause .= " AND sr.submitted_at >= ?";
    $params[] = $_GET['start_date'];
    $date_filter .= "&start_date=" . urlencode($_GET['start_date']);
}

if (!empty($_GET['end_date'])) {
    $whereClause .= " AND sr.submitted_at <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $date_filter .= "&end_date=" . urlencode($_GET['end_date']);
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

// Get paginated responses
$response_stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name
    FROM survey_responses sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
// DEBUG: Output SQL params for troubleshooting
// error_log('Params: ' . print_r(array_merge($params, [$per_page, $offset]), true));

$response_stmt->execute(array_merge($params, [$per_page, $offset]));
$responses = $response_stmt->fetchAll();

// DEBUG: Output number of responses fetched
// error_log('Fetched responses: ' . count($responses));

// --- BEGIN: Fetch all response_data for these responses ---
$response_ids = array_column($responses, 'id');
$response_data_map = [];
if (!empty($response_ids)) {
    $in = str_repeat('?,', count($response_ids) - 1) . '?';
    $data_stmt = $pdo->prepare("SELECT * FROM response_data WHERE response_id IN ($in)");
    $data_stmt->execute($response_ids);
    foreach ($data_stmt->fetchAll() as $row) {
        $response_id = $row['response_id'];
        $field_id = $row['field_id'];
        $value = $row['field_value'];
        // If checkbox, decode JSON
        foreach ($fields as $f) {
            if ($f['id'] == $field_id && $f['field_type'] === 'checkbox') {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? implode(', ', $decoded) : $value;
                break;
            }
        }
        $response_data_map[$response_id][$field_id] = $value;
    }
}
// --- END: Fetch all response_data for these responses ---

// Prepare analytics data for charts
$analytics = [];
foreach ($fields as $field) {
    if ($field['field_type'] === 'checkbox') {
        // Special handling for checkbox fields (stored as JSON arrays)
        $stmt = $pdo->prepare("SELECT field_value FROM response_data WHERE field_id = ?");
        $stmt->execute([$field['id']]);
        $all_values = [];
        
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $val) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                $all_values = array_merge($all_values, $decoded);
            } elseif ($val !== null) {
                $all_values[] = $val;
            }
        }
        
        $counts = array_count_values($all_values);
        arsort($counts);
        $analytics[$field['id']] = array_map(function($value, $count) {
            return ['field_value' => $value, 'count' => $count];
        }, array_keys($counts), $counts);
    } else {
        // Standard handling for other field types
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
}

// Prepare JSON data for JavaScript charts
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
    <!--
        Developer: Adugna Gizaw
        Email: gizawadugna@gmail.com
        LinkedIn: https://www.linkedin.com/in/eleganceict
        Twitter: https://twitter.com/eleganceict1
        GitHub: https://github.com/addex12
        Custom styles use adugna- prefix for patenting and ERPNext/frappe/Jinja2 inspiration.
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> Results - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="../assets/css/add_users.css">
    <style>
        /**
         * Developer: Adugna Gizaw
         * Custom adugna- styles for ERPNext/frappe/Jinja2-inspired compact UI.
         * All styles are responsive, compact, and screen-aware.
         * Layout is centered, visually outstanding, and user friendly.
         */

        body {
            background: #f6f7fb;
            min-height: 100vh;
        }

        .admin-dashboard {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            justify-content: center;
            align-items: flex-start;
        }

        .admin-main {
            flex: 1 1 0;
            max-width: 1100px;
            margin: 2.5vw auto;
            padding: 1.5vw 2vw;
            background: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .admin-header {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.2em;
        }
        .admin-header h1 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5em;
            text-align: center;
            letter-spacing: 0.01em;
        }
        .header-actions {
            display: flex;
            gap: 0.4em;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        /* Card styling */
        .adugna-card {
            background: #fff;
            border-radius: 7px;
            box-shadow: 0 2px 10px rgba(44,62,80,0.09);
            padding: 0.9em 1.1em;
            margin: 1em 0;
            width: 100%;
            max-width: 900px;
            transition: box-shadow 0.14s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card:hover, .adugna-card:focus-within {
            box-shadow: 0 4px 18px rgba(44,62,80,0.13);
        }

        /* Survey stats card row */
        .survey-stats {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 1em;
            width: 100%;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1 1 120px;
            min-width: 100px;
            background: #f7faff;
            border-radius: 5px;
            margin: 0.15em 0;
            padding: 0.6em 0.4em;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 1px 3px rgba(67,97,238,0.06);
            transition: background 0.13s;
        }
        .stat-card:hover {
            background: #eaf2fb;
        }
        .stat-value {
            font-size: 1.02rem;
            font-weight: 700;
            color: #4361ee;
            margin-bottom: 0.1em;
        }
        .stat-label {
            font-size: 0.72rem;
            color: #2c3e50;
            opacity: 0.8;
        }

        /* Button styling */
        .adugna-btn {
            background: #f4f7fa;
            color: #2c3e50;
            border: 1px solid #e3e6eb;
            padding: 0.16rem 0.52rem;
            border-radius: 3px;
            font-weight: 500;
            font-size: 0.78rem;
            transition: background 0.13s, color 0.13s, border 0.13s;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            min-height: 28px;
        }
        .adugna-btn-primary {
            background: #3498db;
            color: #fff;
            border-color: #3498db;
        }
        .adugna-btn-primary:hover, .adugna-btn-primary:focus {
            background: #217dbb;
            color: #fff;
        }
        .adugna-btn-secondary {
            background: #f4f7fa;
            color: #2c3e50;
            border-color: #e3e6eb;
        }
        .adugna-btn-secondary:hover, .adugna-btn-secondary:focus {
            background: #e2efda;
            color: #215967;
        }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
            border-color: #e74c3c;
        }
        .adugna-btn-danger:hover, .adugna-btn-danger:focus {
            background: #c0392b;
            color: #fff;
        }
        .adugna-btn-info {
            background: #00bcd4;
            color: #fff;
            border-color: #00bcd4;
        }
        .adugna-btn-info:hover, .adugna-btn-info:focus {
            background: #0097a7;
            color: #fff;
        }
        .adugna-btn-sm {
            font-size: 0.72rem;
            padding: 0.09rem 0.32rem;
        }

        /* Chart container */
        .adugna-chart-container {
            background: #fff;
            border-radius: 7px;
            padding: 0.7em 0.9em;
            margin-bottom: 13px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-chart-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 0.97rem;
            padding-bottom: 3px;
            border-bottom: 1px solid #f0f0f0;
            width: 100%;
            text-align: center;
        }

        /* Table styling */
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }
        .adugna-table th, .adugna-table td {
            padding: 5px 7px;
            text-align: center;
        }
        .adugna-table th {
            font-size: 0.78rem;
            font-weight: 600;
            background: #f7faff;
        }
        .adugna-table td {
            font-size: 0.72rem;
        }
        .adugna-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Badge styling */
        .adugna-badge {
            font-size: 0.58em;
            padding: 0.13em 0.28em;
            border-radius: 2px;
            background: #e3e6eb;
            color: #2c3e50;
        }

        /* Pagination */
        .adugna-pagination .page-link {
            padding: 0.15rem 0.32rem;
            font-size: 0.68rem;
        }

        /* Filter form */
        .adugna-filter-form {
            background: #fff;
            padding: 0.7em 0.9em;
            border-radius: 7px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 13px;
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-filter-form .row {
            width: 100%;
            justify-content: center;
        }
        .adugna-filter-form .form-group label {
            font-size: 0.72rem;
        }
        .adugna-filter-form .form-control {
            font-size: 0.72rem;
            padding: 0.13rem 0.32rem;
        }
        .adugna-filter-form .btn {
            font-size: 0.72rem;
            padding: 0.13rem 0.32rem;
        }

        /* Icon sizing */
        .adugna-icon, .adugna-btn i, .header-actions .dropdown-toggle i, .header-actions a i {
            font-size: 0.72rem !important;
        }

        /* Responsive tweaks */
        @media (max-width: 1100px) {
            .admin-main, .adugna-card, .adugna-chart-container, .adugna-filter-form {
                max-width: 98vw;
                padding-left: 2vw;
                padding-right: 2vw;
            }
        }
        @media (max-width: 900px) {
            .adugna-card, .adugna-chart-container, .adugna-filter-form {
                padding: 0.5em 0.3em;
            }
            .adugna-table th, .adugna-table td {
                font-size: 0.62rem;
            }
            .survey-stats {
                gap: 0.5em;
            }
        }
        @media (max-width: 700px) {
            .admin-main {
                padding: 1vw 0.5vw;
            }
            .adugna-card, .adugna-chart-container, .adugna-filter-form {
                padding: 0.3em 0.1em;
            }
            .survey-stats {
                flex-direction: column;
                align-items: center;
            }
            .stat-card {
                width: 100%;
                margin-bottom: 0.3em;
            }
        }
        @media (max-width: 600px) {
            .admin-main {
                padding: 0.5vw 0.2vw;
            }
            .adugna-card, .adugna-chart-container, .adugna-filter-form {
                padding: 0.18em 0.05em;
            }
            .adugna-table th, .adugna-table td {
                font-size: 0.58rem;
            }
            .header-actions {
                flex-direction: column;
                gap: 0.3em;
                align-items: center;
            }
        }
        /* Outstanding, interactive hover/focus for cards and buttons */
        .adugna-btn:active {
            transform: scale(0.97);
        }
        /**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
/* Adugna: All adugna- styles are compact, responsive, visually outstanding, and content/screen aware.
   Charts, bars, and Gantt charts are made attractive and friendly. Sidebar/footer styles are not touched. */
body {
    background: #f6f7fb;
    min-height: 100vh;
}
.admin-dashboard {
    display: flex;
    flex-direction: row;
    min-height: 100vh;
    justify-content: center;
    align-items: flex-start;
}
.admin-main {
    flex: 1 1 0;
    max-width: 1100px;
    margin: 2.5vw auto;
    padding: 1.5vw 2vw;
    background: transparent;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.admin-header {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.2em;
}
.admin-header h1 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5em;
    text-align: center;
    letter-spacing: 0.01em;
}
.header-actions {
    display: flex;
    gap: 0.4em;
    align-items: center;
    justify-content: center;
    width: 100%;
}

/* Adugna: Card styling for outstanding, compact look */
.adugna-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 24px rgba(44,62,80,0.10);
    padding: 1.2em 1.5em;
    margin: 1.2em 0;
    width: 100%;
    max-width: 900px;
    transition: box-shadow 0.18s;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.adugna-card:hover, .adugna-card:focus-within {
    box-shadow: 0 8px 32px rgba(44,62,80,0.13);
}

/* Adugna: Survey stats card row */
.survey-stats {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 1em;
    width: 100%;
    flex-wrap: wrap;
}
.stat-card {
    flex: 1 1 120px;
    min-width: 100px;
    background: linear-gradient(135deg, #f7faff 60%, #eaf2fb 100%);
    border-radius: 7px;
    margin: 0.15em 0;
    padding: 0.7em 0.5em;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 1px 4px rgba(67,97,238,0.09);
    transition: background 0.13s, box-shadow 0.13s;
}
.stat-card:hover {
    background: #eaf2fb;
    box-shadow: 0 2px 8px rgba(67,97,238,0.13);
}
.stat-value {
    font-size: 1.08rem;
    font-weight: 700;
    color: #4361ee;
    margin-bottom: 0.1em;
}
.stat-label {
    font-size: 0.72rem;
    color: #2c3e50;
    opacity: 0.8;
}

/* Adugna: Button styling, compact and consistent */
.adugna-btn {
    background: #f4f7fa;
    color: #2c3e50;
    border: 1px solid #e3e6eb;
    padding: 0.16rem 0.52rem;
    border-radius: 3px;
    font-weight: 500;
    font-size: 0.78rem;
    transition: background 0.13s, color 0.13s, border 0.13s;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.2em;
    min-height: 28px;
}
.adugna-btn-primary {
    background: linear-gradient(90deg, #3498db 60%, #4361ee 100%);
    color: #fff;
    border-color: #3498db;
}
.adugna-btn-primary:hover, .adugna-btn-primary:focus {
    background: #217dbb;
    color: #fff;
}
.adugna-btn-secondary {
    background: #f4f7fa;
    color: #2c3e50;
    border-color: #e3e6eb;
}
.adugna-btn-secondary:hover, .adugna-btn-secondary:focus {
    background: #e2efda;
    color: #215967;
}
.adugna-btn-danger {
    background: #e74c3c;
    color: #fff;
    border-color: #e74c3c;
}
.adugna-btn-danger:hover, .adugna-btn-danger:focus {
    background: #c0392b;
    color: #fff;
}
.adugna-btn-info {
    background: #00bcd4;
    color: #fff;
    border-color: #00bcd4;
}
.adugna-btn-info:hover, .adugna-btn-info:focus {
    background: #0097a7;
    color: #fff;
}
.adugna-btn-sm {
    font-size: 0.72rem;
    padding: 0.09rem 0.32rem;
}

/* Adugna: Chart container - outstanding, content/screen aware */
.adugna-chart-container {
    background: #fff;
    border-radius: 10px;
    padding: 1em 1.2em;
    margin-bottom: 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.09);
    width: 100%;
    max-width: 900px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.adugna-chart-title {
    margin-top: 0;
    color: #2c3e50;
    font-size: 1.01rem;
    padding-bottom: 4px;
    border-bottom: 1px solid #f0f0f0;
    width: 100%;
    text-align: center;
    font-weight: 700;
    letter-spacing: 0.01em;
}

/* Adugna: Outstanding, interactive chart canvas */
.adugna-chart-container canvas {
    width: 100% !important;
    max-width: 100% !important;
    height: 220px !important;
    margin: 0 auto;
    display: block;
    background: #f8fafc;
    border-radius: 7px;
    box-shadow: 0 1px 4px rgba(67,97,238,0.06);
}

/* Adugna: Table styling */
.adugna-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
}
.adugna-table th, .adugna-table td {
    padding: 5px 7px;
    text-align: center;
}
.adugna-table th {
    font-size: 0.78rem;
    font-weight: 600;
    background: #f7faff;
}
.adugna-table td {
    font-size: 0.72rem;
}
.adugna-table tr:hover {
    background-color: #f8f9fa;
}
.adugna-badge {
    font-size: 0.58em;
    padding: 0.13em 0.28em;
    border-radius: 2px;
    background: #e3e6eb;
    color: #2c3e50;
}
.adugna-pagination .page-link {
    padding: 0.15rem 0.32rem;
    font-size: 0.68rem;
}
.adugna-filter-form {
    background: #fff;
    padding: 0.7em 0.9em;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.09);
    margin-bottom: 13px;
    width: 100%;
    max-width: 900px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.adugna-filter-form .row {
    width: 100%;
    justify-content: center;
}
.adugna-filter-form .form-group label {
    font-size: 0.72rem;
}
.adugna-filter-form .form-control {
    font-size: 0.72rem;
    padding: 0.13rem 0.32rem;
}
.adugna-filter-form .btn {
    font-size: 0.72rem;
    padding: 0.13rem 0.32rem;
}
.adugna-icon, .adugna-btn i, .header-actions .dropdown-toggle i, .header-actions a i {
    font-size: 0.72rem !important;
    vertical-align: middle;
}
@media (max-width: 1100px) {
    .admin-main, .adugna-card, .adugna-chart-container, .adugna-filter-form {
        max-width: 98vw;
        padding-left: 2vw;
        padding-right: 2vw;
    }
}
@media (max-width: 900px) {
    .adugna-card, .adugna-chart-container, .adugna-filter-form {
        padding: 0.5em 0.3em;
    }
    .adugna-table th, .adugna-table td {
        font-size: 0.62rem;
    }
    .survey-stats {
        gap: 0.5em;
    }
    .adugna-chart-container canvas {
        height: 160px !important;
    }
}
@media (max-width: 700px) {
    .admin-main {
        padding: 1vw 0.5vw;
    }
    .adugna-card, .adugna-chart-container, .adugna-filter-form {
        padding: 0.3em 0.1em;
    }
    .survey-stats {
        flex-direction: column;
        align-items: center;
    }
    .stat-card {
        width: 100%;
        margin-bottom: 0.3em;
    }
    .adugna-chart-container canvas {
        height: 120px !important;
    }
}
@media (max-width: 600px) {
    .admin-main {
        padding: 0.5vw 0.2vw;
    }
    .adugna-card, .adugna-chart-container, .adugna-filter-form {
        padding: 0.18em 0.05em;
    }
    .adugna-table th, .adugna-table td {
        font-size: 0.58rem;
    }
    .header-actions {
        flex-direction: column;
        gap: 0.3em;
        align-items: center;
    }
    .adugna-chart-container canvas {
        height: 90px !important;
    }
}
.adugna-btn:active {
    transform: scale(0.97);
}
@media print {
    body.adugna-print-mode {
        background: #fff !important;
    }
    body.adugna-print-mode .admin-dashboard {
        display: block !important;
    }
    body.adugna-print-mode .admin-sidebar,
    body.adugna-print-mode .admin-sidebar *,
    body.adugna-print-mode .header-actions,
    body.adugna-print-mode .adugna-btn,
    body.adugna-print-mode .dropdown-menu,
    body.adugna-print-mode .dropdown-toggle {
        display: none !important;
        visibility: hidden !important;
    }
    body.adugna-print-mode .admin-main {
        margin: 0 auto !important;
        padding: 0 !important;
        max-width: 210mm !important;
        width: 210mm !important;
        background: #fff !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    body.adugna-print-mode .adugna-card,
    body.adugna-print-mode .adugna-chart-container,
    body.adugna-print-mode .adugna-filter-form {
        box-shadow: none !important;
        border: none !important;
        background: #fff !important;
        page-break-inside: avoid !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: visible !important;
        margin-bottom: 12mm !important;
    }
    body.adugna-print-mode .survey-stats,
    body.adugna-print-mode .filter-section,
    body.adugna-print-mode .chart-section,
    body.adugna-print-mode .response-table-section {
        page-break-after: always !important;
        break-after: page !important;
    }
    body.adugna-print-mode .response-table-section {
        page-break-after: auto !important;
        break-after: auto !important;
    }
    body.adugna-print-mode .adugna-table {
        table-layout: fixed !important;
        width: 100% !important;
        word-break: break-word !important;
    }
    body.adugna-print-mode .adugna-table th,
    body.adugna-print-mode .adugna-table td {
        color: #222 !important;
        background: #fff !important;
        border: 1px solid #eee !important;
        box-sizing: border-box !important;
        overflow: visible !important;
        word-break: break-word !important;
        white-space: pre-line !important;
        max-width: 180px !important;
        font-size: 10pt !important;
    }
    body.adugna-print-mode .adugna-table th {
        font-size: 11pt !important;
    }
    body.adugna-print-mode .adugna-table tr, 
    body.adugna-print-mode .adugna-table td, 
    body.adugna-print-mode .adugna-table th {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    /* Ensure charts are not cut off */
    body.adugna-print-mode .adugna-chart-container canvas {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        display: block !important;
        margin: 0 auto !important;
        max-width: 100% !important;
        height: auto !important;
    }
}
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <!--
                Developer: Adugna Gizaw
                Header with compact, responsive actions and adugna- styles.
            -->
            <header class="admin-header">
                <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <!-- Survey Selector Dropdown -->
                <form id="adugna-survey-selector-form" method="get" style="margin-bottom:1em;display:flex;justify-content:center;align-items:center;gap:0.7em;">
                    <label for="adugna-survey-selector" style="font-size:0.92em;font-weight:600;">Select Survey:</label>
                    <select id="adugna-survey-selector" name="survey_id" class="adugna-btn adugna-btn-secondary" style="min-width:180px;">
                        <?php
                        // Developer: Adugna Gizaw - Fetch all surveys for selector (AJAX could be used for large data)
                        $allSurveys = $pdo->query("SELECT id, title FROM surveys ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($allSurveys as $s) {
                            $selected = $s['id'] == $survey_id ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($s['id']).'" '.$selected.'>'.htmlspecialchars($s['title']).'</option>';
                        }
                        ?>
                    </select>
                </form>
                <script>
                    // Developer: Change survey on selector change
                    document.getElementById('adugna-survey-selector').addEventListener('change', function() {
                        document.getElementById('adugna-survey-selector-form').submit();
                    });
                </script>
                <!-- Developer: Place Export, Print, and Back buttons in separate divs for better alignment and attractiveness -->
                <div class="header-actions adugna-header-actions-flex">
                    <div class="adugna-header-action">
                        <div class="dropdown">
                            <button class="adugna-btn adugna-btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-download adugna-icon"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export_csv.php?survey_id=<?= $survey_id ?>"><i class="fas fa-file-csv adugna-icon"></i> CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="export-pdf"><i class="fas fa-file-pdf adugna-icon"></i> PDF</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="adugna-header-action">
                        <button type="button" class="adugna-btn adugna-btn-secondary" id="adugna-print-btn">
                            <i class="fas fa-print adugna-icon"></i> Print
                        </button>
                    </div>
                    <div class="adugna-header-action">
                        <a href="surveys.php" class="adugna-btn adugna-btn-secondary">
                            <i class="fas fa-arrow-left adugna-icon"></i> Back
                        </a>
                    </div>
                </div>
            </header>

            <!-- Survey Stats Cards -->
            <div class="survey-stats mb-4 adugna-card" style="display:flex;gap:1em;flex-wrap:wrap;">
                <!-- Developer: Compact stat cards, responsive -->
                <div class="stat-card" style="flex:1;min-width:120px;">
                    <div class="stat-value" style="font-size:1.1rem;"><?= number_format($total_responses) ?></div>
                    <div class="stat-label adugna-badge">Total Responses</div>
                </div>
                <div class="stat-card" style="flex:1;min-width:120px;">
                    <div class="stat-value" style="font-size:1.1rem;"><?= date('M j, Y', strtotime($survey['starts_at'])) ?></div>
                    <div class="stat-label adugna-badge">Start Date</div>
                </div>
                <div class="stat-card" style="flex:1;min-width:120px;">
                    <div class="stat-value" style="font-size:1.1rem;"><?= date('M j, Y', strtotime($survey['ends_at'])) ?></div>
                    <div class="stat-label adugna-badge">End Date</div>
                </div>
                <div class="stat-card" style="flex:1;min-width:120px;">
                    <div class="stat-value" style="font-size:1.1rem;"><?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></div>
                    <div class="stat-label adugna-badge">Anonymous</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <!-- Developer: Filter form with adugna- styles, responsive -->
                <form method="GET" class="adugna-filter-form adugna-card">
                    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
                    <div class="row" style="display:flex;flex-wrap:wrap;gap:0.5em;">
                        <div class="col-md-5" style="flex:1;min-width:140px;">
                            <div class="form-group">
                                <label for="start_date">From Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-5" style="flex:1;min-width:140px;">
                            <div class="form-group">
                                <label for="end_date">To Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end" style="display:flex;align-items:end;gap:0.3em;">
                            <button type="submit" class="adugna-btn adugna-btn-primary adugna-btn-sm mr-2">
                                <i class="fas fa-filter adugna-icon"></i> Filter
                            </button>
                            <a href="results.php?survey_id=<?= $survey_id ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm">
                                <i class="fas fa-sync-alt adugna-icon"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="chart-section mb-5">
                <div class="row">
                    <div class="col-12">
                        <div class="adugna-chart-container adugna-card" style="max-width:600px;margin:auto;">
                            <h3 class="adugna-chart-title">Response Summary</h3>
                            <canvas id="summaryChart" height="180" style="max-height:220px;max-width:100%;"></canvas>
                        </div>
                    </div>
                </div>
                <?php foreach ($fields as $field): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="adugna-chart-container adugna-card" style="max-width:600px;margin:auto;">
                                <h3 class="adugna-chart-title"><?= htmlspecialchars($field['field_label']) ?></h3>
                                <canvas id="fieldChart-<?= $field['id'] ?>" height="180" style="max-height:220px;max-width:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Additional Analysis Tools Section -->
            <div class="adugna-card" style="max-width:900px;margin:1.5em auto;">
                <h3 class="adugna-chart-title"><i class="fas fa-chart-area adugna-icon"></i> Advanced Analysis Tools</h3>
                <div class="row" style="display:flex;flex-wrap:wrap;gap:1em;">
                    <div class="col-md-6" style="flex:1;min-width:320px;">
                        <div class="adugna-chart-container" style="margin-bottom:0;">
                            <h4 class="adugna-chart-title" style="font-size:0.98em;"><i class="fas fa-chart-pie adugna-icon"></i> Response Distribution</h4>
                            <canvas id="adugna-pie-distribution" height="180"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6" style="flex:1;min-width:320px;">
                        <div class="adugna-chart-container" style="margin-bottom:0;">
                            <h4 class="adugna-chart-title" style="font-size:0.98em;"><i class="fas fa-chart-gantt adugna-icon"></i> Submission Timeline (Gantt)</h4>
                            <canvas id="adugna-gantt-timeline" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responses Table -->
            <div class="response-table-section adugna-card">
                <h3 class="mb-3" style="font-size:1rem;">Individual Responses</h3>
                <?php if ($total_responses > 0): ?>
                    <div class="table-responsive">
                        <table class="adugna-table" style="width:100%;">
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
                                                <span class="text-muted">Anonymous</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($response['username'] ?? 'N/A') ?>
                                                <?php if ($response['email']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($response['email']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                                        <?php foreach ($fields as $field): 
                                            $val = $response_data_map[$response['id']][$field['id']] ?? null;
                                        ?>
                                            <td>
                                                <?= $val !== null && $val !== '' ? 
                                                    htmlspecialchars($val) : 
                                                    '<span class="text-muted">N/A</span>' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                        <td>
                                            <a href="response_view.php?id=<?= $response['id'] ?>" class="adugna-btn adugna-btn-info adugna-btn-sm">
                                                <i class="fas fa-eye adugna-icon"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="adugna-pagination pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?><?= $date_filter ?>">
                                        <i class="fas fa-chevron-left adugna-icon"></i> Prev
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?survey_id='.$survey_id.'&page=1'.$date_filter.'">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $i ?><?= $date_filter ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; 
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?survey_id='.$survey_id.'&page='.$total_pages.$date_filter.'">'.$total_pages.'</a></li>';
                            }
                            ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page + 1 ?><?= $date_filter ?>">
                                        Next <i class="fas fa-chevron-right adugna-icon"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle adugna-icon"></i> No responses found for this survey. Please check back later.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body> 
</html>
<!--
    Developer: Adugna Gizaw
    JS for charts, PDF export, print, and interactivity. All code is commented for clarity.
-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script> 
<script>
    // Developer: Adugna Gizaw
    // Pass PHP data to JavaScript for charts
    const chartData = <?= $chart_json ?>;

    // Developer: Initialize charts on DOM load
    document.addEventListener('DOMContentLoaded', function () {
        // Summary chart - Response trend over time
        if (chartData.total_responses > 0) {
            const ctx = document.getElementById('summaryChart').getContext('2d');
            const labels = chartData.fields.map(field => field.field_label);
            const data = chartData.fields.map(field => {
                const fieldAnalytics = chartData.analytics[field.id] || [];
                return fieldAnalytics.reduce((sum, item) => sum + item.count, 0);
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Responses',
                        data: data,
                        backgroundColor: 'rgba(67, 97, 238, 0.18)',
                        borderColor: '#4361ee',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#4895ef',
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBorderWidth: 2,
                        pointBorderColor: '#fff',
                        shadowOffsetX: 2,
                        shadowOffsetY: 2,
                        shadowBlur: 6,
                        shadowColor: 'rgba(67,97,238,0.18)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#4361ee',
                            bodyColor: '#222',
                            borderColor: '#4361ee',
                            borderWidth: 1,
                            callbacks: {
                                label: ctx => `Responses: ${ctx.raw}`
                            }
                        }
                    },
                    layout: {
                        padding: 10
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(67,97,238,0.07)'
                            },
                            ticks: {
                                color: '#4361ee',
                                font: { size: 11 }
                            }
                        },
                        x: {
                            title: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#222',
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // Field-specific charts
        chartData.fields.forEach(field => {
            const fieldAnalytics = chartData.analytics[field.id] || [];
            const ctx = document.getElementById(`fieldChart-${field.id}`).getContext('2d');

            if (fieldAnalytics.length > 0) {
                const labels = fieldAnalytics.map(item => item.field_value);
                const data = fieldAnalytics.map(item => item.count);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Responses',
                            data: data,
                            backgroundColor: [
                                '#4895ef', '#4361ee', '#3f37c9', '#f72585', '#b5179e', '#7209b7', '#560bad', '#480ca8'
                            ],
                            borderWidth: 1,
                            borderRadius: 6,
                            hoverBackgroundColor: '#f72585'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: false
                            },
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#fff',
                                titleColor: '#4361ee',
                                bodyColor: '#222',
                                borderColor: '#4361ee',
                                borderWidth: 1,
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.raw}`
                                }
                            }
                        },
                        layout: {
                            padding: 10
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(67,97,238,0.07)'
                                },
                                ticks: {
                                    color: '#4361ee',
                                    font: { size: 11 }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#222',
                                    font: { size: 11 }
                                }
                            }
                        }
                    }
                });
            } else {
                ctx.canvas.parentNode.innerHTML += '<div class="alert alert-info mt-3"><i class="fas fa-info-circle adugna-icon"></i> No response data available for this question.</div>';
            }
        });
    });

    // Developer: PDF Export functionality
    document.addEventListener('DOMContentLoaded', function() {
        var exportBtn = document.getElementById('export-pdf');
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                exportResultsToPDF();
            });
        }
    });

    // Developer: Export results to PDF using html2canvas and jsPDF
    function exportResultsToPDF() {
        var content = document.querySelector('.admin-main');
        if (!content) return;
        var dropdown = document.querySelector('.dropdown');
        if (dropdown) dropdown.style.display = 'none';

        // Adugna Gizaw: Improved PDF export - ensure table and charts fit page, scale content if needed
        html2canvas(content, {
            scale: 2,
            useCORS: true,
            scrollY: -window.scrollY,
            windowWidth: document.body.scrollWidth,
            backgroundColor: "#fff"
        }).then(function(canvas) {
            var pdf = new window.jspdf.jsPDF('p', 'pt', 'a4');
            var pageWidth = pdf.internal.pageSize.getWidth();
            var pageHeight = pdf.internal.pageSize.getHeight();
            var imgWidth = pageWidth - 40;
            var imgHeight = canvas.height * imgWidth / canvas.width;

            // If content is too wide, scale down to fit
            if (canvas.width > pageWidth) {
                imgWidth = pageWidth - 40;
                imgHeight = canvas.height * imgWidth / canvas.width;
            }

            // Calculate the height of one PDF page in canvas pixels
            var pageCanvasHeight = Math.floor(canvas.width * (pageHeight - 40) / imgWidth);

            var renderedHeight = 0;
            var pageNum = 0;

            while (renderedHeight < canvas.height) {
                // Create a canvas for the current page slice
                var pageCanvas = document.createElement('canvas');
                pageCanvas.width = canvas.width;
                // Make sure not to exceed the remaining height
                var sliceHeight = Math.min(pageCanvasHeight, canvas.height - renderedHeight);
                pageCanvas.height = sliceHeight;
                var pageCtx = pageCanvas.getContext('2d');
                // Draw the current slice
                pageCtx.drawImage(canvas, 0, renderedHeight, canvas.width, sliceHeight, 0, 0, canvas.width, sliceHeight);
                var pageImgData = pageCanvas.toDataURL('image/png');
                // Add to PDF
                if (pageNum > 0) pdf.addPage();
                // Calculate the height in PDF units for this slice
                var pdfImgHeight = sliceHeight * imgWidth / canvas.width;
                pdf.addImage(pageImgData, 'PNG', 20, 20, imgWidth, pdfImgHeight);
                renderedHeight += sliceHeight;
                pageNum++;
            }
            pdf.save('survey_results.pdf');
            if (dropdown) dropdown.style.display = '';
        });
    }

    // Developer: Adugna Gizaw
    // Print button logic - hides sidebar and prints only the main content, page-breaks for sections
    document.addEventListener('DOMContentLoaded', function() {
        var printBtn = document.getElementById('adugna-print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                // Hide sidebar and show only main content for printing
                document.body.classList.add('adugna-print-mode');
                // Set up A4 page size for print
                var style = document.createElement('style');
                style.id = 'adugna-print-a4-style';
                style.innerHTML = `
                    @media print {
                        @page {
                            size: A4 portrait;
                            margin: 12mm 8mm 12mm 8mm;
                        }
                        .admin-main, .adugna-card, .adugna-chart-container, .adugna-filter-form {
                            width: 210mm !important;
                            max-width: 210mm !important;
                        }
                    }
                `;
                document.head.appendChild(style);
                window.print();
                // After print, restore
                window.onafterprint = function() {
                    document.body.classList.remove('adugna-print-mode');
                    var s = document.getElementById('adugna-print-a4-style');
                    if (s) s.remove();
                };
            });
        }
    });

    // Developer: Adugna Gizaw - Advanced Analysis Tools

    document.addEventListener('DOMContentLoaded', function () {
        // Pie Chart: Response Distribution by Field (first field as example)
        if (chartData.fields.length > 0) {
            const field = chartData.fields[0];
            const analytics = chartData.analytics[field.id] || [];
            const ctxPie = document.getElementById('adugna-pie-distribution').getContext('2d');
            if (analytics.length > 0) {
                new Chart(ctxPie, {
                    type: 'pie',
                    data: {
                        labels: analytics.map(item => item.field_value),
                        datasets: [{
                            data: analytics.map(item => item.count),
                            backgroundColor: [
                                '#4895ef', '#4361ee', '#3f37c9', '#f72585', '#b5179e', '#7209b7', '#560bad', '#480ca8'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true, position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.raw}`
                                }
                            }
                        }
                    }
                });
            }
        }

        // Gantt Chart: Submission Timeline (using Chart.js horizontal bar as Gantt)
        // Developer: For demo, group responses by day and show as bars
        <?php
        // Prepare Gantt data (submissions per day)
        $ganttData = [];
        $ganttStmt = $pdo->prepare("SELECT DATE(submitted_at) as day, COUNT(*) as count FROM survey_responses WHERE survey_id = ? GROUP BY day ORDER BY day ASC");
        $ganttStmt->execute([$survey_id]);
        foreach ($ganttStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ganttData[] = $row;
        }
        ?>
        const ganttLabels = <?= json_encode(array_column($ganttData, 'day')) ?>;
        const ganttCounts = <?= json_encode(array_column($ganttData, 'count')) ?>;
        const ctxGantt = document.getElementById('adugna-gantt-timeline').getContext('2d');
        if (ganttLabels.length > 0) {
            new Chart(ctxGantt, {
                type: 'bar',
                data: {
                    labels: ganttLabels,
                    datasets: [{
                        label: 'Submissions',
                        data: ganttCounts,
                        backgroundColor: '#4895ef',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `Submissions: ${ctx.raw}`
                            }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: 'rgba(67,97,238,0.07)' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        // Example: Add more analysis tools here (AJAX, JSON, etc.)
        // You can fetch more data via AJAX and render more charts as needed.
    });
</script>
<style>
/**
 * Developer: Adugna Gizaw
 * Print styles: Hide sidebar, center main, add page breaks for major sections, and fit to A4.
 * Fix for content cutoff at bottom of PDF/print by using better page-break and box model rules.
 */
@media print {
    body.adugna-print-mode {
        background: #fff !important;
    }
    body.adugna-print-mode .admin-dashboard {
        display: block !important;
    }
    body.adugna-print-mode .admin-sidebar,
    body.adugna-print-mode .admin-sidebar *,
    body.adugna-print-mode .header-actions,
    body.adugna-print-mode .adugna-btn,
    body.adugna-print-mode .dropdown-menu,
    body.adugna-print-mode .dropdown-toggle {
        display: none !important;
        visibility: hidden !important;
    }
    body.adugna-print-mode .admin-main {
        margin: 0 auto !important;
        padding: 0 !important;
        max-width: 210mm !important;
        width: 210mm !important;
        background: #fff !important;
        box-shadow: none !important;
        /* Prevent content from being cut off at page bottom */
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    body.adugna-print-mode .adugna-card,
    body.adugna-print-mode .adugna-chart-container,
    body.adugna-print-mode .adugna-filter-form {
        box-shadow: none !important;
        border: none !important;
        background: #fff !important;
        page-break-inside: avoid !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: visible !important;
        /* Add margin to avoid cutoff at page bottom */
        margin-bottom: 12mm !important;
    }
    body.adugna-print-mode .survey-stats,
    body.adugna-print-mode .filter-section,
    body.adugna-print-mode .chart-section,
    body.adugna-print-mode .response-table-section {
        page-break-after: always !important;
        break-after: page !important;
    }
    body.adugna-print-mode .response-table-section {
        page-break-after: auto !important;
        break-after: auto !important;
    }
    body.adugna-print-mode .adugna-table th,
    body.adugna-print-mode .adugna-table td {
        color: #222 !important;
        background: #fff !important;
        border: 1px solid #eee !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    /* Prevent table rows from being split across pages */
    body.adugna-print-mode tr, 
    body.adugna-print-mode td, 
    body.adugna-print-mode th {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
<script src="../assets/js/results-export.js"></script>
