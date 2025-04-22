<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Teachers";

// Fetch all users with Teacher role, join teachers and classes table for info if exists
$stmt = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.username,
        u.email,
        r.role_name,
        t.id AS teacher_id,
        t.created_at AS teacher_created_at,
        c.class_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN teachers t ON t.user_id = u.id
    LEFT JOIN classes c ON t.class_id = c.id
    WHERE LOWER(r.role_name) = 'teacher'
    ORDER BY COALESCE(t.created_at, u.created_at) DESC
");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
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
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <div class="teachers-header">
                        <h2>Teacher List</h2>
                        <a href="add_teacher.php" class="btn"><i class="fas fa-user-plus"></i> Add Teacher</a>
                    </div>
                    <div class="table-responsive">
                        <table class="teachers-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Class</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($teacher['user_id']) ?></td>
                                            <td><?= htmlspecialchars($teacher['username']) ?></td>
                                            <td><?= htmlspecialchars($teacher['email']) ?></td>
                                            <td><?= htmlspecialchars($teacher['role_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($teacher['class_name'] ?? '-') ?></td>
                                            <td>
                                                <?= $teacher['teacher_created_at'] 
                                                    ? date('M j, Y g:i A', strtotime($teacher['teacher_created_at'])) 
                                                    : '-' ?>
                                            </td>
                                            <td class="teacher-actions">
                                                <?php if ($teacher['teacher_id']): ?>
                                                    <a href="edit_teacher.php?id=<?= $teacher['teacher_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="delete_teacher.php?id=<?= $teacher['teacher_id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this teacher?')"><i class="fas fa-trash-alt"></i></a>
                                                <?php else: ?>
                                                    <a href="add_teacher.php?user_id=<?= $teacher['user_id'] ?>" title="Add Assignment"><i class="fas fa-plus"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">No teachers found.</td>
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
