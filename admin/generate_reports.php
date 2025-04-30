<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
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
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(25, 118, 210, 0.09);
            padding: 28px 28px 38px 28px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.45em;
            color: #1976d2;
            font-weight: 800;
            margin-bottom: 28px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-report-filters {
            margin-bottom: 2.2em;
            background: #f9f9f9;
            padding: 1.2em 1.5em;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5em;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-report-filters label {
            font-size: 1em;
            color: #444;
            font-weight: 600;
            margin-bottom: 0.2em;
            display: flex;
            align-items: center;
            gap: 0.4em;
        }
        .adugna-report-filters select,
        .adugna-report-filters input[type="date"] {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #d0d7de;
            font-size: 1em;
            background: #f9fbfd;
            color: #222;
            margin-left: 0.2em;
        }
        .adugna-report-actions {
            margin-bottom: 1.5em;
            display: flex;
            gap: 1.2em;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 7px 18px;
            font-size: 1em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 4px rgba(25,118,210,0.07);
        }
        .adugna-btn i { font-size: 1.1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-report-table-container {
            overflow-x: auto;
            margin-top: 1.5em;
            border-radius: 10px;
            background: #f8fafc;
            box-shadow: 0 1px 8px rgba(25,118,210,0.04);
        }
        .adugna-report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1em;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-report-table th, .adugna-report-table td {
            padding: 11px 12px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-report-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 700;
            font-size: 1.03em;
            border-bottom: 2px solid #e3eafc;
        }
        .adugna-report-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-report-table tr:hover {
            background: #eaf6ff;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 14px 6px 18px 6px; }
        }
        @media (max-width: 900px) {
            .adugna-main-content { padding: 0.7rem 0.5rem 1rem 0.5rem; }
            .adugna-header-title { font-size: 1.15em; }
            .adugna-report-filters { flex-direction: column; gap: 0.7em; align-items: flex-start; }
            .adugna-report-actions { justify-content: flex-start; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
            .adugna-report-table th, .adugna-report-table td { padding: 5px 4px; font-size: 0.95em; }
            .adugna-report-filters { padding: 0.7em 0.2em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-chart-bar"></i> <?= htmlspecialchars($pageTitle) ?>
            </div>
            <form method="get" class="adugna-report-filters">
                <label>Report Type:
                    <select name="report_type" onchange="this.form.submit()">
                        <option value="audit" <?= $reportType==='audit'?'selected':'' ?>>Audit Log</option>
                        <option value="surveys" <?= $reportType==='surveys'?'selected':'' ?>>Surveys</option>
                        <option value="feedback" <?= $reportType==='feedback'?'selected':'' ?>>Feedback</option>
                        <option value="tickets" <?= $reportType==='tickets'?'selected':'' ?>>Support Tickets</option>
                    </select>
                </label>
                <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
                <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
                <label>User:
                    <select name="user_id">
                        <option value="">All</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $userId==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($reportType==='tickets'): ?>
                <label>Status:
                    <select name="status">
                        <option value="">All</option>
                        <option value="open" <?= $status==='open'?'selected':'' ?>>Open</option>
                        <option value="closed" <?= $status==='closed'?'selected':'' ?>>Closed</option>
                    </select>
                </label>
                <?php endif; ?>
            </form>
            <div class="adugna-report-actions">
                <button onclick="exportTableToCSV('report.csv')" class="adugna-btn adugna-btn-secondary"><i class="fas fa-download"></i> Export CSV</button>
                <button onclick="window.print()" class="adugna-btn adugna-btn-secondary"><i class="fas fa-print"></i> Print</button>
            </div>
            <div id="report-results" class="adugna-report-table-container">
                <?php if (empty($reportData)): ?>
                    <p style="color:#888;text-align:center;">No data found for the selected criteria.</p>
                <?php else: ?>
                    <table class="adugna-report-table" id="report-table">
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
            // Adugna Gizaw: Export table to CSV, compact and responsive
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
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
