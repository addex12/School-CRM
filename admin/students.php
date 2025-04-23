<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Students";

// --- Ensure all users with student role are in students table ---
$studentUsers = $pdo->query("SELECT u.id FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'student'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($studentUsers as $user_id) {
    $exists = $pdo->prepare("SELECT id FROM students WHERE user_id=?");
    $exists->execute([$user_id]);
    if (!$exists->fetchColumn()) {
        $pdo->prepare("INSERT INTO students (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
    }
}

// Fetch all users with student role (role_id = 4 or role_name = 'student')
$stmt = $pdo->query("
    SELECT 
        u.id AS user_id, u.username, u.email, 
        s.id AS student_id, s.class_id, s.section_id, c.class_name, sec.section_name, s.status, s.created_at
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'student'
    ORDER BY u.username
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all classes and sections for dropdowns
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);
// Fetch sections with class_id for filtering
$sections = $pdo->query("SELECT id, section_name, class_id FROM sections ORDER BY section_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle bulk assign (CSV import)
$bulk_error = $bulk_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_assign'])) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $row = 0; $assigned = 0; $errors = [];
        while (($data = fgetcsv($file)) !== false) {
            $row++;
            if ($row == 1) continue; // skip header
            $username = trim($data[0] ?? '');
            $class_id = intval($data[1] ?? 0);
            $section_id = intval($data[2] ?? 0);
            if (!$username || !$class_id) {
                $errors[] = "Row $row: Missing username or class_id";
                continue;
            }
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if (!$user) {
                $errors[] = "Row $row: User not found";
                continue;
            }
            $student_id = $pdo->query("SELECT id FROM students WHERE user_id=" . intval($user['id']))->fetchColumn();
            if (!$student_id) {
                $errors[] = "Row $row: Student not found";
                continue;
            }
            $pdo->prepare("UPDATE students SET class_id=? WHERE id=?")->execute([$class_id, $student_id]);
            if ($section_id) {
                // Remove student from all batches (sections) for this student (ensure only one section at a time)
                $remove = $pdo->prepare("
                    DELETE FROM enrollments 
                    WHERE student_id=?
                ");
                $remove->execute([$student_id]);

                // Find or create batch for this class/section
                $batch = $pdo->prepare("SELECT id FROM batches WHERE class_id=? AND section_id=?");
                $batch->execute([$class_id, $section_id]);
                $batch_id = $batch->fetchColumn();
                if (!$batch_id) {
                    // Fetch a valid program_id (first available) or fallback to a safe value
                    $program_id = $pdo->query("SELECT id FROM programs LIMIT 1")->fetchColumn();
                    if (!$program_id) {
                        // If no program exists, create one or handle error as needed
                        $pdo->prepare("INSERT INTO programs (name) VALUES ('Default Program')")->execute();
                        $program_id = $pdo->lastInsertId();
                    }
                    $pdo->prepare("INSERT INTO batches (program_id, class_id, section_id, name) VALUES (?, ?, ?, ?)")
                        ->execute([$program_id, $class_id, $section_id, "Class $class_id - Section $section_id"]);
                    $batch_id = $pdo->lastInsertId();
                }
                // Enroll student in batch
                $exists = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND batch_id=?");
                $exists->execute([$student_id, $batch_id]);
                if (!$exists->fetch()) {
                    $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?,?)")->execute([$student_id, $batch_id]);
                }
            }
            $assigned++;
        }
        fclose($file);
        $bulk_success = "$assigned students assigned. " . (count($errors) ? implode("; ", $errors) : "");
    } else {
        $bulk_error = "Please upload a valid CSV file.";
    }
}

