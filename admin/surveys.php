<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$pageTitle = "Manage Surveys";
$_SESSION['survey_success'] = $_SESSION['survey_success'] ?? null;
$_SESSION['survey_error'] = $_SESSION['survey_error'] ?? null;

// Fetch all surveys with category and status
$stmt = $pdo->query("
    SELECT s.*, u.username AS creator, c.name AS category, st.label AS status_label
    FROM surveys s
    LEFT JOIN users u ON s.created_by = u.id
    LEFT JOIN survey_categories c ON s.category_id = c.id
    LEFT JOIN survey_statuses st ON s.status = st.id
    ORDER BY s.created_at DESC
");
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting and clarity */
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-admin-main { margin-left: 260px; padding: 1.2rem 1.2rem; }
        .adugna-surveys-container {
            background: #fff;
            border-radius: 0.7rem;
            box-shadow: 0 2px 12px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
            border: 1px solid #e5e7eb;
            padding: 1.1rem 1.1rem;
            margin: 2rem 0;
            width: 100%;
            max-width: 1100px;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-surveys-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .adugna-surveys-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #215967;
            font-weight: 700;
        }
        /* Adugna: Compact, ERPNext/frappe-inspired button styles */
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.4em;
            padding: 0.13rem 0.7rem;
            font-size: 0.92em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 0.92em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-btn-success {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
        }
        .adugna-btn-success:hover {
            background: #b7e4c7;
            color: #215967;
        }
        .adugna-table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .adugna-surveys-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 0.98em;
        }
        .adugna-surveys-table th, .adugna-surveys-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-surveys-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-surveys-table tr:hover {
            background: #f4f8fb;
        }
        .adugna-survey-actions a, .adugna-survey-actions button {
            margin-right: 5px;
            color: #4f46e5;
            text-decoration: none;
            font-size: 1em;
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px 4px;
        }
        .adugna-survey-actions a:last-child, .adugna-survey-actions button:last-child {
            margin-right: 0;
        }
        .adugna-status-pill {
            display: inline-block;
            padding: 0.2em 0.7em;
            border-radius: 1em;
            font-size: 0.93em;
            font-weight: 600;
            background: #e2efda;
            color: #215967;
        }
        .adugna-status-pill.active { background: #dcfce7; color: #27ae60; }
        .adugna-status-pill.inactive { background: #fee2e2; color: #e74c3c; }
        .adugna-status-pill.closed { background: #f1c40f; color: #fff; }
        .adugna-category-pill {
            display: inline-block;
            padding: 0.15em 0.6em;
            border-radius: 1em;
            font-size: 0.91em;
            background: #f5f7fa;
            color: #215967;
            margin-right: 0.3em;
        }
        @media (max-width: 900px) {
            .adugna-surveys-container { padding: 0.7rem; }
            .adugna-surveys-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .adugna-surveys-table th, .adugna-surveys-table td { padding: 7px 4px; font-size: 0.93em; }
            .adugna-admin-main { padding: 7px 2px 80px; }
        }
        @media (max-width: 400px) {
            .adugna-surveys-container { padding: 2px; }
            .adugna-surveys-header h2 { font-size: 1em; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="adugna-surveys-container">
                    <?php if (isset($_GET['msg'])): ?>
                        <?php
                        $msg = $_GET['msg'];
                        $alert = '';
                        if ($msg === 'deleted') {
                            $alert = '<div class="adugna-alert-success" style="background:#e2efda;color:#215967;border:1px solid #b7e4c7;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-check-circle"></i> Survey deleted successfully.</div>';
                        } elseif ($msg === 'delete_failed') {
                            $alert = '<div class="adugna-alert-danger" style="background:#ffeaea;color:#e74c3c;border:1px solid #f5c6cb;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-times-circle"></i> Failed to delete survey.</div>';
                        } elseif ($msg === 'invalid') {
                            $alert = '<div class="adugna-alert-danger" style="background:#ffeaea;color:#e74c3c;border:1px solid #f5c6cb;padding:10px 18px;border-radius:5px;margin-bottom:1em;"><i class="fa fa-exclamation-circle"></i> Invalid survey ID.</div>';
                        }
                        echo $alert;
                        ?>
                    <?php endif; ?>
                    <div class="adugna-surveys-header">
                        <h2>Survey List</h2>
                        <a href="survey_builder.php" class="adugna-btn adugna-btn-success">
                            <i class="fas fa-plus"></i> New Survey
                        </a>
                    </div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-surveys-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Creator</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($surveys)): ?>
                                    <?php foreach ($surveys as $survey): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($survey['id']) ?></td>
                                            <td>
                                                <a href="survey_preview.php?id=<?= $survey['id'] ?>" style="color:#2563eb;font-weight:600;">
                                                    <?= htmlspecialchars($survey['title']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($survey['category']): ?>
                                                    <span class="adugna-category-pill"><?= htmlspecialchars($survey['category']) ?></span>
                                                <?php else: ?>
                                                    <span class="adugna-category-pill" style="background:#fee2e2;color:#e74c3c;">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($survey['is_active']): ?>
                                                    <span class="adugna-status-pill active"><?= $survey['status_label'] ?? 'Active' ?></span>
                                                <?php else: ?>
                                                    <span class="adugna-status-pill inactive"><?= $survey['status_label'] ?? 'Inactive' ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($survey['creator']) ?></td>
                                            <td><?= date('M j, Y', strtotime($survey['starts_at'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($survey['ends_at'])) ?></td>
                                            <td class="adugna-survey-actions">
                                                <!-- Compact, consistent action icons -->
                                                <a href="survey_preview.php?id=<?= $survey['id'] ?>" title="Preview"><i class="fas fa-eye"></i></a>
                                                <a href="edit_survey.php?id=<?= $survey['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="results.php?survey_id=<?= $survey['id'] ?>" title="Results"><i class="fas fa-chart-bar"></i></a>
                                                <a href="delete_survey.php?id=<?= $survey['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this survey?');"><i class="fas fa-trash" style="color:#e74c3c;"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No surveys found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Adugna Gizaw: Interactive row highlight for better UX
        document.querySelectorAll('.adugna-surveys-table tbody tr').forEach(function(row) {
            row.addEventListener('mouseenter', function() { row.style.background = '#e2efda'; });
            row.addEventListener('mouseleave', function() { row.style.background = ''; });
        });

        // Adugna: ERPNext-style search/filter bar
        document.addEventListener('DOMContentLoaded', function() {
            // Add ERPNext-style search bar
            const searchBar = document.createElement('div');
            searchBar.style = 'display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;';
            searchBar.innerHTML = `
                <input type="text" id="adugnaSurveySearch" placeholder="Search surveys..." style="flex:1;padding:10px 16px;border:1px solid #e5e7eb;border-radius:6px;font-size:1rem;background:#f9fafb;">
                <button class="adugna-btn adugna-btn-success" id="adugnaClearSearch" style="padding:10px 18px;">Clear</button>
            `;
            const table = document.querySelector('.adugna-surveys-table');
            const container = table.parentElement;
            container.insertBefore(searchBar, table);

            const searchInput = document.getElementById('adugnaSurveySearch');
            const clearBtn = document.getElementById('adugnaClearSearch');
            searchInput.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                document.querySelectorAll('.adugna-surveys-table tbody tr').forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            });
        });

        // Adugna: ERPNext-style row click for preview
        document.querySelectorAll('.adugna-surveys-table tbody tr').forEach(function(row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Only trigger if not clicking an action icon
                if (!e.target.closest('.adugna-survey-actions')) {
                    const idCell = row.querySelector('td');
                    if (idCell) {
                        const id = idCell.textContent.trim();
                        window.location.href = 'survey_preview.php?id=' + encodeURIComponent(id);
                    }
                }
            });
        });
        // Adugna: ERPNext-style action dropdown (for future extensibility)
        document.querySelectorAll('.adugna-survey-actions').forEach(function(cell) {
            // Could add dropdown here if needed
        });
    </script>
            <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>