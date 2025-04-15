<?php
// admin/all_responses.php
// Show all survey responses for admin, with no filtering by survey_id
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

$pageTitle = 'All Survey Responses';

// Pagination setup
$per_page = 30;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));
$offset = ($page - 1) * $per_page;

// Get total responses count
$total_stmt = $pdo->query("SELECT COUNT(*) FROM survey_responses");
$total_responses = $total_stmt->fetchColumn();
$total_pages = max(1, ceil($total_responses / $per_page));

// Get paginated responses (with survey and user info)
$response_stmt = $pdo->prepare("
    SELECT sr.*, s.title AS survey_title, u.username, u.email, r.role_name
    FROM survey_responses sr
    LEFT JOIN surveys s ON sr.survey_id = s.id
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$response_stmt->execute([$per_page, $offset]);
$responses = $response_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .response-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .response-table th, .response-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .response-table th { background: #f8f9fa; }
        .pagination { margin: 30px 0 0 0; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-header">
            <h1>All Survey Responses</h1>
        </header>
        <div class="table-responsive">
            <table class="response-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Survey</th>
                        <th>Respondent</th>
                        <th>Role</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($responses as $index => $response): ?>
                    <tr>
                        <td><?= $index + 1 + $offset ?></td>
                        <td><?= htmlspecialchars($response['survey_title'] ?? 'Unknown') ?></td>
                        <td><?= $response['username'] ? htmlspecialchars($response['username']) : '<span class="text-muted">Anonymous</span>' ?></td>
                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                        <td>
                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php require_once 'includes/admin_footer.php'; ?>
</body>
</html>
