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

// Fetch surveys grouped by status
try {
    $stmt = $pdo->query("
        SELECT s.*, sc.name AS category_name 
        FROM surveys s
        LEFT JOIN survey_categories sc ON s.category_id = sc.id
        ORDER BY s.status, s.created_at DESC
    ");
    $surveys = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching surveys: " . $e->getMessage());
    $surveys = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php foreach ($surveys as $status => $statusSurveys): ?>
                    <section class="survey-section">
                        <h2><?= htmlspecialchars(ucfirst($status)) ?> Surveys</h2>
                        <?php if (!empty($statusSurveys)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statusSurveys as $survey): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($survey['title']) ?></td>
                                            <td><?= htmlspecialchars($survey['category_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($survey['created_by']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($survey['created_at'])) ?></td>
                                            <td>
                                                <?php if (isset($survey['id'])): ?>
                                                    <a href="survey_builder.php?id=<?= htmlspecialchars($survey['id']) ?>" class="btn btn-secondary">Edit</a>
                                                <?php else: ?>
                                                    <span class="text-muted">No ID</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No surveys found in this status.</p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>


