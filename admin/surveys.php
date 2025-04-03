<?php
/**
 * Surveys Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Surveys";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT s.*, sc.name as category_name, ss.label as status_label, ss.icon as status_icon, 
          u.username as creator_name, COUNT(sr.id) as response_count
          FROM surveys s
          LEFT JOIN survey_categories sc ON s.category_id = sc.id
          LEFT JOIN survey_statuses ss ON s.status_id = ss.id
          LEFT JOIN users u ON s.created_by = u.id
          LEFT JOIN survey_responses sr ON s.id = sr.survey_id
          WHERE 1=1";

$params = [];

// Apply filters
if ($status !== 'all') {
    $query .= " AND s.status_id = ?";
    $params[] = $status;
}

if ($category !== 'all') {
    $query .= " AND s.category_id = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $query .= " AND (s.title LIKE ? OR s.description LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Group by survey ID for the count
$query .= " GROUP BY s.id";

// Add sorting
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';
$query .= " ORDER BY $sort $order";

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
$query .= " LIMIT $itemsPerPage OFFSET $offset"; // Embed LIMIT and OFFSET directly into the query string

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories = $pdo->query("SELECT * FROM survey_categories")->fetchAll(PDO::FETCH_ASSOC);

// Get statuses for filter dropdown
$statuses = $pdo->query("SELECT * FROM survey_statuses")->fetchAll(PDO::FETCH_ASSOC);
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
                <!-- Filters Section -->
                <div class="filters-section">
                    <form method="get" class="filter-form">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $status == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="category">Category:</label>
                            <select name="category" id="category">
                                <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $category == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group search-group">
                            <input type="text" name="search" placeholder="Search surveys..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <button type="submit" class="filter-button">Apply Filters</button>
                        <a href="surveys.php" class="btn btn-secondary">Reset Filters</a>
                    </form>
                </div>
                
                <!-- Surveys Table -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-poll"></i> Survey List</h2>
                        <a href="survey_create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Survey
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <?php if (!empty($surveys)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="sortable <?= $sort === 'title' ? 'sorted-' . $order : '' ?>">
                                            <a href="?<?= buildSortQuery('title') ?>">Title</a>
                                        </th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th class="sortable <?= $sort === 'response_count' ? 'sorted-' . $order : '' ?>">
                                            <a href="?<?= buildSortQuery('response_count') ?>">Responses</a>
                                        </th>
                                        <th>Creator</th>
                                        <th class="sortable <?= $sort === 'starts_at' ? 'sorted-' . $order : '' ?>">
                                            <a href="?<?= buildSortQuery('starts_at') ?>">Start Date</a>
                                        </th>
                                        <th class="sortable <?= $sort === 'ends_at' ? 'sorted-' . $order : '' ?>">
                                            <a href="?<?= buildSortQuery('ends_at') ?>">End Date</a>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($surveys as $survey): ?>
                                        <tr>
                                            <td>
                                                <a href="survey_view.php?id=<?= $survey['id'] ?>" class="survey-title">
                                                    <?= htmlspecialchars($survey['title']) ?>
                                                </a>
                                                <?php if (!empty($survey['description'])): ?>
                                                    <p class="survey-description"><?= htmlspecialchars(substr($survey['description'], 0, 50)) ?><?= strlen($survey['description']) > 50 ? '...' : '' ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($survey['category_name'] ?? 'Uncategorized') ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= getStatusColor($survey['status_label']) ?>">
                                                    <i class="fas <?= htmlspecialchars($survey['status_icon']) ?>"></i>
                                                    <?= htmlspecialchars($survey['status_label']) ?>
                                                </span>
                                            </td>
                                            <td><?= $survey['response_count'] ?></td>
                                            <td><?= htmlspecialchars($survey['creator_name']) ?></td>
                                            <td><?= formatDate($survey['starts_at'], 'Y-m-d H:i:s') ?></td>
                                            <td><?= formatDate($survey['ends_at'], 'Y-m-d') ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="survey_view.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-view" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="survey_edit.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="survey_responses.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-responses" title="Responses">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <a href="survey_delete.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this survey?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- Pagination -->
                            <div class="pagination">
                                <?php if ($totalPages > 1): ?>
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                           class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-poll"></i>
                                <p>No surveys found</p>
                                <?php if ($status !== 'all' || $category !== 'all' || !empty($search)): ?>
                                    <a href="surveys.php" class="btn btn-primary">
                                        <i class="fas fa-undo"></i> Reset Filters
                                    </a>
                                <?php endif; ?>
                                <a href="survey_create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create New Survey
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/surveys.js"></script>
</body>
</html>