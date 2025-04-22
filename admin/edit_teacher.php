<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Edit Teacher";

// Get teacher ID from URL
$teacher_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
if (!$teacher_id) {
    header("Location: teachers.php?error=Invalid+teacher+ID");
    exit;
}

// Fetch teacher data
$teacher_stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.role_id, u.active
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$teacher_stmt->execute([$teacher_id]);
$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: teachers.php?error=Teacher+not+found");
    exit;
}

// Fetch available classes with their curriculum
$classes_stmt = $pdo->query("
    SELECT c.id, c.class_name, cu.name AS curriculum_name 
    FROM classes c
    JOIN curriculums cu ON c.curriculum_id = cu.id
    ORDER BY c.class_name
");
$classes = [];
while ($row = $classes_stmt->fetch(PDO::FETCH_ASSOC)) {
    // Use 'id' as the key for the value attribute in the select dropdown
    $classes[] = [
        'id' => isset($row['id']) ? $row['id'] : '',
        'class_name' => isset($row['class_name']) ? $row['class_name'] : '',
        'curriculum_name' => isset($row['curriculum_name']) ? $row['curriculum_name'] : ''
    ];
}

// Fetch subjects grouped by curriculum
$subjects_by_curriculum = [];
$subjects_stmt = $pdo->query("
    SELECT s.id, s.subject_name, s.curriculum_id, cu.name AS curriculum_name
    FROM subjects s
    JOIN curriculums cu ON s.curriculum_id = cu.id
    ORDER BY cu.name, s.subject_name
");
while ($subject = $subjects_stmt->fetch(PDO::FETCH_ASSOC)) {
    $subjects_by_curriculum[$subject['curriculum_name']][] = $subject;
}

// Fetch subjects taught by this teacher
$teacher_subjects_stmt = $pdo->prepare("
    SELECT ts.id, cs.subject_id, cs.class_id, s.subject_name, 
           c.class_name, sec.section_name, sec.id as section_id
    FROM teacher_subjects ts
    JOIN class_subjects cs ON ts.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN classes c ON cs.class_id = c.id
    LEFT JOIN sections sec ON ts.section_id = sec.id
    WHERE ts.teacher_id = ?
    ORDER BY c.class_name, sec.section_name, s.subject_name
");
$teacher_subjects_stmt->execute([$teacher_id]);
$teacher_subjects = $teacher_subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections grouped by class
$sections_by_class = [];
$sections_stmt = $pdo->query("
    SELECT s.id, s.section_name, s.class_id, c.class_name
    FROM sections s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.section_name
");
while ($section = $sections_stmt->fetch(PDO::FETCH_ASSOC)) {
    $sections_by_class[$section['class_id']][] = $section;
}

// Handle form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic teacher info
    $qualification = $_POST['qualification'] ?? '';
    $subject_specialization = $_POST['subject_specialization'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

    // Update teacher record
    $update_stmt = $pdo->prepare("
        UPDATE teachers 
        SET qualification = ?, subject_specialization = ?, date_of_birth = ?, 
            gender = ?, address = ?, status = ?, class_id = ?
        WHERE id = ?
    ");
    
    if ($update_stmt->execute([
        $qualification, $subject_specialization, $date_of_birth,
        $gender, $address, $status, $class_id, $teacher_id
    ])) {
        $success = "Teacher information updated successfully.";
    } else {
        $error = "Failed to update teacher information.";
    }

    // Handle subject assignments
    if (isset($_POST['subject_assignments'])) {
        $new_assignments = json_decode($_POST['subject_assignments'], true);
        
        // First, delete all existing assignments
        $delete_stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
        $delete_stmt->execute([$teacher_id]);
        
        // Then add new assignments
        if (is_array($new_assignments)) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, section_id)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($new_assignments as $assignment) {
                $subject_id = intval($assignment['subject_id']);
                $class_id = !empty($assignment['class_id']) ? intval($assignment['class_id']) : null;
                $section_id = !empty($assignment['section_id']) ? intval($assignment['section_id']) : null;
                
                $insert_stmt->execute([
                    $teacher_id, $subject_id, $class_id, $section_id
                ]);
            }
        }
    }
}

// Prepare data for JS
$js_teacher_subjects = json_encode($teacher_subjects);
$js_subjects_by_curriculum = json_encode($subjects_by_curriculum);
$js_sections_by_class = json_encode($sections_by_class);
$js_classes = json_encode($classes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <style>
        .admin-dashboard { display: flex; min-height: 100vh; background: #f4f6fa; }
        .admin-main { flex: 1; padding: 2rem; }
        .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        select, input, textarea { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 4px; text-decoration: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .error { color: #dc3545; margin-bottom: 1rem; }
        .success { color: #28a745; margin-bottom: 1rem; }
        .form-row { display: flex; gap: 1rem; }
        .form-row > div { flex: 1; }
        .subject-assignments { margin-top: 2rem; }
        .assignment-list { margin-top: 1rem; }
        .assignment-item { display: flex; align-items: center; padding: 0.5rem; border-bottom: 1px solid #eee; }
        .assignment-item > div { flex: 1; }
        .assignment-actions { width: 100px; text-align: right; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .btn-danger { background: #dc3545; color: white; }
        .select2-container { width: 100% !important; margin-bottom: 1rem; }
        .subject-group { margin-bottom: 1rem; }
        .subject-group h4 { margin-bottom: 0.5rem; color: #555; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <p>Editing: <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></p>
            </header>
            <div class="content">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <form method="post" autocomplete="off">
                        <div class="form-row">
                            <div>
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" value="<?= htmlspecialchars($teacher['first_name']) ?>" readonly>
                            </div>
                            <div>
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" value="<?= htmlspecialchars($teacher['last_name']) ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div>
                                <label for="username">Username</label>
                                <input type="text" id="username" value="<?= htmlspecialchars($teacher['username']) ?>" readonly>
                            </div>
                            <div>
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?= htmlspecialchars($teacher['email']) ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div>
                                <label for="qualification">Qualification</label>
                                <input type="text" name="qualification" id="qualification" value="<?= htmlspecialchars($teacher['qualification']) ?>">
                            </div>
                            <div>
                                <label for="subject_specialization">Subject Specialization</label>
                                <input type="text" name="subject_specialization" id="subject_specialization" value="<?= htmlspecialchars($teacher['subject_specialization']) ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div>
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($teacher['date_of_birth']) ?>">
                            </div>
                            <div>
                                <label for="gender">Gender</label>
                                <select name="gender" id="gender">
                                    <option value="">-- Select --</option>
                                    <option value="Male" <?= $teacher['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $teacher['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $teacher['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="address">Address</label>
                            <textarea name="address" id="address" rows="3"><?= htmlspecialchars($teacher['address']) ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div>
                                <label for="status">Status</label>
                                <select name="status" id="status">
                                    <option value="active" <?= $teacher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $teacher['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="on_leave" <?= $teacher['status'] === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                </select>
                            </div>
                            <div>
                                <label for="class_id">Assigned Class (optional)</label>
                                <select name="class_id" id="class_id">
                                    <option value="">-- None --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= htmlspecialchars($class['id']) ?>" <?= (isset($teacher['class_id']) && $teacher['class_id'] == $class['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="subject-assignments">
                            <h3>Subject Assignments</h3>
                            <div id="new-assignment-form">
                                <div class="form-row">
                                    <div>
                                        <label for="new_subject_id">Subject</label>
                                        <select id="new_subject_id" class="select2-subject">
                                            <option value="">-- Select Subject --</option>
                                            <?php foreach ($subjects_by_curriculum as $curriculum => $subjects): ?>
                                                <optgroup label="<?= htmlspecialchars($curriculum) ?>">
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <option value="<?= $subject['id'] ?>">
                                                            <?= htmlspecialchars($subject['subject_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="new_class_id">Class (optional)</label>
                                        <select id="new_class_id" class="select2-class">
                                            <option value="">-- Select Class --</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class['id'] ?>">
                                                    <?= htmlspecialchars($class['class_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="new_section_id">Section (optional)</label>
                                        <select id="new_section_id" class="select2-section">
                                            <option value="">-- Select Section --</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" id="add-assignment" class="btn btn-primary">Add Assignment</button>
                            </div>
                            
                            <div class="assignment-list" id="assignment-list">
                                <!-- Assignment items will be added here by JavaScript -->
                            </div>
                            
                            <input type="hidden" name="subject_assignments" id="subject_assignments" value="">
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="teachers.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-subject').select2();
            $('.select2-class').select2();
            $('.select2-section').select2();
            
            // Initialize data
            const teacherSubjects = <?= $js_teacher_subjects ?>;
            const subjectsByCurriculum = <?= $js_subjects_by_curriculum ?>;
            const sectionsByClass = <?= $js_sections_by_class ?>;
            const allClasses = <?= $js_classes ?>;
            
            let assignments = [...teacherSubjects];
            renderAssignments();
            
            // Update sections dropdown when class changes
            $('#new_class_id').on('change', function() {
                const classId = $(this).val();
                const $sectionSelect = $('#new_section_id');
                
                $sectionSelect.empty().append('<option value="">-- Select Section --</option>');
                
                if (classId && sectionsByClass[classId]) {
                    sectionsByClass[classId].forEach(section => {
                        $sectionSelect.append(`<option value="${section.id}">${section.section_name}</option>`);
                    });
                }
                
                $sectionSelect.trigger('change');
            });
            
            // Add new assignment
            $('#add-assignment').on('click', function() {
                const subjectId = $('#new_subject_id').val();
                const classId = $('#new_class_id').val();
                const sectionId = $('#new_section_id').val();
                
                if (!subjectId) {
                    alert('Please select a subject');
                    return;
                }
                
                // Find subject details
                let subjectName = '';
                for (const curriculum in subjectsByCurriculum) {
                    const subject = subjectsByCurriculum[curriculum].find(s => s.id == subjectId);
                    if (subject) {
                        subjectName = subject.subject_name;
                        break;
                    }
                }
                
                // Find class name
                const className = classId ? allClasses.find(c => c.id == classId)?.class_name : 'Any Class';
                
                // Find section name
                let sectionName = 'Any Section';
                if (classId && sectionId && sectionsByClass[classId]) {
                    const section = sectionsByClass[classId].find(s => s.id == sectionId);
                    if (section) sectionName = section.section_name;
                }
                
                // Check if this assignment already exists
                const exists = assignments.some(a => 
                    a.subject_id == subjectId && 
                    a.class_id == (classId || null) && 
                    a.section_id == (sectionId || null)
                );
                
                if (exists) {
                    alert('This assignment already exists');
                    return;
                }
                
                assignments.push({
                    subject_id: subjectId,
                    subject_name: subjectName,
                    class_id: classId || null,
                    class_name: className,
                    section_id: sectionId || null,
                    section_name: sectionName
                });
                
                renderAssignments();
                
                // Reset form
                $('#new_subject_id').val('').trigger('change');
                $('#new_class_id').val('').trigger('change');
                $('#new_section_id').val('').trigger('change');
            });
            
            // Remove assignment
            $(document).on('click', '.remove-assignment', function() {
                const index = $(this).data('index');
                assignments.splice(index, 1);
                renderAssignments();
            });
            
            // Render assignments list
            function renderAssignments() {
                const $list = $('#assignment-list');
                $list.empty();
                
                if (assignments.length === 0) {
                    $list.append('<p>No assignments yet</p>');
                } else {
                    assignments.forEach((assignment, index) => {
                        $list.append(`
                            <div class="assignment-item">
                                <div>${assignment.subject_name}</div>
                                <div>${assignment.class_name}</div>
                                <div>${assignment.section_name}</div>
                                <div class="assignment-actions">
                                    <button type="button" class="btn btn-danger btn-sm remove-assignment" data-index="${index}">Remove</button>
                                </div>
                            </div>
                        `);
                    });
                }
                
                // Update hidden field with JSON data
                const assignmentsForSubmit = assignments.map(a => ({
                    subject_id: a.subject_id,
                    class_id: a.class_id || null,
                    section_id: a.section_id || null
                }));
                
                $('#subject_assignments').val(JSON.stringify(assignmentsForSubmit));
            }
        });
    </script>
</body>
</html>