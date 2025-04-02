<?php
/**
 * Feedback Management Page
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Feedback Management";

// Pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Build query
$query = "SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id";
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "f.status = ?";
    $params[] = $statusFilter;
}

if ($ratingFilter > 0) {
    $where[] = "f.rating = ?";
    $params[] = $ratingFilter;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM (" . $query . ") as total";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Add sorting and pagination - fixed version
$query .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
$params[] = (int)$itemsPerPage;  // Explicitly cast to integer
$params[] = (int)$offset;        // Explicitly cast to integer

// Fetch feedback
$stmt = $pdo->prepare($query);

// Bind parameters with explicit type for LIMIT/OFFSET
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $paramType);
}

$stmt->execute();
$feedbackItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Status counts for filters
$statusCounts = [];
$statusQuery = "SELECT status, COUNT(*) as count FROM feedback GROUP BY status";
$stmt = $pdo->query($statusQuery);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCounts[$row['status']] = $row['count'];
}

// Rating counts for filters
$ratingCounts = [];
$ratingQuery = "SELECT rating, COUNT(*) as count FROM feedback GROUP BY rating";
$stmt = $pdo->query($ratingQuery);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ratingCounts[$row['rating']] = $row['count'];
}

// Load configuration
$feedbackConfig = json_decode(file_get_contents('../assets/config/feedback.json'), true);
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
    <script src="../assets/js/feedback.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Manage user feedback and ratings</p>
                </div>
                <div class="header-right">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Filters Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-filter"></i> Filters</h2>
                    </div>
                    <div class="filters-container">
                        <form id="feedback-filters" method="get" class="filter-form">
                            <div class="filter-group">
                                <label for="status-filter">Status:</label>
                                <select id="status-filter" name="status" class="filter-select">
                                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All (<?= array_sum($statusCounts) ?>)</option>
                                    <?php foreach ($feedbackConfig['status_options'] as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                            <?= $label ?> (<?= $statusCounts[$value] ?? 0 ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="rating-filter">Rating:</label>
                                <select id="rating-filter" name="rating" class="filter-select">
                                    <option value="0" <?= $ratingFilter === 0 ? 'selected' : '' ?>>All Ratings</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" <?= $ratingFilter === $i ? 'selected' : '' ?>>
                                            <?= str_repeat('★', $i) ?> (<?= $ratingCounts[$i] ?? 0 ?>)
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="filter-button">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            
                            <a href="feedback.php" class="reset-button">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        </form>
                    </div>
                </div>
                
                <!-- Feedback List Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-comments"></i> Feedback List</h2>
                        <div class="section-actions">
                            <span class="results-count">
                                Showing <?= count($feedbackItems) ?> of <?= $totalItems ?> items
                            </span>
                            <div class="export-actions">
                                <button id="export-csv" class="export-button">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button id="export-pdf" class="export-button">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedbackItems)): ?>
                                    <?php foreach ($feedbackItems as $item): ?>
                                        <tr data-feedback-id="<?= $item['id'] ?>">
                                            <td><?= $item['id'] ?></td>
                                            <td>
                                                <?= $item['user_id'] ? htmlspecialchars($item['username']) : 'Anonymous' ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['subject']) ?></td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $item['rating'] ? 'filled' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= str_replace('_', '-', $item['status']) ?>">
                                                    <?= $feedbackConfig['status_options'][$item['status']] ?? $item['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($item['created_at']) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="view-button" data-id="<?= $item['id'] ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="status-button" data-id="<?= $item['id'] ?>" data-status="<?= $item['status'] ?>">
                                                        <i class="fas fa-edit"></i> Status
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-data">No feedback found matching your criteria.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalItems > $itemsPerPage): ?>
                        <div class="pagination-container">
                            <div class="pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link first">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link prev">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $totalPages = ceil($totalItems / $itemsPerPage);
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);
                                
                                if ($startPage > 1) {
                                    echo '<span class="page-dots">...</span>';
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor;
                                
                                if ($endPage < $totalPages) {
                                    echo '<span class="page-dots">...</span>';
                                }
                                ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="page-link next">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link last">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feedback Detail Modal -->
    <div id="feedback-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Feedback Details</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="feedback-detail">
                    <div class="detail-row">
                        <span class="detail-label">ID:</span>
                        <span class="detail-value" id="detail-id"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">User:</span>
                        <span class="detail-value" id="detail-user"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value" id="detail-date"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Rating:</span>
                        <span class="detail-value" id="detail-rating"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" id="detail-status"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subject:</span>
                        <span class="detail-value" id="detail-subject"></span>
                    </div>
                    <div class="detail-row full-width">
                        <span class="detail-label">Message:</span>
                        <div class="detail-message" id="detail-message"></div>
                    </div>
                    <div class="detail-row full-width" id="admin-notes-container">
                        <span class="detail-label">Admin Notes:</span>
                        <textarea class="detail-notes" id="detail-notes" placeholder="Add your notes here..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="save-notes" class="save-button">
                    <i class="fas fa-save"></i> Save Notes
                </button>
                <button class="close-button">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Status Change Modal -->
    <div id="status-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Feedback Status</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status-feedback-id">
                <div class="status-options">
                    <?php foreach ($feedbackConfig['status_options'] as $value => $label): ?>
                        <div class="status-option">
                            <input type="radio" name="new-status" id="status-<?= $value ?>" value="<?= $value ?>">
                            <label for="status-<?= $value ?>">
                                <span class="badge badge-<?= str_replace('_', '-', $value) ?>">
                                    <?= $label ?>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button id="update-status" class="save-button">
                    <i class="fas fa-check"></i> Update Status
                </button>
                <button class="close-button">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Pass PHP data to JavaScript
        const feedbackConfig = <?= json_encode($feedbackConfig) ?>;
        const currentFilters = {
            status: '<?= $statusFilter ?>',
            rating: <?= $ratingFilter ?>,
            page: <?= $currentPage ?>
        };
    </script>
</body>
</html>