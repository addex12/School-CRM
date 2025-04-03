<?php
/**
 * Delete Survey
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if survey ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Survey ID not specified.";
    header("Location: surveys.php");
    exit();
}

$surveyId = (int)$_GET['id'];

// Check if confirmation is given
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete responses data first
        $stmt = $pdo->prepare("DELETE rd FROM response_data rd
                              JOIN survey_responses sr ON rd.response_id = sr.id
                              WHERE sr.survey_id = ?");
        $stmt->execute([$surveyId]);
        
        // Delete responses
        $stmt = $pdo->prepare("DELETE FROM survey_responses WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        
        // Delete fields
        $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        
        // Delete survey roles
        $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        
        // Finally delete the survey
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Survey deleted successfully!";
        header("Location: surveys.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete survey: " . $e->getMessage();
        header("Location: survey_view.php?id=$surveyId");
        exit();
    }
}

// Fetch survey title for confirmation
try {
    $stmt = $pdo->prepare("SELECT title FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$survey) {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: surveys.php");
    exit();
}

$pageTitle = "Delete Survey";
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
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Confirm survey deletion</p>
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
                <div class="delete-confirmation">
                    <div class="confirmation-card">
                        <div class="confirmation-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Are you sure you want to delete this survey?</h2>
                        <p class="survey-title">"<?= htmlspecialchars($survey['title']) ?>"</p>
                        <p class="warning-text">This action cannot be undone. All survey data, including responses, will be permanently deleted.</p>
                        
                        <div class="confirmation-actions">
                            <a href="survey_delete.php?id=<?= $surveyId ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Yes, Delete Permanently
                            </a>
                            <a href="survey_view.php?id=<?= $surveyId ?>" class="btn btn-outline">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>