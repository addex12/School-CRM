<?php
/**
 * Survey Responses Page
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Survey Responses";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get survey ID
$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    $_SESSION['error'] = "No survey specified.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey info
try {
    $stmt = $pdo->prepare("SELECT id, title, is_anonymous FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$survey) {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Survey fetch error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load survey data.";
    header("Location: surveys.php");
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$userId = $_GET['user_id'] ?? '';

// Build base query
$query = "SELECT sr.*, u.username, u.email 
          FROM survey_responses sr 
          LEFT JOIN users u ON sr.user_id = u.id 
          WHERE sr.survey_id = :survey_id";
$params = [':survey_id' => $surveyId];

// Apply filters
if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($dateFrom)) {
    $query .= " AND sr.submitted_at >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND sr.submitted_at <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

if (!empty($userId)) {
    $query .= " AND sr.user_id = :user_id";
    $params[':user_id'] = $userId;
}

// Add sorting
$sortField = $_GET['sort'] ?? 'submitted_at';
$sortOrder = $_GET['order'] ?? 'desc';
$validSortFields = ['id', 'username', 'submitted_at', 'ip_address'];
$sortField = in_array($sortField, $validSortFields) ? $sortField : 'submitted_at';
$sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sortField $sortOrder";

// Pagination
$itemsPerPage = 10;
$currentPage = $_GET['page'] ?? 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count
$countQuery = "SELECT COUNT(*) FROM ($query) as total";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

$totalPages = ceil($totalItems / $itemsPerPage);

// Get paginated results
$query .= " LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent users for filter dropdown
$users = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.username 
                          FROM survey_responses sr 
                          JOIN users u ON sr.user_id = u.id 
                          WHERE sr.survey_id = ? 
                          ORDER BY u.username");
    $stmt->execute([$surveyId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Users fetch error: " . $e->getMessage());
}

// Format dates for display
foreach ($responses as &$response) {
    $response['submitted_at'] = formatDate($response['submitted_at']);
}
unset($response);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/survey_responses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($survey['title']) ?></h1>
                    <p class="welcome-message">Survey Responses</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications content would be here -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Filters Section -->
                <div class="filters-section">
                    <form method="get" class="filter-form">
                        <input type="hidden" name="id" value="<?= $surveyId ?>">
                        
                        <div class="filter-group">
                            <label for="search">Search:</label>
                            <input type="text" id="search" name="search" placeholder="Search users..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">From:</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To:</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        
                        <?php if (!$survey['is_anonymous'] && !empty($users)): ?>
                            <div class="filter-group">
                                <label for="user_id">User:</label>
                                <select id="user_id" name="user_id">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" 
                                            <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="survey_responses.php?id=<?= $surveyId ?>" class="btn btn-outline">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </form>
                </div>
                
                <!-- Responses Table -->
                <div class="responses-section">
                    <div class="section-header">
                        <h2><i class="fas fa-clipboard-list"></i> Responses</h2>
                        <div class="response-count">
                            Total: <?= $totalItems ?> responses
                        </div>
                    </div>
                    
                    <?php if (!empty($responses)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?<?= buildQueryString(['sort' => 'id', 'order' => $sortField === 'id' && $sortOrder === 'ASC' ? 'desc' : 'asc']) ?>">
                                                ID <?= getSortIcon('id') ?>
                                            </a>
                                        </th>
                                        <?php if (!$survey['is_anonymous']): ?>
                                            <th>
                                                <a href="?<?= buildQueryString(['sort' => 'username', 'order' => $sortField === 'username' && $sortOrder === 'ASC' ? 'desc' : 'asc']) ?>">
                                                    User <?= getSortIcon('username') ?>
                                                </a>
                                            </th>
                                        <?php endif; ?>
                                        <th>
                                            <a href="?<?= buildQueryString(['sort' => 'submitted_at', 'order' => $sortField === 'submitted_at' && $sortOrder === 'ASC' ? 'desc' : 'asc']) ?>">
                                                Submitted <?= getSortIcon('submitted_at') ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?<?= buildQueryString(['sort' => 'ip_address', 'order' => $sortField === 'ip_address' && $sortOrder === 'ASC' ? 'desc' : 'asc']) ?>">
                                                IP Address <?= getSortIcon('ip_address') ?>
                                            </a>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($responses as $response): ?>
                                        <tr>
                                            <td><?= $response['id'] ?></td>
                                            <?php if (!$survey['is_anonymous']): ?>
                                                <td>
                                                    <?php if ($response['username']): ?>
                                                        <?= htmlspecialchars($response['username']) ?>
                                                        <small><?= htmlspecialchars($response['email']) ?></small>
                                                    <?php else: ?>
                                                        Guest
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td><?= $response['submitted_at'] ?></td>
                                            <td><?= htmlspecialchars($response['ip_address']) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-view">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="response_export.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-export">
                                                        <i class="fas fa-download"></i> Export
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-delete" 
                                                            data-response-id="<?= $response['id'] ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <?php if ($totalPages > 1): ?>
                                <?php if ($currentPage > 1): ?>
                                    <a href="?<?= buildQueryString(['page' => 1]) ?>" class="page-link">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?<?= buildQueryString(['page' => $currentPage - 1]) ?>" class="page-link">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?<?= buildQueryString(['page' => $i]) ?>" 
                                       class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?<?= buildQueryString(['page' => $currentPage + 1]) ?>" class="page-link">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?<?= buildQueryString(['page' => $totalPages]) ?>" class="page-link">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No responses found</p>
                            <?php if ($search || $dateFrom || $dateTo || $userId): ?>
                                <a href="survey_responses.php?id=<?= $surveyId ?>" class="btn btn-primary">
                                    <i class="fas fa-undo"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Bulk Actions -->
                <?php if (!empty($responses)): ?>
                    <div class="bulk-actions">
                        <h3><i class="fas fa-tasks"></i> Bulk Actions</h3>
                        <div class="actions">
                            <button type="button" id="export-all" class="btn btn-secondary">
                                <i class="fas fa-download"></i> Export All Responses
                            </button>
                            <button type="button" id="delete-selected" class="btn btn-danger" disabled>
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this response? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline close-modal">Cancel</button>
                <button type="button" id="confirm-delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../assets/js/survey_responses.js"></script>
</body>
</html>