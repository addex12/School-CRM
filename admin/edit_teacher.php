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

// Fetch available classes
$class_stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = [];
while ($row = $class_stmt->fetch(PDO::FETCH_ASSOC)) {
    // Avoid undefined index warning by checking if 'id' exists
    $row_id = isset($row['id']) ? $row['id'] : null;
    $row_name = isset($row['class_name']) ? $row['class_name'] : '';
    $classes[] = [
        'id' => $row_id,
        'class_name' => $row_name
    ];
}

// Fetch subjects taught by this teacher
$subjects_stmt = $pdo->prepare("
    SELECT ts.id, ts.subject_id, ts.class_id, ts.section_id, 
           s.subject_name, c.class_name, sec.section_name
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    LEFT JOIN classes c ON ts.class_id = c.id
    LEFT JOIN sections sec ON ts.section_id = sec.id
    WHERE ts.teacher_id = ?
    ORDER BY c.class_name, sec.section_name, s.subject_name
");
$subjects_stmt->execute([$teacher_id]);
$teacher_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available subjects
$all_subjects_stmt = $pdo->query("
    SELECT s.id, s.subject_name, c.class_name, s.curriculum_id
    FROM subjects s
    JOIN curriculums cu ON s.curriculum_id = cu.id
    LEFT JOIN classes c ON cu.id = c.curriculum_id
    ORDER BY s.subject_name
");
$all_subjects = $all_subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections
$sections_stmt = $pdo->query("SELECT id, section_name, class_id FROM sections ORDER BY class_id, section_name");
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);

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
            gender = ?, address = ?, status = ?
        WHERE id = ?
    ");
    
    if ($update_stmt->execute([
        $qualification, $subject_specialization, $date_of_birth,
        $gender, $address, $status, $teacher_id
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
$js_all_subjects = json_encode($all_subjects);
$js_sections = json_encode($sections);
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
                                            <?php foreach ($all_subjects as $subject): ?>
                                                <option value="<?= $subject['id'] ?>">
                                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                                </option>
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
            const allSubjects = <?= $js_all_subjects ?>;
            const allSections = <?= $js_sections ?>;
            const allClasses = <?= $js_classes ?>;
            
            let assignments = [...teacherSubjects];
            renderAssignments();
            
            // Update sections dropdown when class changes
            $('#new_class_id').on('change', function() {
                const classId = $(this).val();
                const $sectionSelect = $('#new_section_id');
                
                $sectionSelect.empty().append('<option value="">-- Select Section --</option>');
                
                if (classId) {
                    const sectionsForClass = allSections.filter(section => section.class_id == classId);
                    sectionsForClass.forEach(section => {
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
                
                // Find subject details
                const subject = allSubjects.find(s => s.id == subjectId);
                const className = classId ? allClasses.find(c => c.id == classId)?.class_name : 'Any Class';
                const sectionName = sectionId ? allSections.find(s => s.id == sectionId)?.section_name : 'Any Section';
                
                assignments.push({
                    subject_id: subjectId,
                    subject_name: subject?.subject_name || '',
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
                        const subjectName = assignment.subject_name || allSubjects.find(s => s.id == assignment.subject_id)?.subject_name || 'Unknown Subject';
                        const className = assignment.class_name || (assignment.class_id ? allClasses.find(c => c.id == assignment.class_id)?.class_name : 'Any Class');
                        const sectionName = assignment.section_name || (assignment.section_id ? allSections.find(s => s.id == assignment.section_id)?.section_name : 'Any Section');
                        
                        $list.append(`
                            <div class="assignment-item">
                                <div>${subjectName}</div>
                                <div>${className}</div>
                                <div>${sectionName}</div>
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
   <?php require_once '../includes/footer.php';?>
</body>
</html>