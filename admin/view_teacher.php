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

$pageTitle = "View Teachers";

// Fetch all teachers with user info and created_at
$stmt = $pdo->query("
    SELECT t.id AS teacher_id, u.username, u.email, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, t.created_at
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY u.username
");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subject assignments for all teachers
$assignments = [];
$assign_stmt = $pdo->query("
    SELECT ts.teacher_id, c.class_name, sec.section_name, s.subject_name
    FROM teacher_subjects ts
    JOIN class_subjects cs ON ts.class_subject_id = cs.id
    JOIN classes c ON cs.class_id = c.id
    JOIN sections sec ON ts.section_id = sec.id
    JOIN subjects s ON cs.subject_id = s.id
");
foreach ($assign_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $assignments[$row['teacher_id']][] = $row['class_name'] . ' - ' . $row['section_name'] . ' : ' . $row['subject_name'];
}

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
    <style>
        .dashboard-section {
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            max-width: 1200px;
        }
        .teacher-table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        tr:hover {
            background: #f4f8fb;
        }
        .actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .actions a:last-child {
            margin-right: 0;
        }
        .assignment-list {
            margin: 0;
            padding-left: 1.2em;
            font-size: 0.97em;
            color: #2d6a4f;
        }
        .assignment-list li {
            margin-bottom: 0.2em;
        }
        .teacher-details {
            font-size: 0.97em;
            color: #555;
        }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1rem 0.5rem; }
            table { min-width: 900px; }
        }
        @media (max-width: 600px) {
            .dashboard-section { padding: 0.5rem 0.2rem; }
            th, td { padding: 8px 6px; }
            table { min-width: 700px; }
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
                    <h2 style="margin-bottom:1.5rem;">Teacher List</h2>
                    <div class="teacher-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Qualification</th>
                                    <th>Subject Specialization</th>
                                    <th>Date of Birth</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Assignments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?= esc($teacher['teacher_id']) ?></td>
                                            <td>
                                                <div><?= esc($teacher['username']) ?></div>
                                                <div class="teacher-details">User ID: <?= esc($teacher['teacher_id']) ?></div>
                                            </td>
                                            <td><?= esc($teacher['email']) ?></td>
                                            <td><?= esc($teacher['qualification'] ?? '-') ?></td>
                                            <td><?= esc($teacher['subject_specialization'] ?? '-') ?></td>
                                            <td><?= esc($teacher['date_of_birth'] ?? '-') ?></td>
                                            <td><?= esc($teacher['gender'] ?? '-') ?></td>
                                            <td><?= esc($teacher['address'] ?? '-') ?></td>
                                            <td><?= esc($teacher['status'] ?? '-') ?></td>
                                            <td>
                                                <?= $teacher['created_at'] ? date('M j, Y', strtotime($teacher['created_at'])) : '-' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($assignments[$teacher['teacher_id']])): ?>
                                                    <ul class="assignment-list">
                                                        <?php foreach ($assignments[$teacher['teacher_id']] as $as): ?>
                                                            <li><?= esc($as) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span style="color:#888;">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <a href="edit_teacher.php?id=<?= esc($teacher['teacher_id']) ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12">No teachers found.</td>
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
