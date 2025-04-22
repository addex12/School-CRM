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

$pageTitle = "Teachers";

// Fetch all teachers with user and class info
$stmt = $pdo->query("
    SELECT t.id AS teacher_id, u.username, u.email, u.address, u.date_of_birth, u.gender, u.status, 
           c.class_name
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN classes c ON t.class_id = c.id
    ORDER BY u.username
");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper
function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .teachers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .teachers-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .teachers-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .teachers-header .btn:hover {
            background: #217dbb;
        }
        .teachers-table {
            width: 100%;
            border-collapse: collapse;
        }
        .teachers-table th, .teachers-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .teachers-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .teachers-table tr:hover {
            background: #f4f8fb;
        }
        .teacher-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .teacher-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .teachers-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .teachers-table th, .teachers-table td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= esc($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <div class="teachers-header">
                        <h2>Teacher List</h2>
                        <div>
                            <a href="add_teacher.php" class="btn"><i class="fas fa-plus"></i> Add Teacher</a>
                            <a href="view_teacher.php" class="btn" style="background:#16a085;"><i class="fas fa-eye"></i> View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="teachers-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Date of Birth</th>
                                    <th>Gender</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?= esc($teacher['teacher_id']) ?></td>
                                            <td><?= esc($teacher['username']) ?></td>
                                            <td><?= esc($teacher['email']) ?></td>
                                            <td><?= esc($teacher['address'] ?? '-') ?></td>
                                            <td><?= esc($teacher['date_of_birth'] ?? '-') ?></td>
                                            <td><?= esc($teacher['gender'] ?? '-') ?></td>
                                            <td><?= esc($teacher['class_name'] ?? '-') ?></td>
                                            <td><?= esc($teacher['status'] ?? '-') ?></td>
                                            <td class="teacher-actions">
                                                <a href="edit_teacher.php?id=<?= esc($teacher['teacher_id']) ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="view_teacher.php" title="View All"><i class="fas fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9">No teachers found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
