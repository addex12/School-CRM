<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Manage Surveys";

// Initialize variables
$error = null;
$survey_id = $_GET['survey_id'] ?? null;

// Validate survey_id if provided
if ($survey_id !== null) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        $error = "Survey not found.";
    } elseif (!$survey['is_active']) {
        $error = "This survey is inactive.";
    } elseif (strtotime($survey['starts_at']) > time()) {
        $error = "This survey has not started yet.";
    } elseif (strtotime($survey['ends_at']) < time()) {
        $error = "This survey has already ended.";
    }
}

// Fetch all surveys for display
$surveys = $pdo->query("SELECT * FROM surveys ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <!-- Display error messages -->
                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Display all surveys -->
                <div class="table-section">
                    <h2>All Surveys</h2>
                    <a href="survey_preview.php?id=<?= $survey['id'] ?>"...>                        <table class="table">
                            <thead>
                                <tr></tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title']) ?></td>
                                        <td><?= htmlspecialchars($survey['description']) ?></td>
                                        <td><?= htmlspecialchars($survey['created_by']) ?></td>
                                        <td>
                                            <?php if (!$survey['is_active']): ?>
                                                <span class="status-inactive">Inactive</span>
                                            <?php elseif (strtotime($survey['starts_at']) > time()): ?>
                                                <span class="status-upcoming">Upcoming</span>
                                            <?php elseif (strtotime($survey['ends_at']) < time()): ?>
                                                <span class="status-ended">Ended</span>
                                            <?php else: ?>
                                                <span class="status-active">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="survey_preview.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">Preview</a>
                                            <a href="results.php?survey_id=<?= $survey['id'] ?>" class="btn btn-secondary">Results</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No surveys found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>