// --- Bulk assign selected students to class/section ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_assign_selected'])) {
    $selected_students = $_POST['selected_students'] ?? [];
    $class_id = intval($_POST['bulk_class_id'] ?? 0);
    $section_id = $_POST['bulk_section_id'] !== "" ? intval($_POST['bulk_section_id']) : null;
    $assigned = 0; $errors = [];
    foreach ($selected_students as $student_id) {
        $student_id = intval($student_id);
        if (!$student_id || !$class_id) continue;
        // Assign both class and section at once
        $pdo->prepare("UPDATE students SET class_id=?, section_id=? WHERE id=?")->execute([$class_id, $section_id, $student_id]);
        if ($section_id) {
            // Remove from all batches
            $pdo->prepare("DELETE FROM enrollments WHERE student_id=?")->execute([$student_id]);
            // Find or create batch
            $batch = $pdo->prepare("SELECT id FROM batches WHERE class_id=? AND section_id=?");
            $batch->execute([$class_id, $section_id]);
            $batch_id = $batch->fetchColumn();
            if (!$batch_id) {
                $program_id = $pdo->query("SELECT id FROM programs LIMIT 1")->fetchColumn();
                if (!$program_id) {
                    $pdo->prepare("INSERT INTO programs (name) VALUES ('Default Program')")->execute();
                    $program_id = $pdo->lastInsertId();
                }
                $pdo->prepare("INSERT INTO batches (program_id, class_id, section_id, name) VALUES (?, ?, ?, ?)")
                    ->execute([$program_id, $class_id, $section_id, "Class $class_id - Section $section_id"]);
                $batch_id = $pdo->lastInsertId();
            }
            // Enroll student in batch
            $exists = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND batch_id=?");
            $exists->execute([$student_id, $batch_id]);
            if (!$exists->fetch()) {
                $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?,?)")->execute([$student_id, $batch_id]);
            }
        }
        $assigned++;
    }
    $bulk_success = "$assigned students assigned to class" . ($section_id ? " and section" : "") . ".";
}

// Handle single assign (form below)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_single'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);
    $section_id = intval($_POST['section_id'] ?? 0);

    if ($student_id && $class_id) {
        // Update student's class
        $pdo->prepare("UPDATE students SET class_id=? WHERE id=?")->execute([$class_id, $student_id]);
        if ($section_id) {
            // Remove student from all batches (sections) for this student (ensure only one section at a time)
            $remove = $pdo->prepare("
                DELETE FROM enrollments 
                WHERE student_id=?
            ");
            $remove->execute([$student_id]);

            // Find or create batch for this class/section
            $batch = $pdo->prepare("SELECT id FROM batches WHERE class_id=? AND section_id=?");
            $batch->execute([$class_id, $section_id]);
            $batch_id = $batch->fetchColumn();
            if (!$batch_id) {
                // Fetch a valid program_id (first available) or fallback to a safe value
                $program_id = $pdo->query("SELECT id FROM programs LIMIT 1")->fetchColumn();
                if (!$program_id) {
                    $pdo->prepare("INSERT INTO programs (name) VALUES ('Default Program')")->execute();
                    $program_id = $pdo->lastInsertId();
                }
                $pdo->prepare("INSERT INTO batches (program_id, class_id, section_id, name) VALUES (?, ?, ?, ?)")
                    ->execute([$program_id, $class_id, $section_id, "Class $class_id - Section $section_id"]);
                $batch_id = $pdo->lastInsertId();
            }
            // Enroll student in batch if not already enrolled
            $exists = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND batch_id=?");
            $exists->execute([$student_id, $batch_id]);
            if (!$exists->fetch()) {
                $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?,?)")->execute([$student_id, $batch_id]);
            }
        }
        // Optionally, add a success message (not required)
        header("Location: students.php?assigned=1");
        exit;
    }
}

// Export students
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['username', 'class_id', 'section_id']);
    foreach ($students as $s) {
        fputcsv($out, [$s['username'], $s['class_id'], $s['section_id'] ?? '']);
    }
    fclose($out);
    exit;
}

