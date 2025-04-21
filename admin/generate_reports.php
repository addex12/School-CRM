<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Generate Reports";

// Helper: Get users for dropdown
$users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();

// Defaults
$reportType = $_GET['report_type'] ?? 'audit';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$userId = $_GET['user_id'] ?? '';
$status = $_GET['status'] ?? '';

// Query builder for each report type
function getReportData($pdo, $type, $startDate, $endDate, $userId, $status) {
    $params = [];
    $where = [];
    if ($startDate) {
        $where[] = 'created_at >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $where[] = 'created_at <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    if ($status && $type === 'tickets') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    switch ($type) {
        case 'audit':
            $sql = "SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $whereSql ORDER BY a.created_at DESC LIMIT 100";
            break;
        case 'surveys':
            $sql = "SELECT * FROM surveys $whereSql ORDER BY created_at DESC LIMIT 100";
            break;
        case 'feedback':
            $sql = "SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id $whereSql ORDER BY f.created_at DESC LIMIT 100";
            break;
        case 'tickets':
            $sql = "SELECT t.*, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id $whereSql ORDER BY t.created_at DESC LIMIT 100";
            break;
        case 'attendance':
            $sql = "SELECT a.*, u.username FROM attendance a LEFT JOIN users u ON a.employee_id = u.id $whereSql ORDER BY a.date DESC LIMIT 100";
            break;
        default:
            return [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get report data
$reportData = getReportData($pdo, $reportType, $startDate, $endDate, $userId, $status);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-filters { margin-bottom: 2em; background: #f9f9f9; padding: 1em; border-radius: 8px; }
        .report-actions { margin-bottom: 1em; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 8px; }
        .report-table th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <form method="get" class="report-filters">
                    <label>Report Type:
                        <select name="report_type" onchange="this.form.submit()">
                            <option value="audit" <?= $reportType==='audit'?'selected':'' ?>>Audit Log</option>
                            <option value="surveys" <?= $reportType==='surveys'?'selected':'' ?>>Surveys</option>
                            <option value="feedback" <?= $reportType==='feedback'?'selected':'' ?>>Feedback</option>
                            <option value="tickets" <?= $reportType==='tickets'?'selected':'' ?>>Support Tickets</option>
                            <option value="attendance" <?= $reportType==='attendance'?'selected':'' ?>>Attendance</option>
                        </select>
                    </label>
                    <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
                    <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
                    <?php if (in_array($reportType, ['audit','feedback','tickets','attendance'])): ?>
                    <label>User:
                        <select name="user_id">
                            <option value="">All</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $userId==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php endif; ?>
                    <?php if ($reportType==='tickets'): ?>
                    <label>Status:
                        <select name="status">
                            <option value="">All</option>
                            <option value="open" <?= $status==='open'?'selected':'' ?>>Open</option>
                            <option value="closed" <?= $status==='closed'?'selected':'' ?>>Closed</option>
                        </select>
                    </label>
                    <?php endif; ?>
                    <!--<button type="submit" class="btn btn-primary">Generate</button>-->
                </form>
                <div class="report-actions">
                    <button onclick="exportTableToCSV('report.csv')" class="btn btn-secondary">Export CSV</button>
                    <button onclick="window.print()" class="btn btn-secondary">Print</button>
                </div>
                <div id="report-results">
                    <?php if (empty($reportData)): ?>
                        <p>No data found for the selected criteria.</p>
                    <?php else: ?>
                        <table class="report-table" id="report-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($reportData[0]) as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?= htmlspecialchars($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <script>
                // Export table to CSV
                function exportTableToCSV(filename) {
                    var csv = [];
                    var rows = document.querySelectorAll("#report-table tr");
                    for (var i = 0; i < rows.length; i++) {
                        var row = [], cols = rows[i].querySelectorAll("td, th");
                        for (var j = 0; j < cols.length; j++)
                            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                        csv.push(row.join(","));
                    }
                    var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
                    var downloadLink = document.createElement("a");
                    downloadLink.download = filename;
                    downloadLink.href = window.URL.createObjectURL(csvFile);
                    downloadLink.style.display = "none";
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                }
                </script>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
