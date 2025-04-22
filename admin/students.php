<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Students";

// Fetch students with user, role, and class info
$stmt = $pdo->query("
    SELECT s.id AS student_id, u.username, u.email, r.role_name, c.class_name, s.enrollment_no, s.created_at
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY s.created_at DESC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .students-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .students-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .students-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .students-header .btn:hover {
            background: #217dbb;
        }
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        .students-table th, .students-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .students-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .students-table tr:hover {
            background: #f4f8fb;
        }
        .student-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .student-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .students-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .students-table th, .students-table td {
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
                    <div class="students-header">
                        <h2>Student List</h2>
                        <a href="add_student.php" class="btn"><i class="fas fa-user-plus"></i> Add Student</a>
                    </div>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Class</th>
                                    <th>Enrollment No</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['username']) ?></td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= htmlspecialchars($student['role_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($student['enrollment_no']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($student['created_at'])) ?></td>
                                            <td class="student-actions">
                                                <a href="edit_student.php?id=<?= $student['student_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_student.php?id=<?= $student['student_id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No students found.</td>
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
