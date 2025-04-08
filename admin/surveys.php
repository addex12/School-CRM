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
$stmt = $pdo->query("
    SELECT s.*, GROUP_CONCAT(r.role_name SEPARATOR ', ') AS assigned_roles
    FROM surveys s
    LEFT JOIN survey_roles sr ON s.id = sr.survey_id
    LEFT JOIN roles r ON sr.role_id = r.id
    GROUP BY s.id
    ORDER BY s.starts_at DESC
");
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_survey'])) {
    $survey_id = $_POST['survey_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$survey_id]);
        $_SESSION['success'] = "Survey deleted successfully!";
        header("Location: surveys.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting survey: " . $e->getMessage();
    }
}

// Fetch survey statuses dynamically
try {
    $statusesStmt = $pdo->query("SELECT status, label FROM survey_statuses ORDER BY id");
    $statuses = $statusesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching survey statuses: " . $e->getMessage());
    $statuses = [];
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
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status['status']) ?>">
                                <?= htmlspecialchars($status['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </header>
            <div class="content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Assigned Roles</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($surveys as $survey): ?>
                            <tr>
                                <td><?= htmlspecialchars($survey['title']); ?></td>
                                <td><?= htmlspecialchars($survey['description']); ?></td>
                                <td><?= htmlspecialchars($survey['category_id']); ?></td>
                                <td><?= htmlspecialchars($survey['assigned_roles']); ?></td>
                                <td><?= htmlspecialchars($survey['starts_at']); ?></td>
                                <td><?= htmlspecialchars($survey['ends_at']); ?></td>
                                <td>
                                    <a href="edit_survey.php?id=<?= $survey['id']; ?>">Edit</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="survey_id" value="<?= $survey['id']; ?>">
                                        <button type="submit" name="delete_survey" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<script src="../assets/js/surveys.js"></script>

