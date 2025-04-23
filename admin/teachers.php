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

// Ensure all users with teacher role are in teachers table
$teacherUsers = $pdo->query("SELECT u.id FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'teacher'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($teacherUsers as $user_id) {
    $exists = $pdo->prepare("SELECT id FROM teachers WHERE user_id=?");
    $exists->execute([$user_id]);
    if (!$exists->fetchColumn()) {
        $pdo->prepare("INSERT INTO teachers (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
    }
}

// Fetch all users with teacher role (role_id = 2 or role_name = 'teacher')
$stmt = $pdo->query("
    SELECT 
        u.id AS user_id, u.username, u.email, 
        t.id AS teacher_id, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, t.created_at
    FROM users u
    LEFT JOIN teachers t ON t.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'teacher'
    ORDER BY u.username
");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CRUD actions for teachers
if (isset($_GET['delete_teacher']) && is_numeric($_GET['delete_teacher'])) {
    $teacher_id = intval($_GET['delete_teacher']);
    $pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([$teacher_id]);
    // Optionally, delete user as well (uncomment if needed)
    // $pdo->prepare("DELETE FROM users WHERE id=(SELECT user_id FROM teachers WHERE id=?)")->execute([$teacher_id]);
    header("Location: teachers.php?msg=Teacher+deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $qualification = trim($_POST['qualification']);
    $subject_specialization = trim($_POST['subject_specialization']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);
    $status = trim($_POST['status']);
    $pdo->prepare("UPDATE teachers SET qualification=?, subject_specialization=?, date_of_birth=?, gender=?, address=?, status=? WHERE id=?")
        ->execute([$qualification, $subject_specialization, $date_of_birth, $gender, $address, $status, $teacher_id]);
    header("Location: teachers.php?msg=Teacher+updated");
    exit;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }
        .admin-main {
            margin-left: 250px;
        }
        .dashboard-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            margin-bottom: 2rem;
            padding: 2rem 2.5rem;
        }
        .teachers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .teachers-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #215967;
            font-weight: 600;
        }
        .erpnext-btn, .btn, .btn-secondary, .btn-success {
            display: inline-block;
            padding: 8px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 8px;
            text-decoration: none;
        }
        .erpnext-btn:hover, .btn:hover, .btn-secondary:hover, .btn-success:hover {
            background: #e2efda;
            color: #215967;
        }
        .btn-success {
            background: #27ae60;
            color: #fff;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-sm, .erpnext-btn.btn-sm {
            padding: 4px 14px;
            font-size: 13px;
        }
        .excel-table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #bdbdbd;
            padding: 10px 12px;
            text-align: left;
            font-size: 1em;
        }
        .excel-table th {
            background: #e2efda;
            color: #215967;
            font-weight: bold;
        }
        .excel-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .excel-table tr:hover {
            background: #f4f8fb;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1rem; }
            .teachers-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .excel-table th, .excel-table td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967; font-weight:700;"><?= esc($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <div class="teachers-header">
                        <h2>Teacher List</h2>
                        <div>
                            <a href="add_teacher.php" class="erpnext-btn btn-sm btn-success"><i class="fas fa-plus"></i> Add Teacher</a>
                            <a href="view_teacher.php" class="erpnext-btn btn-sm btn-secondary"><i class="fas fa-eye"></i> View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="excel-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Qualification</th>
                                    <th>Subject Specialization</th>
                                    <th>Date of Birth</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?= esc($teacher['user_id']) ?></td>
                                            <td><?= esc($teacher['username']) ?></td>
                                            <td><?= esc($teacher['email']) ?></td>
                                            <td><?= esc($teacher['qualification'] ?? '-') ?></td>
                                            <td><?= esc($teacher['subject_specialization'] ?? '-') ?></td>
                                            <td><?= esc($teacher['date_of_birth'] ?? '-') ?></td>
                                            <td><?= esc($teacher['gender'] ?? '-') ?></td>
                                            <td><?= esc($teacher['address'] ?? '-') ?></td>
                                            <td><?= esc($teacher['status'] ?? '-') ?></td>
                                            <td><?= esc($teacher['created_at'] ?? '-') ?></td>
                                            <td>
                                                <a href="teachers.php?edit_teacher=<?= esc($teacher['teacher_id']) ?>" class="erpnext-btn btn-sm btn-secondary">Edit</a>
                                                <a href="teachers.php?delete_teacher=<?= esc($teacher['teacher_id']) ?>" class="erpnext-btn btn-sm btn-danger" onclick="return confirm('Delete this teacher?')">Delete</a>
                                            </td>
                                        </tr>
                                        <?php if (isset($_GET['edit_teacher']) && $_GET['edit_teacher'] == $teacher['teacher_id']): ?>
                                        <tr>
                                            <td colspan="11">
                                                <form method="post" style="display:flex;gap:1rem;align-items:center;">
                                                    <input type="hidden" name="teacher_id" value="<?= esc($teacher['teacher_id']) ?>">
                                                    <label>Qualification:
                                                        <input type="text" name="qualification" value="<?= esc($teacher['qualification']) ?>">
                                                    </label>
                                                    <label>Subject Specialization:
                                                        <input type="text" name="subject_specialization" value="<?= esc($teacher['subject_specialization']) ?>">
                                                    </label>
                                                    <label>Date of Birth:
                                                        <input type="date" name="date_of_birth" value="<?= esc($teacher['date_of_birth']) ?>">
                                                    </label>
                                                    <label>Gender:
                                                        <input type="text" name="gender" value="<?= esc($teacher['gender']) ?>">
                                                    </label>
                                                    <label>Address:
                                                        <input type="text" name="address" value="<?= esc($teacher['address']) ?>">
                                                    </label>
                                                    <label>Status:
                                                        <input type="text" name="status" value="<?= esc($teacher['status']) ?>">
                                                    </label>
                                                    <button type="submit" name="edit_teacher" class="erpnext-btn btn-sm btn-success">Save</button>
                                                    <a href="teachers.php" class="erpnext-btn btn-sm btn-secondary">Cancel</a>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11">No teachers found.</td>
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
