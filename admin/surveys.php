<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Surveys";

// Fetch all surveys
$stmt = $pdo->query("SELECT s.*, u.username AS creator FROM surveys s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC");
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
            color: #34495e;
        }
        .surveys-header .btn {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .surveys-header .btn:hover {
            background: #219150;
        }
        .surveys-table {
            width: 100%;
            border-collapse: collapse;
        }
        .surveys-table th, .surveys-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .surveys-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .surveys-table tr:hover {
            background: #f4f8fb;
        }
        .survey-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .survey-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .surveys-container {
                padding: 1rem 0.5rem;
            }
            .surveys-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .surveys-table th, .surveys-table td {
                padding: 8px 6px;
            }
        }
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
                <div class="surveys-container">
                    <div class="surveys-header">
                        <h2>Survey List</h2>
                        <a href="survey_builder.php" class="btn"><i class="fas fa-plus"></i> New Survey</a>
                    </div>
                    <div class="table-responsive">
                        <table class="surveys-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Creator</th>
                                    <th>Status</th>
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
                                            <td><?= htmlspecialchars($survey['title']) ?></td>
                                            <td><?= htmlspecialchars($survey['creator']) ?></td>
                                            <td>
                                                <?php if ($survey['is_active']): ?>
                                                    <span style="color:#27ae60;font-weight:500;">Active</span>
                                                <?php else: ?>
                                                    <span style="color:#e74c3c;font-weight:500;">Inactive</span>
                                                <?php endif; ?>
                                            </td>
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
                                        <td colspan="7">No surveys found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
