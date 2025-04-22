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

$pageTitle = "Edit Teacher";

// Get teacher ID
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$teacher_id) {
    header("Location: teachers.php");
    exit;
}

// Fetch teacher info with user
$stmt = $pdo->prepare("
    SELECT t.id AS teacher_id, t.user_id, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, 
           u.username, u.email
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: teachers.php");
    exit;
}

// Fetch all classes
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections per class
$sections_stmt = $pdo->query("SELECT id, class_id, section_name FROM sections ORDER BY class_id, section_name");
$sections = [];
foreach ($sections_stmt->fetchAll(PDO::FETCH_ASSOC) as $section) {
    $sections[$section['class_id']][] = $section;
}

// Fetch all subjects per class (via class_subjects)
$class_subjects_stmt = $pdo->query("
    SELECT cs.id as class_subject_id, cs.class_id, s.subject_name, s.id as subject_id
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    ORDER BY cs.class_id, s.subject_name
");
$class_subjects = [];
foreach ($class_subjects_stmt->fetchAll(PDO::FETCH_ASSOC) as $cs) {
    $class_subjects[$cs['class_id']][] = $cs;
}

// Prepare data for JS
$js_classes = json_encode($classes);
$js_sections = json_encode($sections);
$js_class_subjects = json_encode($class_subjects);

// Fetch current assignments for this teacher
$assigned = [];
$assigned_stmt = $pdo->prepare("SELECT class_subject_id, section_id FROM teacher_subjects WHERE teacher_id = ?");
$assigned_stmt->execute([$teacher_id]);
foreach ($assigned_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $assigned[] = [
        'class_subject_id' => $row['class_subject_id'],
        'section_id' => $row['section_id']
    ];
}

// Handle update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    // Only update columns that exist in your teachers table
    $qualification = trim($_POST['qualification'] ?? '');
    $subject_specialization = trim($_POST['subject_specialization'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $status = trim($_POST['status'] ?? '');

    // Update teacher info
    $stmt = $pdo->prepare("UPDATE teachers SET qualification=?, subject_specialization=?, address=?, date_of_birth=?, gender=?, status=? WHERE id=?");
    $stmt->execute([$qualification, $subject_specialization, $address, $date_of_birth, $gender, $status, $teacher_id]);

    // Update assignments
    $new_assignments = $_POST['assignments'] ?? [];
    // Remove all old assignments
    $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$teacher_id]);
    // Insert new assignments
    foreach ($new_assignments as $assignment) {
        list($class_subject_id, $section_id) = explode('_', $assignment);
        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, class_subject_id, section_id) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_id, $class_subject_id, $section_id]);
    }

    $success = "Teacher details and assignments updated successfully!";
    // Refresh teacher data
    $stmt = $pdo->prepare("
        SELECT t.id AS teacher_id, t.user_id, t.qualification, t.subject_specialization, t.date_of_birth, t.gender, t.address, t.status, 
               u.username, u.email
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    // Refresh $assigned
    $assigned = [];
    $assigned_stmt = $pdo->prepare("SELECT class_subject_id, section_id FROM teacher_subjects WHERE teacher_id = ?");
    $assigned_stmt->execute([$teacher_id]);
    foreach ($assigned_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $assigned[] = [
            'class_subject_id' => $row['class_subject_id'],
            'section_id' => $row['section_id']
        ];
    }
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
            max-width: 700px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
        }
        label {
            display: block;
            margin-top: 1rem;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-top: 4px;
        }
        .btn {
            margin-top: 1.2rem;
        }
        .success {
            color: #27ae60;
            margin-bottom: 1rem;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        .assignment-form-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .assignment-form-row select {
            width: 180px;
            min-width: 120px;
        }
        .assignment-list {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .assignment-list-table {
            width: 100%;
            border-collapse: collapse;
        }
        .assignment-list-table th, .assignment-list-table td {
            border: 1px solid #eee;
            padding: 6px 8px;
            font-size: 0.97em;
            text-align: left;
        }
        .assignment-list-table th {
            background: #f8f9fa;
        }
        .remove-btn {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
        }
        @media (max-width: 900px) {
            .dashboard-section { max-width: 98vw; padding: 1rem 0.5rem; }
            .assignment-form-row select { width: 100px; }
        }
        @media (max-width: 600px) {
            .dashboard-section { padding: 0.5rem 0.2rem; }
            .assignment-form-row { flex-direction: column; gap: 8px; }
            .assignment-form-row select { width: 100%; }
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
                    <?php if ($success): ?>
                        <div class="success"><?= esc($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="error"><?= esc($error) ?></div>
                    <?php endif; ?>
                    <form method="post" id="teacherForm">
                        <label>Username:
                            <input type="text" value="<?= esc($teacher['username']) ?>" disabled>
                        </label>
                        <label>Email:
                            <input type="text" value="<?= esc($teacher['email']) ?>" disabled>
                        </label>
                        <label>Qualification:
                            <input type="text" name="qualification" value="<?= esc($teacher['qualification'] ?? '') ?>">
                        </label>
                        <label>Subject Specialization:
                            <input type="text" name="subject_specialization" value="<?= esc($teacher['subject_specialization'] ?? '') ?>">
                        </label>
                        <label>Address:
                            <input type="text" name="address" value="<?= esc($teacher['address'] ?? '') ?>">
                        </label>
                        <label>Date of Birth:
                            <input type="date" name="date_of_birth" value="<?= esc($teacher['date_of_birth'] ?? '') ?>">
                        </label>
                        <label>Gender:
                            <select name="gender">
                                <option value="">Select</option>
                                <option value="Male" <?= ($teacher['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($teacher['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($teacher['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </label>
                        <label>Status:
                            <input type="text" name="status" value="<?= esc($teacher['status'] ?? '') ?>">
                        </label>

                        <h3 style="margin-top:2.5rem;">Assign Classes, Sections & Subjects</h3>
                        <div class="assignment-form-row">
                            <div>
                                <label for="class_select">Class</label>
                                <select id="class_select">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div>
                                <label for="section_select">Section</label>
                                <select id="section_select" disabled>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                            <div>
                                <label for="subject_select">Subject</label>
                                <select id="subject_select" disabled>
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                            <button type="button" class="btn" id="addAssignmentBtn" style="margin-top:0;">Add Assignment</button>
                        </div>

                        <div class="assignment-list">
                            <table class="assignment-list-table" id="assignmentTable">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Subject</th>
                                        <th>Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- JS will populate -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Hidden inputs for assignments -->
                        <div id="assignmentsInputs"></div>

                        <button type="submit" name="update_teacher" class="btn">Update</button>
                        <a href="teachers.php" class="btn" style="background:#aaa;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Data from PHP
        const classes = <?= $js_classes ?>;
        const sections = <?= $js_sections ?>;
        const classSubjects = <?= $js_class_subjects ?>;
        const assignments = <?= json_encode($assigned) ?>;

        // Helper: get class name by id
        function getClassName(id) {
            const c = classes.find(x => x.id == id);
            return c ? c.class_name : '';
        }
        // Helper: get section name by id
        function getSectionName(class_id, section_id) {
            if (!sections[class_id]) return '';
            const s = sections[class_id].find(x => x.id == section_id);
            return s ? s.section_name : '';
        }
        // Helper: get subject name by class_subject_id
        function getSubjectName(class_id, class_subject_id) {
            if (!classSubjects[class_id]) return '';
            const s = classSubjects[class_id].find(x => x.class_subject_id == class_subject_id);
            return s ? s.subject_name : '';
        }

        // Populate class dropdown
        function populateClassDropdown() {
            const classSelect = document.getElementById('class_select');
            classSelect.innerHTML = '<option value="">Select Class</option>';
            classes.forEach(c => {
                classSelect.innerHTML += `<option value="${c.id}">${c.class_name}</option>`;
            });
        }
        // Populate section dropdown based on class
        function populateSectionDropdown(class_id) {
            const sectionSelect = document.getElementById('section_select');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            if (sections[class_id]) {
                sections[class_id].forEach(s => {
                    sectionSelect.innerHTML += `<option value="${s.id}">${s.section_name}</option>`;
                });
                sectionSelect.disabled = false;
            }
        }
        // Populate subject dropdown based on class
        function populateSubjectDropdown(class_id) {
            const subjectSelect = document.getElementById('subject_select');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            subjectSelect.disabled = true;
            if (classSubjects[class_id]) {
                classSubjects[class_id].forEach(s => {
                    subjectSelect.innerHTML += `<option value="${s.class_subject_id}">${s.subject_name}</option>`;
                });
                subjectSelect.disabled = false;
            }
        }

        // Assignment list (array of {class_id, section_id, class_subject_id})
        let assignmentList = [];

        // Initialize with current assignments
        function initAssignments() {
            assignments.forEach(a => {
                // Find class_id for class_subject_id
                let class_id = null;
                for (const cid in classSubjects) {
                    if (classSubjects[cid].find(s => s.class_subject_id == a.class_subject_id)) {
                        class_id = cid;
                        break;
                    }
                }
                if (class_id) {
                    assignmentList.push({
                        class_id: class_id,
                        section_id: a.section_id,
                        class_subject_id: a.class_subject_id
                    });
                }
            });
            renderAssignmentTable();
        }

        // Render assignment table and hidden inputs
        function renderAssignmentTable() {
            const tbody = document.getElementById('assignmentTable').querySelector('tbody');
            const inputsDiv = document.getElementById('assignmentsInputs');
            tbody.innerHTML = '';
            inputsDiv.innerHTML = '';
            assignmentList.forEach((a, idx) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${getClassName(a.class_id)}</td>
                        <td>${getSectionName(a.class_id, a.section_id)}</td>
                        <td>${getSubjectName(a.class_id, a.class_subject_id)}</td>
                        <td><button type="button" class="remove-btn" onclick="removeAssignment(${idx})" title="Remove">&times;</button></td>
                    </tr>
                `;
                inputsDiv.innerHTML += `<input type="hidden" name="assignments[]" value="${a.class_subject_id}_${a.section_id}">`;
            });
        }

        // Remove assignment
        function removeAssignment(idx) {
            assignmentList.splice(idx, 1);
            renderAssignmentTable();
        }

        // Add assignment
        document.getElementById('addAssignmentBtn').addEventListener('click', function() {
            const class_id = document.getElementById('class_select').value;
            const section_id = document.getElementById('section_select').value;
            const class_subject_id = document.getElementById('subject_select').value;
            if (!class_id || !section_id || !class_subject_id) {
                alert('Please select class, section, and subject.');
                return;
            }
            // Prevent duplicates
            if (assignmentList.find(a => a.class_id == class_id && a.section_id == section_id && a.class_subject_id == class_subject_id)) {
                alert('This assignment already exists.');
                return;
            }
            assignmentList.push({class_id, section_id, class_subject_id});
            renderAssignmentTable();
        });

        // Dropdown change handlers
        document.getElementById('class_select').addEventListener('change', function() {
            const class_id = this.value;
            populateSectionDropdown(class_id);
            populateSubjectDropdown(class_id);
        });

        // On page load
        populateClassDropdown();
        initAssignments();

    </script>
</body>
</html>
