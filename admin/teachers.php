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
require_once '../includes/db.php';

$pageTitle = "Teachers";

// Fetch all teacher data with related information
$stmt = $pdo->prepare("
    SELECT 
        t.id AS teacher_id,
        t.name,
        t.email,
        t.username,
        t.address,
        t.date_of_birth,
        t.gender,
        t.qualification,
        t.subject_specialization,
        t.status,
        t.created_at,
        t.updated_at,
        sub.subject_name,
        cn.grade AS class_grade,
        sec.section_name,
        cls.class_name,
        u.id AS user_id,
        r.role_name
    FROM teachers t
    LEFT JOIN users u ON t.username = u.username
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN subjects sub ON t.subject_id = sub.id
    LEFT JOIN class_names cn ON t.class_name_id = cn.id
    LEFT JOIN sections sec ON t.section_id = sec.id
    LEFT JOIN classes cls ON t.class_id = cls.id
    ORDER BY t.name ASC
");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $teacher_ids = $_POST['teacher_ids'] ?? [];
    
    if (!empty($teacher_ids)) {
        $placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
        
        try {
            if ($action === 'delete') {
                // Delete teachers and associated user accounts
                $stmt = $pdo->prepare("
                    DELETE t, u FROM teachers t
                    JOIN users u ON t.username = u.username
                    WHERE t.id IN ($placeholders)
                ");
                $stmt->execute($teacher_ids);
                $message = count($teacher_ids) . " teacher(s) deleted successfully.";
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare("
                    UPDATE teachers t
                    JOIN users u ON t.username = u.username
                    SET t.status = 'Active', u.is_active = 1
                    WHERE t.id IN ($placeholders)
                ");
                $stmt->execute($teacher_ids);
                $message = count($teacher_ids) . " teacher(s) activated successfully.";
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("
                    UPDATE teachers t
                    JOIN users u ON t.username = u.username
                    SET t.status = 'Inactive', u.is_active = 0
                    WHERE t.id IN ($placeholders)
                ");
                $stmt->execute($teacher_ids);
                $message = count($teacher_ids) . " teacher(s) deactivated successfully.";
            }
            
            header("Location: teachers.php?success=" . urlencode($message));
            exit;
        } catch (PDOException $e) {
            $error = "Error performing bulk action: " . $e->getMessage();
            header("Location: teachers.php?error=" . urlencode($error));
            exit;
        }
    }
}

// Handle import/export messages
if (isset($_GET['imported'])) {
    $imported = intval($_GET['imported']);
    $failed = intval($_GET['failed'] ?? 0);
    $message = "Imported $imported teachers successfully" . ($failed > 0 ? " ($failed failed)" : "");
}
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
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            background: #fff;
        }
        .teachers-table {
            width: 100%;
            border-collapse: collapse;
        }
        .teachers-table th, .teachers-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            vertical-align: middle;
        }
        .teachers-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .teachers-table tr:hover {
            background: #f4f8fb;
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
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        .teacher-actions {
            white-space: nowrap;
        }
        .teacher-actions a {
            background: #eaf6ff;
            border-radius: 6px;
            padding: 6px 10px;
            margin-right: 6px;
            color: #3498db;
            font-size: 1.1em;
            transition: background 0.18s;
            display: inline-block;
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
        .import-export-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 1.2rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .import-export-bar .btn {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 0.5rem 1.1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.18s;
        }
        .import-export-bar .btn:hover {
            background: #219150;
        }
        .import-export-bar .btn-secondary {
            background: #2980b9;
        }
        .import-export-bar .btn-secondary:hover {
            background: #1c5d8c;
        }
        .import-export-bar input[type="file"] {
            display: none;
        }
        .import-export-bar label {
            background: #2980b9;
            color: #fff;
            border-radius: 6px;
            padding: 0.5rem 1.1rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.18s;
            margin: 0;
        }
        .import-export-bar label:hover {
            background: #1c5d8c;
        }
        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        .bulk-actions select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .bulk-actions button {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .bulk-actions button:hover {
            background: #c0392b;
        }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1.2rem 0.5rem; }
            .import-export-bar { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .dashboard-section { padding: 0.7rem 0.2rem; }
            .teacher-avatar { width: 30px; height: 30px; font-size: 0.95rem; }
            .teachers-table th, .teachers-table td { padding: 8px 6px; }
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
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>
                <?php if (isset($message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="dashboard-section">
                    <div class="teachers-header">
                        <h2>Manage Teachers</h2>
                        <div>
                            <a href="add_teacher.php" class="btn"><i class="fas fa-plus"></i> Add Teacher</a>
                        </div>
                    </div>

                    <div class="import-export-bar">
                        <a href="download_teacher_template.php" class="btn"><i class="fas fa-download"></i> Download Template</a>
                        <form action="import_teachers.php" method="post" enctype="multipart/form-data" style="display:inline;">
                            <label for="import-file"><i class="fas fa-upload"></i> Import Teachers</label>
                            <input type="file" id="import-file" name="import_file" accept=".csv" required>
                            <button type="submit" class="btn-secondary" style="display:none;">Submit</button>
                        </form>
                        <a href="export_teachers.php" class="btn"><i class="fas fa-file-export"></i> Export Teachers</a>
                    </div>

                    <form method="post" id="bulk-action-form">
                        <div class="bulk-actions">
                            <select name="bulk_action" required>
                                <option value="">-- Bulk Actions --</option>
                                <option value="activate">Activate</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" onclick="return confirm('Are you sure you want to perform this action on selected teachers?')">Apply</button>
                        </div>

                        <div class="table-responsive">
                            <table class="teachers-table">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" id="select-all"></th>
                                        <th>Teacher</th>
                                        <th>Contact</th>
                                        <th>Subject</th>
                                        <th>Class/Grade</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($teachers)): ?>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <tr>
                                                <td><input type="checkbox" name="teacher_ids[]" value="<?= $teacher['teacher_id'] ?>"></td>
                                                <td>
                                                    <span class="teacher-avatar">
                                                        <?php
                                                        $initials = '';
                                                        if (!empty($teacher['name'])) {
                                                            $parts = explode(' ', $teacher['name']);
                                                            foreach ($parts as $p) { 
                                                                $initials .= strtoupper($p[0]); 
                                                                if (strlen($initials) == 2) break; 
                                                            }
                                                        } else {
                                                            $initials = strtoupper(substr($teacher['username'], 0, 2));
                                                        }
                                                        echo htmlspecialchars($initials);
                                                        ?>
                                                    </span>
                                                    <?= htmlspecialchars($teacher['name']) ?>
                                                    <span class="badge-role"><?= htmlspecialchars($teacher['role_name'] ?? 'Teacher') ?></span>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($teacher['email']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($teacher['username']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($teacher['subject_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if ($teacher['class_name'] || $teacher['class_grade']): ?>
                                                        <div><?= htmlspecialchars($teacher['class_name'] ?? 'N/A') ?></div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($teacher['class_grade'] ?? '') ?>
                                                            <?= $teacher['section_name'] ? ' - ' . htmlspecialchars($teacher['section_name']) : '' ?>
                                                        </small>
                                                    <?php else: ?>
                                                        Unassigned
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="<?= strtolower($teacher['status']) === 'active' ? 'status-active' : 'status-inactive' ?>">
                                                        <?= htmlspecialchars($teacher['status'] ?? 'Inactive') ?>
                                                    </span>
                                                </td>
                                                <td class="teacher-actions">
                                                    <a href="edit_teacher.php?edit_id=<?= $teacher['teacher_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="delete_teacher.php?id=<?= $teacher['teacher_id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this teacher?')"><i class="fas fa-trash-alt"></i></a>
                                                    <a href="view_teacher.php?id=<?= $teacher['teacher_id'] ?>" title="View"><i class="fas fa-eye"></i></a>
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
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Toggle select all checkboxes
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="teacher_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Auto-submit import form when file is selected
        document.getElementById('import-file').addEventListener('change', function() {
            if (this.files.length) {
                this.form.submit();
            }
        });

        // Confirm before bulk actions
        document.getElementById('bulk-action-form').addEventListener('submit', function(e) {
            const selectedAction = this.elements['bulk_action'].value;
            const checkedBoxes = document.querySelectorAll('input[name="teacher_ids[]"]:checked');
            
            if (!selectedAction) {
                e.preventDefault();
                alert('Please select a bulk action');
                return false;
            }
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one teacher');
                return false;
            }
            
            if (selectedAction === 'delete') {
                if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} teacher(s)? This cannot be undone.`)) {
                    e.preventDefault();
                    return false;
                }
            } else {
                if (!confirm(`Are you sure you want to ${selectedAction} ${checkedBoxes.length} teacher(s)?`)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>