// Handle CRUD actions for students
if (isset($_GET['delete_student']) && is_numeric($_GET['delete_student'])) {
    $student_id = intval($_GET['delete_student']);
    $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$student_id]);
    header("Location: students.php?msg=Student+deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $student_id = intval($_POST['student_id']);
    $class_id = intval($_POST['class_id']);
    $section_id = $_POST['section_id'] !== "" ? intval($_POST['section_id']) : null;
    $status = trim($_POST['status']);
    $pdo->prepare("UPDATE students SET class_id=?, section_id=?, status=? WHERE id=?")
        ->execute([$class_id, $section_id, $status, $student_id]);
    header("Location: students.php?msg=Student+updated");
    exit;
}

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
        .students-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .students-header h2 {
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
        .form-label {
            font-weight: 500;
            color: #215967;
            margin-bottom: 4px;
            display: block;
        }
        select, input[type="file"], input[type="text"], input[type="number"], input[type="email"] {
            border: 1px solid #bdbdbd;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 1em;
            background: #f9fafb;
            margin-bottom: 10px;
            width: 100%;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1rem; }
            .students-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .excel-table th, .excel-table td { padding: 8px 6px; }
        }
        .bulk-select-bar {
            background: #e2efda;
            border-radius: 6px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .bulk-select-bar label {
            margin: 0 0.5rem 0 0;
            font-weight: 500;
            color: #215967;
        }
        .bulk-select-bar select {
            min-width: 120px;
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
                    <div class="students-header">
                        <h2>Bulk Assign Students to Classes/Sections</h2>
                    </div>
                    <?php if ($bulk_error): ?><div class="error"><?= esc($bulk_error) ?></div><?php endif; ?>
                    <?php if ($bulk_success): ?><div class="success"><?= esc($bulk_success) ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" style="margin-bottom:1.5rem;">
                        <input type="file" name="csv_file" accept=".csv" required>
                        <button type="submit" name="bulk_assign" class="erpnext-btn btn-sm btn-success">Bulk Assign</button>
                        <a href="students.php?export=1" class="erpnext-btn btn-sm btn-secondary">Export Students</a>
                        <a href="download_template.php?type=students_assign" class="erpnext-btn btn-sm btn-secondary">Download CSV Template</a>
                    </form>
                    <p style="color:#888;">CSV columns: username, class_id, section_id (section_id optional)</p>
                </div>
                <div class="dashboard-section">
                    <h2 style="color:#215967;">Assign Student to Class/Section</h2>
                    <form method="post" style="display:flex; flex-wrap:wrap; gap:1.5rem;">
                        <div style="flex:1 1 200px;">
                            <label class="form-label">Student:</label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= esc($s['student_id']) ?>"><?= esc($s['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1 1 200px;">
                            <label class="form-label">Class:</label>
                            <select name="class_id" id="class_id_select" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= esc($c['id']) ?>"><?= esc($c['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1 1 200px;">
                            <label class="form-label">Section (optional):</label>
                            <select name="section_id" id="section_id_select">
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= esc($sec['id']) ?>" data-class="<?= esc($sec['class_id']) ?>"><?= esc($sec['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="align-self:flex-end;">
                            <button type="submit" name="assign_single" class="erpnext-btn btn-sm btn-success">Assign</button>
                        </div>
                    </form>
                </div>
                <div class="dashboard-section">
                    <div class="students-header">
                        <h2>Student List</h2>
                    </div>
                    <!-- Bulk selection bar -->
                    <form method="post" id="bulkAssignForm">
                        <div class="bulk-select-bar">
                            <label><input type="checkbox" id="select_all_students"> Select All</label>
                            <label>Class:
                                <select name="bulk_class_id" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= esc($c['id']) ?>"><?= esc($c['class_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Section (optional):
                                <select name="bulk_section_id">
                                    <option value="">Select Section</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= esc($sec['id']) ?>" data-class="<?= esc($sec['class_id']) ?>"><?= esc($sec['section_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit" name="bulk_assign_selected" class="erpnext-btn btn-sm btn-success">Assign Selected</button>
                        </div>
                        <div class="table-responsive">
                            <table class="excel-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select_all_students_head"></th>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($students)): ?>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td><input type="checkbox" name="selected_students[]" value="<?= esc($s['student_id']) ?>" class="student-checkbox"></td>
                                                <td><?= esc($s['user_id']) ?></td>
                                                <td><?= esc($s['username']) ?></td>
                                                <td><?= esc($s['email']) ?></td>
                                                <td><?= esc($s['class_name'] ?? '-') ?></td>
                                                <td><?= esc($s['section_name'] ?? '-') ?></td>
                                                <td><?= esc($s['status'] ?? '-') ?></td>
                                                <td><?= esc($s['created_at'] ?? '-') ?></td>
                                                <td>
                                                    <a href="students.php?edit_student=<?= esc($s['student_id']) ?>" class="erpnext-btn btn-sm btn-secondary">Edit</a>
                                                    <a href="students.php?delete_student=<?= esc($s['student_id']) ?>" class="erpnext-btn btn-sm btn-danger" onclick="return confirm('Delete this student?')">Delete</a>
                                                </td>
                                            </tr>
                                            <?php if (isset($_GET['edit_student']) && $_GET['edit_student'] == $s['student_id']): ?>
                                            <tr>
                                                <td colspan="9">
                                                    <form method="post" style="display:flex;gap:1rem;align-items:center;">
                                                        <input type="hidden" name="student_id" value="<?= esc($s['student_id']) ?>">
                                                        <label>Class:
                                                            <select name="class_id" required>
                                                                <?php foreach ($classes as $c): ?>
                                                                    <option value="<?= esc($c['id']) ?>" <?= ($s['class_id'] == $c['id']) ? 'selected' : '' ?>><?= esc($c['class_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label>Section:
                                                            <select name="section_id">
                                                                <option value="">Select Section</option>
                                                                <?php foreach ($sections as $sec): ?>
                                                                    <option value="<?= esc($sec['id']) ?>" <?= ($s['section_id'] == $sec['id']) ? 'selected' : '' ?>><?= esc($sec['section_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label>Status:
                                                            <input type="text" name="status" value="<?= esc($s['status']) ?>">
                                                        </label>
                                                        <button type="submit" name="edit_student" class="erpnext-btn btn-sm btn-success">Save</button>
                                                        <a href="students.php" class="erpnext-btn btn-sm btn-secondary">Cancel</a>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9">No students found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Bulk select all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select_all_students');
            const selectAllHead = document.getElementById('select_all_students_head');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            function toggleAll(checked) {
                checkboxes.forEach(cb => cb.checked = checked);
            }
            if (selectAll) selectAll.addEventListener('change', e => toggleAll(e.target.checked));
            if (selectAllHead) selectAllHead.addEventListener('change', e => toggleAll(e.target.checked));
        });

        // Filter sections based on selected class (for bulk assign bar)
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.querySelector('select[name="bulk_class_id"]');
            const sectionSelect = document.querySelector('select[name="bulk_section_id"]');
            if (classSelect && sectionSelect) {
                const allOptions = Array.from(sectionSelect.options);
                function filterSections() {
                    const classId = classSelect.value;
                    sectionSelect.innerHTML = '';
                    allOptions.forEach(opt => {
                        if (!opt.value || !classId || opt.getAttribute('data-class') === classId) {
                            sectionSelect.appendChild(opt.cloneNode(true));
                        }
                    });
                }
                classSelect.addEventListener('change', filterSections);
                filterSections();
            }
        });

        // Filter sections based on selected class
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('class_id_select');
            const sectionSelect = document.getElementById('section_id_select');
            const allOptions = Array.from(sectionSelect.options);

            function filterSections() {
                const classId = classSelect.value;
                sectionSelect.innerHTML = '';
                allOptions.forEach(opt => {
                    if (!opt.value || !classId || opt.getAttribute('data-class') === classId) {
                        sectionSelect.appendChild(opt.cloneNode(true));
                    }
                });
            }

            classSelect.addEventListener('change', filterSections);
            filterSections();
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
