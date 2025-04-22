<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Teachers";

// Only fetch and show important details: user_id, username, email, role_name, class_name, created_at
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
        .dashboard-section {
            background: linear-gradient(135deg, #f8fafc 80%, #e3e9f7 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(44,62,80,0.10);
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
            transition: box-shadow 0.2s;
        }
        .dashboard-section:hover {
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
        }
        .teachers-header h2 {
            font-size: 1.5rem;
            color: #2d3a4b;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .teachers-header .btn {
            background: linear-gradient(90deg, #3498db 60%, #217dbb 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            padding: 10px 28px;
            margin-left: 10px;
            transition: background 0.18s;
        }
        .teachers-header .btn:hover {
            background: #00509e;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            background: #fff;
        }
        .teachers-table th, .teachers-table td {
            vertical-align: middle;
        }
        .teacher-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #3498db;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
        }
        .badge-role {
            background: #eaf6ff;
            color: #3498db;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.95em;
            font-weight: 600;
            margin-left: 6px;
        }
        .teacher-actions a {
            background: #eaf6ff;
            border-radius: 6px;
            padding: 6px 10px;
            margin-right: 6px;
            color: #3498db;
            font-size: 1.1em;
            transition: background 0.18s;
        }
        .teacher-actions a:hover {
            background: #3498db;
            color: #fff;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .search-bar input {
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 8px 14px;
            font-size: 1em;
            width: 220px;
        }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1.2rem 0.5rem; }
        }
        @media (max-width: 600px) {
            .dashboard-section { padding: 0.7rem 0.2rem; }
            .teacher-avatar { width: 30px; height: 30px; font-size: 0.95rem; }
        }
        .import-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 1.2rem;
            align-items: center;
        }
        .import-bar .btn {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 0.5rem 1.1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.18s;
        }
        .import-bar .btn:hover {
            background: #219150;
        }
        .import-bar input[type="file"] {
            display: none;
        }
        .import-bar label {
            background: #2980b9;
            color: #fff;
            border-radius: 6px;
            padding: 0.5rem 1.1rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.18s;
        }
        .import-bar label:hover {
            background: #1c5d8c;
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

                    <div class="import-bar">
                        <a href="download_teacher_template.php" class="btn"><i class="fas fa-download"></i> Download Template</a>
                        <form action="import_teachers.php" method="post" enctype="multipart/form-data" style="display:inline;">
                            <label for="import-file"><i class="fas fa-upload"></i> Import Teachers</label>
                            <input type="file" id="import-file" name="import_file" accept=".csv" onchange="this.form.submit()">
                        </form>
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
                                            <td>
                                                <span class="teacher-avatar">
                                                    <?php
                                                    $initials = '';
                                                    if (!empty($teacher['username'])) {
                                                        $parts = explode(' ', $teacher['username']);
                                                        foreach ($parts as $p) { $initials .= strtoupper($p[0]); if (strlen($initials) == 2) break; }
                                                    } else {
                                                        $initials = strtoupper(substr($teacher['email'], 0, 2));
                                                    }
                                                    echo htmlspecialchars($initials);
                                                    ?>
                                                </span>
                                                <?= htmlspecialchars($teacher['user_id']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($teacher['username']) ?></td>
                                            <td><?= htmlspecialchars($teacher['email']) ?></td>
                                            <td><span class="badge-role"><?= htmlspecialchars($teacher['role_name'] ?? 'N/A') ?></span></td>
                                            <td>
                                                <?php
                                                echo !empty($teacher['class_name']) ? htmlspecialchars($teacher['class_name']) : '<span style="color:#888;">Unassigned</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?= $teacher['teacher_created_at'] 
                                                    ? date('M j, Y g:i A', strtotime($teacher['teacher_created_at'])) 
                                                    : '-' ?>
                                            </td>
                                            <td class="teacher-actions">
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
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Auto-submit import form when file is selected
        document.querySelectorAll('input[type="file"][name="import_file"]').forEach(function(input) {
            input.addEventListener('change', function() {
                if (this.files.length) this.form.submit();
            });
        });
    </script>
</body>
</html>
