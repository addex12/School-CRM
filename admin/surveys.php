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
require_once '../models/Survey.php';

$pageTitle = "Surveys";

// Get all surveys with their roles
$surveys = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               sc.name as category_name,
               u.username as created_by_user,
               GROUP_CONCAT(DISTINCT r.role_name) as target_roles
        FROM surveys s
        LEFT JOIN survey_categories sc ON s.category_id = sc.id
        LEFT JOIN users u ON s.created_by = u.id
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN roles r ON sr.role_id = r.id
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching surveys: " . $e->getMessage());
    $surveys = [];
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $survey_id = $_POST['survey_id'] ?? null;
    if ($survey_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
            $stmt->execute([$survey_id]);
            $_SESSION['success'] = "Survey deleted successfully!";
            header("Location: surveys.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error deleting survey: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete survey";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="header-actions">
                    <a href="survey_builder.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Survey</a>
                </div>
            </header>
            
            <div class="content">
                <?php if (!empty($surveys)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Target Roles</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title']) ?></td>
                                        <td><?= htmlspecialchars($survey['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($survey['target_roles'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="status-badge <?= strtolower($survey['status']) ?>">
                                                <?= htmlspecialchars($survey['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($survey['created_by_user'] ?? 'N/A') ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($survey['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="survey_builder.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger delete-survey" 
                                                        data-survey-id="<?= $survey['id'] ?>" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-results">No surveys found. <a href="survey_builder.php">Create your first survey</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete Survey</h3>
            <p>Are you sure you want to delete this survey? This action cannot be undone.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="survey_id" id="deleteSurveyId">
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete survey confirmation
            document.querySelectorAll('.delete-survey').forEach(button => {
                button.addEventListener('click', function() {
                    const surveyId = this.dataset.surveyId;
                    document.getElementById('deleteSurveyId').value = surveyId;
                    document.getElementById('deleteModal').style.display = 'block';
                });
            });

            // Close modal
            document.getElementById('cancelDelete').addEventListener('click', function() {
                document.getElementById('deleteModal').style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('deleteModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
<?php include 'includes/footer.php'; ?>
