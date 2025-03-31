<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../models/Survey.php'; // Adjust the path to where the Survey class is defined
requireAdmin();

$pageTitle = "Manage Surveys";

// Initialize variables
$error = null;
$survey_id = $_GET['survey_id'] ?? null;

$survey = Survey::model()->findByPk($survey_id);
if (!$survey) {
    $error = "Survey not found.";
} else {
    $statuses = Survey::getStatuses();
    $statusLabels = array_column($statuses, 'label', 'status');

    if ($survey->status === 'active') {
        $error = "This survey is active.";
    } elseif ($survey->status === 'inactive') {
        $error = "This survey is inactive.";
    } elseif ($survey->status === 'pending') {
        $error = "This survey is pending.";
    } elseif ($survey->status === 'suspended') {
        $error = "This survey is suspended.";
    } elseif ($survey->status === 'archived') {
        $error = "This survey is archived.";
    } elseif ($survey->status === 'pending_review') {
        $error = "This survey is pending review.";
    } elseif ($survey->status === 'suspended_review') {
        $error = "This survey is suspended for review.";
    }
}

// Handle delete survey request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_survey'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: surveys.php");
        exit();
    }
    $survey_id = intval($_POST['survey_id']);
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);    
    $_SESSION['success'] = "Survey deleted successfully!";
    header("Location: surveys.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST"&& isset($_POST[""])) {
    $survey_id = $_POST["survey_id"];
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $_SESSION['success'] = "Survey deleted successfully!";
    header("Location: surveys.php");
    exit();
}   
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

// Handle delete survey request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_survey'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: surveys.php");
        exit();
    }
    $survey_id = $_POST['survey_id'];
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $_SESSION['success'] = "Survey deleted successfully!";
    header("Location: surveys.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST"&& isset($_POST[""]))
{
    $survey_id = $_POST["survey_id"];
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $_SESSION['success'] = "Survey deleted successfully!";
    header("Location: surveys.php");
    exit();
}
if (isset($_POST['delete_survey']) &&
    ($survey_id = $_POST['survey_id'])) {
    $stmt = $pdo->prepare('DELETE FROM surveys WHERE id = ?');
    $stmt->execute([$survey_id]);
}

// Fetch all surveys for display
$activeSurveys = $pdo->query("SELECT * FROM surveys WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
$inactiveSurveys = $pdo->query("SELECT * FROM surveys WHERE status = 'inactive' ORDER BY created_at DESC")->fetchAll();
$archivedSurveys = $pdo->query("SELECT * FROM surveys WHERE status = 'archived' ORDER BY created_at DESC")->fetchAll();
$draftSurveys = $pdo->query("SELECT * FROM surveys WHERE status = 'draft' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="export-dropdown">
                    <a href="survey_builder.php" class="btn btn-primary">Create New Survey</a>
                    <a href="export_surveys.php" class="btn btn-secondary">Export Surveys</a>
                </div>
                  <li><a href="logout.php">Logout</a></li>
                </ul>
            </header>
            <div class="content">
                <?php if ($error) : ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php else: ?>
                <h2>Manage Surveys</h2>

                <p>Here you can manage all your surveys. You can create, edit, delete, and view survey results.</p>
                <p>Use the buttons above to create a new survey or export existing surveys.</p>
                <!-- Display error messages -->
                <?php if (is_array($error)): ?>
                    <?php foreach ($error as $err): ?>
                        <p class="error-message"><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="error-message"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Display active surveys -->
                <div class="table-section">
                    <h2>Active Surveys</h2>
                    <?php if (count($activeSurveys) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeSurveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($survey['description'] ?? '') ?></td>
                                        <td>
                                            <span class="status-active">Active</span>
                                        </td>
                                        <td>
                                            <a href="survey_builder.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">Edit</a>
                                            <a href="survey_preview.php?id=<?= $survey['id'] ?>" class="btn btn-secondary">Preview</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
                                                <button type="submit" name="delete_survey" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this survey?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No active surveys found.</p>
                    <?php endif; ?>
                </div>

                <!-- Display inactive surveys -->
                <div class="table-section">
                    <h2>Inactive Surveys</h2>
                    <?php if (count($inactiveSurveys) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inactiveSurveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($survey['description'] ?? '') ?></td>
                                        <td>
                                            <span class="status-inactive">Inactive</span>
                                        </td>
                                        <td>
                                            <a href="survey_builder.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">Edit</a>
                                            <a href="survey_preview.php?id=<?= $survey['id'] ?>" class="btn btn-secondary">Preview</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
                                                <button type="submit" name="delete_survey" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this survey?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No inactive surveys found.</p>
                    <?php endif; ?>
                </div>

                <!-- Display archived surveys -->
                <div class="table-section">
                    <h2>Archived Surveys</h2>
                    <?php if (count($archivedSurveys) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedSurveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($survey['description'] ?? '') ?></td>
                                        <td>
                                            <span class="status-archived">Archived</span>
                                        </td>
                                        <td>
                                            <a href="survey_builder.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">Edit</a>
                                            <a href="survey_preview.php?id=<?= $survey['id'] ?>" class="btn btn-secondary">Preview</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
                                                <button type="submit" name="delete_survey" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this survey?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No archived surveys found.</p>
                    <?php endif; ?>
                </div>

                <!-- Display draft surveys -->
                <div class="table-section">
                    <h2>Draft Surveys</h2>
                    <?php if (count($draftSurveys) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($draftSurveys as $survey): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($survey['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($survey['description'] ?? '') ?></td>
                                        <td>
                                            <span class="status-draft">Draft</span>
                                        </td>
                                        <td>
                                            <a href="survey_builder.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">Edit</a>
                                            <a href="survey_preview.php?id=<?= $survey['id'] ?>" class="btn btn-secondary">Preview</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
                                                <button type="submit" name="delete_survey" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this survey?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No draft surveys found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
  </div>
  <?php require_once 'includes/footer.php'; ?>
</body>
</html>
<?php include 'includes/footer.php'; ?>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/survey.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/export.js"></script>
    <script src="../assets/js/chart.js"></script>
    <script src="../assets/js/survey-results.js"></script>
    <script src="../assets/js/survey.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>

</html>


