<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start();
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

// --- List all users with student role, even if not assigned to a class/section ---
$stmt = $pdo->query("
    SELECT 
        u.id AS user_id, u.username, u.email, 
        s.id AS student_id, s.class_id, s.section_id, s.status, s.created_at
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'student'
    ORDER BY u.username
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    // Get user_id before deleting
    $user_id = $pdo->query("SELECT user_id FROM students WHERE id=" . $student_id)->fetchColumn();
    if ($user_id) {
        $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$student_id]);
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$user_id]);
    }
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

// --- AJAX handler for editing a student (class/section/status) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_edit_student'])) {
    $student_id = intval($_POST['student_id']);
    $class_id = intval($_POST['class_id']);
    $section_id = $_POST['section_id'] !== "" ? intval($_POST['section_id']) : null;
    $status = trim($_POST['status']);
    $pdo->prepare("UPDATE students SET class_id=?, section_id=?, status=? WHERE id=?")
        ->execute([$class_id, $section_id, $status, $student_id]);
    // Also update enrollments if section is set
    if ($section_id) {
        $pdo->prepare("DELETE FROM enrollments WHERE student_id=?")->execute([$student_id]);
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
        $exists = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND batch_id=?");
        $exists->execute([$student_id, $batch_id]);
        if (!$exists->fetch()) {
            $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?,?)")->execute([$student_id, $batch_id]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// --- AJAX handler for bulk assign selected students ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_bulk_assign_selected'])) {
    $selected_students = $_POST['selected_students'] ?? [];
    $class_id = intval($_POST['bulk_class_id'] ?? 0);
    $section_id = $_POST['bulk_section_id'] !== "" ? intval($_POST['bulk_section_id']) : null;
    $assigned = 0;
    foreach ($selected_students as $student_id) {
        $student_id = intval($student_id);
        if (!$student_id || !$class_id) continue;
        $pdo->prepare("UPDATE students SET class_id=?, section_id=? WHERE id=?")->execute([$class_id, $section_id, $student_id]);
        if ($section_id) {
            $pdo->prepare("DELETE FROM enrollments WHERE student_id=?")->execute([$student_id]);
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
            $exists = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND batch_id=?");
            $exists->execute([$student_id, $batch_id]);
            if (!$exists->fetch()) {
                $pdo->prepare("INSERT INTO enrollments (student_id, batch_id) VALUES (?,?)")->execute([$student_id, $batch_id]);
            }
        }
        $assigned++;
    }
    echo json_encode(['success' => true, 'assigned' => $assigned]);
    exit;
}

function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function sort_link($col, $label, $current_sort, $current_order) {
    $next_order = ($current_sort === $col && $current_order === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($current_sort === $col) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }
    $params = $_GET;
    $params['sort'] = $col;
    $params['order'] = $next_order;
    $url = strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query($params);
    return '<a href="' . esc($url) . '" style="color:inherit;text-decoration:none;">' . esc($label) . $arrow . '</a>';
}
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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
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
        .table-responsive { overflow-x: auto; }
        @media (max-width: 900px) {
            .dashboard-section { padding: 1rem; }
            .students-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
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
                    <div class="students-header">
                        <h2>Student List</h2>
                        <a href="students.php?export=1" class="erpnext-btn btn-sm btn-secondary">Export Students</a>
                        <a href="download_template.php?type=students_assign" class="erpnext-btn btn-sm btn-secondary">Download CSV Template</a>
                    </div>
                    <div class="table-responsive">
                        <!-- Search bar -->
                        <div style="margin-bottom:1.2rem;display:flex;align-items:center;gap:1rem;">
                            <input type="text" id="studentSearch" placeholder="Search students..." style="flex:1;padding:10px 16px;border:1px solid #e5e7eb;border-radius:6px;font-size:1rem;background:#f9fafb;">
                            <button class="erpnext-btn btn-primary" id="searchBtn" style="padding:10px 18px;"><i class="fas fa-search"></i> Search</button>
                            <button class="erpnext-btn btn-secondary" id="clearSearch" style="padding:10px 18px;">Clear</button>
                        </div>
                        <table class="excel-table" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTbody">
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><?= esc($s['user_id']) ?></td>
                                            <td><?= esc($s['username']) ?></td>
                                            <td><?= esc($s['email']) ?></td>
                                            <td><?= esc($s['status'] ?? '-') ?></td>
                                            <td><?= esc($s['created_at'] ?? '-') ?></td>
                                            <td>
                                                <a href="edit_user.php?id=<?= esc($s['user_id']) ?>" class="erpnext-btn btn-sm btn-secondary">Edit</a>
                                                <a href="students.php?delete_student=<?= esc($s['student_id']) ?>" class="erpnext-btn btn-sm btn-danger" onclick="return confirm('Delete this student and user?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        // Real-time search/filter functionality using JSON data
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('studentSearch');
            const searchBtn = document.getElementById('searchBtn');
            const clearBtn = document.getElementById('clearSearch');
            const tbody = document.getElementById('studentsTbody');

            // Prepare students data as JSON for client-side filtering
            const studentsData = <?=
                json_encode(array_map(function($s) {
                    return [
                        'student_id' => $s['student_id'],
                        'user_id' => $s['user_id'],
                        'username' => $s['username'],
                        'email' => $s['email'],
                        'status' => $s['status'],
                        'created_at' => $s['created_at']
                    ];
                }, $students));
            ?>;

            function renderRows(filtered) {
                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="6">No students found.</td></tr>';
                    return;
                }
                tbody.innerHTML = filtered.map(function(s) {
                    return `<tr>
                        <td>${s.user_id || ''}</td>
                        <td>${s.username || ''}</td>
                        <td>${s.email || ''}</td>
                        <td>${s.status || '-'}</td>
                        <td>${s.created_at || '-'}</td>
                        <td>
                            <a href="edit_user.php?id=${s.user_id}" class="erpnext-btn btn-sm btn-secondary">Edit</a>
                            <a href="students.php?delete_student=${s.student_id}" class="erpnext-btn btn-sm btn-danger" onclick="return confirm('Delete this student and user?')">Delete</a>
                        </td>
                    </tr>`;
                }).join('');
            }

            function filterRows() {
                const val = searchInput.value.toLowerCase();
                const filtered = studentsData.filter(function(s) {
                    return Object.values(s).join(' ').toLowerCase().includes(val);
                });
                renderRows(filtered);
            }

            // Real-time filtering as you type
            searchInput.addEventListener('input', filterRows);

            // On search button click, show only filtered results (same as real-time)
            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterRows();
            });

            // Clear search
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                renderRows(studentsData);
            });

            // Initial render
            renderRows(studentsData);
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>