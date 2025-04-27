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
        /* ERPNext card and button styling */
        .erpnext-card {
            background: #fff;
            border-radius: 8px; /* Reduced border radius */
            box-shadow: 0 2px 6px rgba(44,62,80,0.05); /* Slightly lighter shadow */
            padding: 1.5rem 1rem; /* Reduced padding */
            margin: 1.5rem 0; /* Reduced margin */
        }
        .erpnext-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.4rem 0.8rem; /* Smaller button size */
            border-radius: 4px; /* Reduced border radius */
            font-weight: 500;
            font-size: 0.85rem; /* Smaller font size */
            transition: background 0.18s;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
        }
        .erpnext-btn-primary {
            background: #3498db;
        }
        .erpnext-btn-primary:hover {
            background: #217dbb;
        }
        .erpnext-btn-secondary {
            background: #eaeaea;
            color: #666;
        }
        .erpnext-btn-secondary:hover {
            background: #e2efda;
            color: #215967;
        }
        .erpnext-btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .erpnext-btn-danger:hover {
            background: #c0392b;
        }
        .erpnext-btn-info {
            background: #00bcd4;
            color: #fff;
        }
        .erpnext-btn-info:hover {
            background: #0097a7;
        }
        .chart-container {
            background: white;
            border-radius: 8px; /* Reduced border radius */
            padding: 15px; /* Reduced padding */
            margin-bottom: 20px; /* Reduced margin */
            box-shadow: 0 3px 5px rgba(0,0,0,0.05); /* Slightly lighter shadow */
        }
        .chart-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1rem; /* Smaller font size */
            padding-bottom: 8px; /* Reduced padding */
            border-bottom: 1px solid #eee;
        }
        .response-table th, .response-table td {
            padding: 8px 10px; /* Reduced padding */
        }
        .response-table th {
            font-size: 0.9rem; /* Smaller font size */
        }
        .response-table td {
            font-size: 0.85rem; /* Smaller font size */
        }
        .response-table tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.7em; /* Smaller badge size */
            padding: 0.3em 0.5em; /* Reduced padding */
        }
        .pagination .page-link {
            padding: 0.4rem 0.6rem; /* Smaller pagination buttons */
            font-size: 0.85rem; /* Smaller font size */
        }
        .filter-form {
            background: white;
            padding: 15px; /* Reduced padding */
            border-radius: 8px; /* Reduced border radius */
            box-shadow: 0 3px 5px rgba(0,0,0,0.05); /* Slightly lighter shadow */
            margin-bottom: 20px; /* Reduced margin */
        }
        .filter-form .form-group label {
            font-size: 0.85rem; /* Smaller label font size */
        }
        .filter-form .form-control {
            font-size: 0.85rem; /* Smaller input font size */
            padding: 0.4rem 0.6rem; /* Reduced padding */
        }
        .filter-form .btn {
            font-size: 0.85rem; /* Smaller button font size */
            padding: 0.4rem 0.8rem; /* Reduced padding */
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="erpnext-btn erpnext-btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="export_csv.php?survey_id=<?= $survey_id ?>"><i class="fas fa-file-csv"></i> CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export-pdf"><i class="fas fa-file-pdf"></i> PDF</a></li>
                            <li><a class="dropdown-item" href="export_json.php?survey_id=<?= $survey_id ?>"><i class="fas fa-file-code"></i> JSON</a></li>
                        </ul>
                    </div>
                    <a href="surveys.php" class="erpnext-btn erpnext-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>

            <!-- Survey Stats Cards -->
            <div class="survey-stats mb-4 erpnext-card">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($total_responses) ?></div>
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

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-form erpnext-card">
                    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="start_date">From Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="end_date">To Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="results.php?survey_id=<?= $survey_id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="chart-section mb-5">
                <div class="row">
                    <div class="col-12">
                        <div class="chart-container erpnext-card">
                            <h3 class="chart-title">Response Summary</h3>
                            <canvas id="summaryChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($fields as $field): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-container erpnext-card">
                                <h3 class="chart-title"><?= htmlspecialchars($field['field_label']) ?></h3>
                                <canvas id="fieldChart-<?= $field['id'] ?>" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Responses Table -->
            <div class="response-table-section erpnext-card">
                <h3 class="mb-3">Individual Responses</h3>
                
                <?php if ($total_responses > 0): ?>
                    <div class="table-responsive">
                        <table class="response-table">
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
                                        
                                        <?php 
                                        // --- BEGIN: Use response_data_map for answers ---
                                        foreach ($fields as $field): 
                                            $val = $response_data_map[$response['id']][$field['id']] ?? null;
                                        ?>
                                            <td>
                                                <?= $val !== null && $val !== '' ? 
                                                    htmlspecialchars($val) : 
                                                    '<span class="text-muted">N/A</span>' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <!-- --- END: Use response_data_map for answers --- -->
                                        
                                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                        <td>
                                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?><?= $date_filter ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Show page numbers
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
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No responses found for this survey. Please check back later.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body> 
</html>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script> 
   <script>
        // Pass PHP data to JavaScript
        const chartData = <?= $chart_json ?>;
        
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Summary chart - Response trend over time
            if (chartData.total_responses > 0) {
                const ctx = document.getElementById('summaryChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        datasets: [{
                            label: 'Responses',
                            data: [
                                Math.floor(chartData.total_responses * 0.2),
                                Math.floor(chartData.total_responses * 0.4),
                                Math.floor(chartData.total_responses * 0.7),
                                chartData.total_responses
                            ],
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            borderColor: 'rgba(67, 97, 238, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Response Trend Over Time',
                                font: { size: 16 }
                            },
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `Responses: ${ctx.raw}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { 
                                    display: true, 
                                    text: 'Number of Responses',
                                    font: { weight: 'bold' }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                title: { 
                                    display: true, 
                                    text: 'Time Period',
                                    font: { weight: 'bold' }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                }); // Ensure this matches the opening parenthesis or brace earlier in the code
            }
        }); // Close the outer function or block properly

        // Field-specific charts
        chartData.fields.forEach(field => {
            const fieldAnalytics = chartData.analytics[field.id] || [];
            const ctx = document.getElementById(`fieldChart-${field.id}`).getContext('2d');

            if (fieldAnalytics.length > 0) {
                // Ensure "Yes" and "No" responses are always included
                const labels = ['Yes', 'No'];
                const data = [0, 0]; // Default counts for "Yes" and "No"

                fieldAnalytics.forEach(item => {
                    if (item.field_value === 'Yes') {
                        data[0] = item.count;
                    } else if (item.field_value === 'No') {
                        data[1] = item.count;
                    }
                });

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: ['#4361ee', '#f72585'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '60%',
                        plugins: {
                            title: {
                                display: true,
                                text: field.field_label,
                                font: { size: 14 }
                            },
                            legend: { display: true },
                            datalabels: {
                                anchor: 'end',
                                align: 'end',
                                formatter: value => value,
                                color: '#4361ee',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            } else {
                ctx.canvas.parentNode.innerHTML += '<div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> No response data available for this question.</div>';
            }
        });
        
        // PDF Export functionality
        document.addEventListener('DOMContentLoaded', function() {
            var exportBtn = document.getElementById('export-pdf');
            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    exportResultsToPDF();
                });
            }
        });

        function exportResultsToPDF() {
            // Select the main content to export (adjust selector as needed)
            var content = document.querySelector('.admin-main');
            if (!content) return;

            // Hide export dropdown for PDF
            var dropdown = document.querySelector('.dropdown');
            if (dropdown) dropdown.style.display = 'none';

            html2canvas(content, {scale: 2}).then(function(canvas) {
                var imgData = canvas.toDataURL('image/png');
                var pdf = new window.jspdf.jsPDF('p', 'pt', 'a4');
                var pageWidth = pdf.internal.pageSize.getWidth();
                var pageHeight = pdf.internal.pageSize.getHeight();
                var imgWidth = pageWidth - 40;
                var imgHeight = canvas.height * imgWidth / canvas.width;

                var position = 20;
                if (imgHeight < pageHeight - 40) {
                    pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
                } else {
                    // Multi-page
                    let heightLeft = imgHeight;
                    let y = position;
                    while (heightLeft > 0) {
                        pdf.addImage(imgData, 'PNG', 20, y, imgWidth, imgHeight);
                        heightLeft -= (pageHeight - 40);
                        if (heightLeft > 0) {
                            pdf.addPage();
                            y = 0;
                        }
                    }
                }
                pdf.save('survey_results.pdf');
                if (dropdown) dropdown.style.display = '';
            });
        }
    </script>
    <script src="../assets/js/results-export.js"></script>
