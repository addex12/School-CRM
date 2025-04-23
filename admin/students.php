<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Students";

// Fetch students with class and section info (only users with 'student' role)
// FIX: Remove user_roles join (table does not exist), use users.role_id = 4 for student role
$stmt = $pdo->query("
    SELECT s.*, u.username, u.email, c.class_name, sec.section_name
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN enrollments e ON s.id = e.student_id
    LEFT JOIN batches b ON e.batch_id = b.id
    LEFT JOIN sections sec ON b.section_id = sec.id
    WHERE u.role_id = 4
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
        .erpnext-btn, .btn, .btn-secondary {
            display: inline-block;
            padding: 6px 18px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #222d32;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 4px;
        }
        .erpnext-btn:hover, .btn:hover, .btn-secondary:hover {
            background: #e2efda;
            color: #215967;
        }
        .btn-secondary {
            background: #eaeaea;
            color: #666;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-success {
            background: #27ae60;
            color: #fff;
        }
        .btn-sm, .erpnext-btn.btn-sm {
            padding: 4px 12px;
            font-size: 13px;
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
                    <h2>Bulk Assign Students to Classes/Sections</h2>
                    <?php if ($bulk_error): ?><div class="error"><?= htmlspecialchars($bulk_error) ?></div><?php endif; ?>
                    <?php if ($bulk_success): ?><div class="success"><?= htmlspecialchars($bulk_success) ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <input type="file" name="csv_file" accept=".csv" required>
                        <button type="submit" name="bulk_assign" class="erpnext-btn btn-sm btn-success">Bulk Assign</button>
                        <a href="students.php?export=1" class="erpnext-btn btn-sm btn-secondary">Export Students</a>
                        <a href="download_template.php?type=students_assign" class="erpnext-btn btn-sm btn-secondary">Download CSV Template</a>
                    </form>
                    <p>CSV columns: username, class_id, section_id (section_id optional)</p>
                </div>
                <div class="dashboard-section">
                    <h2>Assign Student to Class/Section</h2>
                    <form method="post">
                        <label>Student:
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Class:
                            <select name="class_id" id="class_id_select" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Section (optional):
                            <select name="section_id" id="section_id_select">
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>" data-class="<?= $sec['class_id'] ?>"><?= htmlspecialchars($sec['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" name="assign_single" class="erpnext-btn btn-sm btn-success">Assign</button>
                    </form>
                </div>
                <div class="dashboard-section">
                    <h2>Student List</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Section</th>
                                <!-- ...other columns... -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['username']) ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['class_name']) ?></td>
                                    <td><?= htmlspecialchars($s['section_name'] ?? '-') ?></td>
                                    <!-- ...other columns... -->
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Filter sections based on selected class
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('class_id_select');
            const sectionSelect = document.getElementById('section_id_select');
            const allOptions = Array.from(sectionSelect.options);

            function filterSections() {
                const classId = classSelect.value;
                // Always keep the first option (Select Section)
                sectionSelect.innerHTML = '';
                allOptions.forEach(opt => {
                    if (!opt.value || !classId || opt.getAttribute('data-class') === classId) {
                        sectionSelect.appendChild(opt.cloneNode(true));
                    }
                });
            }

            classSelect.addEventListener('change', filterSections);
            filterSections(); // Initial filter
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
