<?php
/**
 * Support Tickets Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Support Tickets";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT st.*, tp.label as priority_label, tp.color as priority_color, u.username 
          FROM support_tickets st 
          JOIN ticket_priorities tp ON st.priority_id = tp.id 
          JOIN users u ON st.user_id = u.id 
          WHERE 1=1";

$params = [];

// Apply filters
if ($status !== 'all') {
    $query .= " AND st.status = ?";
    $params[] = $status;
}

if ($priority !== 'all') {
    $query .= " AND st.priority_id = ?";
    $params[] = $priority;
}

if (!empty($search)) {
    $query .= " AND (st.subject LIKE ? OR st.message LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add sorting
$query .= " ORDER BY st.created_at DESC";

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
$query .= " LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get priorities for filter dropdown
$priorities = $pdo->query("SELECT * FROM ticket_priorities")->fetchAll(PDO::FETCH_ASSOC);
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
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="on_hold" <?= $status === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="priority">Priority:</label>
                            <select name="priority" id="priority">
                                <option value="all" <?= $priority === 'all' ? 'selected' : '' ?>>All Priorities</option>
                                <?php foreach ($priorities as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $priority == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group search-group">
                            <input type="text" name="search" placeholder="Search tickets..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <button type="submit" class="filter-button">Apply Filters</button>
                    </form>
                </div>
                
                <!-- Tickets Table -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-ticket-alt"></i> Support Tickets</h2>
                        <a href="ticket_create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Ticket
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <?php if (!empty($tickets)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Subject</th>
                                        <th>User</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ticket['ticket_number']) ?></td>
                                            <td>
                                                <a href="ticket_view.php?id=<?= $ticket['id'] ?>">
                                                    <?= htmlspecialchars($ticket['subject']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($ticket['username']) ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= $ticket['priority_color'] ?>">
                                                    <?= htmlspecialchars($ticket['priority_label']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>">
                                                    <?= ucwords(str_replace('_', ' ', $ticket['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($ticket['created_at']) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-view">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="ticket_edit.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-edit">
                                                        <i class="fas fa-edit"></i>
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
                                <i class="fas fa-ticket-alt"></i>
                                <p>No tickets found</p>
                                <a href="ticket_create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create New Ticket
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>