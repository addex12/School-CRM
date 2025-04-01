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
        SELECT s.*, sc.name AS category_name, u.username AS created_by_user
        FROM surveys s
        LEFT JOIN survey_categories sc ON s.category_id = sc.id
        LEFT JOIN users u ON s.created_by = u.id
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
    <script src="../assets/js/surveys.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="filter-container">
                    <input type="text" id="search-surveys" placeholder="Search surveys..." class="form-control">
                    <select id="filter-status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </header>
            <div class="content">
                <?php foreach ($surveys as $status => $statusSurveys): ?>
                    <section class="survey-section" data-status="<?= htmlspecialchars($status) ?>">
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
                                            <td><?= htmlspecialchars($survey['created_by_user'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($survey['created_at'])) ?></td>
                                            <td>
                                                <?php if (isset($survey['id']) && $survey['id'] !== null): ?>
                                                    <a href="survey_builder.php?id=<?= htmlspecialchars($survey['id']) ?>" class="btn btn-secondary">Edit</a>
                                                    <a href="view_survey.php?id=<?= htmlspecialchars($survey['id']) ?>" class="btn btn-secondary">View</a>
                                                    <a href="results.php?survey_id=<?= htmlspecialchars($survey['id']) ?>" class="btn btn-secondary">Results</a>
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
<script src="../assets/js/surveys.js"></script>

