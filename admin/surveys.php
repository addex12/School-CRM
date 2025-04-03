<?php
/**
 * Surveys Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Survey Management";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Fetch all surveys
$surveys = $pdo->query("
    SELECT s.*, sc.name AS category_name, u.username AS created_by
    FROM surveys s
    LEFT JOIN survey_categories sc ON s.category_id = sc.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
")->fetchAll();
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
    <link rel="stylesheet" href="../assets/css/surveys.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Manage and analyze your surveys</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge"><?= countUnreadNotifications($pdo, $_SESSION['user_id']) ?></span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications dropdown content -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <a href="survey_create.php" class="btn btn-primary">Create New Survey</a>
                <?php if (count($surveys) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Created By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($surveys as $survey): ?>
                                <tr>
                                    <td><?= htmlspecialchars($survey['title']) ?></td>
                                    <td><?= htmlspecialchars($survey['category_name'] ?? 'Uncategorized') ?></td>
                                    <td><?= htmlspecialchars($survey['created_by']) ?></td>
                                    <td><?= htmlspecialchars($survey['is_active'] ? 'Active' : 'Inactive') ?></td>
                                    <td>
                                        <a href="survey_edit.php?id=<?= $survey['id'] ?>" class="btn btn-edit">Edit</a>
                                        <a href="survey_assign.php?id=<?= $survey['id'] ?>" class="btn btn-view">Assign</a>
                                        <form method="POST" action="survey_delete.php" class="delete-form" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $survey['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No surveys found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/surveys.js"></script>
</body>
</html>