<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Teachers";

// Fetch teachers data with corrected join condition
$stmt = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.username,
        u.email,
        r.role_name,
        t.id AS teacher_id,
        t.created_at AS teacher_created_at,
        cn.grade AS class_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN teachers t ON t.user_id = u.id
    LEFT JOIN class_names cn ON t.class_name_id = cn.id
    WHERE LOWER(r.role_name) = 'teacher'
    ORDER BY COALESCE(t.created_at, u.created_at) DESC
");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle import status messages
$imported = isset($_GET['imported']) ? intval($_GET['imported']) : 0;
$failed = isset($_GET['failed']) ? intval($_GET['failed']) : 0;
$error = isset($_GET['error']) ? "Error importing file. Please try again." : '';
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
        .admin-dashboard { display: flex; min-height: 100vh; background: #f4f6fa; }
        .admin-main { flex: 1; padding: 2rem; }
        .dashboard-section { background: #fff; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .teacher-avatar { width: 40px; height: 40px; border-radius: 50%; background: #3498db; color: white; 
                          display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
        .badge-primary { background: #e3f2fd; color: #1976d2; }
        .import-section { margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; }
        .file-input { display: none; }
        .file-label { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 4px; background: #2980b9; color: white; cursor: pointer; }
        .alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
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
                    <div class="header-row">
                        <h2>Teacher Management</h2>
                        <a href="add_teacher.php" class="btn btn-primary">Add Teacher</a>
                    </div>

                    <?php if ($imported > 0): ?>
                        <div class="alert alert-success">
                            Successfully imported <?= $imported ?> teachers.
                            <?php if ($failed > 0): ?>
                                Failed to import <?= $failed ?> records.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="import-section">
                        <a href="download_teacher_template.php" class="btn btn-success">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                        <form action="import_teachers.php" method="post" enctype="multipart/form-data">
                            <input type="file" id="import-file" name="import_file" accept=".csv" class="file-input" onchange="this.form.submit()">
                            <label for="import-file" class="file-label">
                                <i class="fas fa-upload"></i> Import Teachers
                            </label>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Grade</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td>
                                                <span class="teacher-avatar">
                                                    <?= strtoupper(substr($teacher['username'], 0, 2)) ?>
                                                </span>
                                                <?= htmlspecialchars($teacher['user_id']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($teacher['username']) ?></td>
                                            <td><?= htmlspecialchars($teacher['email']) ?></td>
                                            <td><span class="badge badge-primary"><?= htmlspecialchars($teacher['role_name'] ?? 'N/A') ?></span></td>
                                            <td><?= !empty($teacher['class_name']) ? htmlspecialchars($teacher['class_name']) : 'Unassigned' ?></td>
                                            <td><?= $teacher['teacher_created_at'] ? date('M j, Y g:i A', strtotime($teacher['teacher_created_at'])) : '-' ?></td>
                                            <td>
                                                <?php if ($teacher['teacher_id']): ?>
                                                    <a href="edit_teacher.php?edit_id=<?= $teacher['teacher_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
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
    </div>
</body>
</html>