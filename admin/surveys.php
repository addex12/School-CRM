<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */ob_start();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Surveys";

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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .surveys-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
        }
        .surveys-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .surveys-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #215967;
            font-weight: 700;
        }
        .erpnext-btn, .btn, .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 8px;
            text-decoration: none;
        }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #eaeaea; color: #666; }
        .btn-secondary:hover { background: #e2efda; color: #215967; }
        .surveys-header .btn-primary {
            background: #27ae60;
            color: #fff;
            margin-left: 1rem;
        }
        .surveys-header .btn-primary:hover {
            background: #219150;
        }
        .surveys-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .surveys-table th, .surveys-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .surveys-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
        }
        .surveys-table tr:hover {
            background: #f4f8fb;
        }
        .survey-actions a, .survey-actions button {
            margin-right: 8px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 1.1em;
            background: none;
            border: none;
            cursor: pointer;
        }
        .survey-actions a:last-child, .survey-actions button:last-child {
            margin-right: 0;
        }
        .status-pill {
            display: inline-block;
            padding: 0.3em 0.9em;
            border-radius: 1em;
            font-size: 0.97em;
            font-weight: 600;
            background: #e2efda;
            color: #215967;
        }
        .status-pill.active { background: #dcfce7; color: #27ae60; }
        .status-pill.inactive { background: #fee2e2; color: #e74c3c; }
        .status-pill.closed { background: #f1c40f; color: #fff; }
        .category-pill {
            display: inline-block;
            padding: 0.2em 0.7em;
            border-radius: 1em;
            font-size: 0.93em;
            background: #f5f7fa;
            color: #215967;
            margin-right: 0.5em;
        }
        @media (max-width: 900px) {
            .surveys-container { padding: 1rem 0.5rem; }
            .surveys-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .surveys-table th, .surveys-table td { padding: 8px 6px; }
            .admin-main { padding: 10px 2px 80px; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="surveys-container">
                    <div class="surveys-header">
                        <h2>Survey List</h2>
                        <a href="survey_builder.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Survey</a>
                    </div>
                    <div class="table-responsive">
                        <table class="surveys-table">
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
                                                    <span class="category-pill"><?= htmlspecialchars($survey['category']) ?></span>
                                                <?php else: ?>
                                                    <span class="category-pill" style="background:#fee2e2;color:#e74c3c;">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($survey['is_active']): ?>
                                                    <span class="status-pill active"><?= $survey['status_label'] ?? 'Active' ?></span>
                                                <?php else: ?>
                                                    <span class="status-pill inactive"><?= $survey['status_label'] ?? 'Inactive' ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($survey['creator']) ?></td>
                                            <td><?= date('M j, Y', strtotime($survey['starts_at'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($survey['ends_at'])) ?></td>
                                            <td class="survey-actions">
                                                <a href="survey_preview.php?id=<?= $survey['id'] ?>" title="Preview"><i class="fas fa-eye"></i></a>
                                                <a href="edit_survey.php?id=<?= $survey['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="results.php?survey_id=<?= $survey['id'] ?>" title="Results"><i class="fas fa-chart-bar"></i></a>
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
        // Interactive row highlight
        document.querySelectorAll('.surveys-table tbody tr').forEach(function(row) {
            row.addEventListener('mouseenter', function() { row.style.background = '#e2efda'; });
            row.addEventListener('mouseleave', function() { row.style.background = ''; });
        });

        // ERPNext-style search/filter
        document.addEventListener('DOMContentLoaded', function() {
            // Add ERPNext-style search bar
            const searchBar = document.createElement('div');
            searchBar.style = 'display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;';
            searchBar.innerHTML = `
                <input type="text" id="surveySearch" placeholder="Search surveys..." style="flex:1;padding:10px 16px;border:1px solid #e5e7eb;border-radius:6px;font-size:1rem;background:#f9fafb;">
                <button class="erpnext-btn btn-primary" id="clearSearch" style="padding:10px 18px;">Clear</button>
            `;
            const table = document.querySelector('.surveys-table');
            const container = table.parentElement;
            container.insertBefore(searchBar, table);

            const searchInput = document.getElementById('surveySearch');
            const clearBtn = document.getElementById('clearSearch');
            searchInput.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                document.querySelectorAll('.surveys-table tbody tr').forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            });
        });

        // ERPNext-style row click for preview
        document.querySelectorAll('.surveys-table tbody tr').forEach(function(row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Only trigger if not clicking an action icon
                if (!e.target.closest('.survey-actions')) {
                    const idCell = row.querySelector('td');
                    if (idCell) {
                        const id = idCell.textContent.trim();
                        window.location.href = 'survey_preview.php?id=' + encodeURIComponent(id);
                    }
                }
            });
        });
        // ERPNext-style action dropdown (for future extensibility)
        document.querySelectorAll('.survey-actions').forEach(function(cell) {
            // Could add dropdown here if needed
        });
    </script>
            <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>