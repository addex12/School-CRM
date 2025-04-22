<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

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
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections grouped by class
$sections_by_class = [];
$sections = $pdo->query("SELECT id, section_name, class_id FROM sections ORDER BY class_id, section_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sections as $section) {
    $sections_by_class[$section['class_id']][] = $section;
}

// Fetch all class_subjects for lookup
$class_subjects_lookup = [];
$class_subjects_stmt = $pdo->query("SELECT id, class_id, subject_id FROM class_subjects");
while ($row = $class_subjects_stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['class_id'] . '_' . $row['subject_id'];
    $class_subjects_lookup[$key] = $row['id'];
}

// Fetch subjects taught by this teacher
$teacher_subjects_stmt = $pdo->prepare("
    SELECT 
        ts.id,
        cs.subject_id,
        cs.class_id,
        s.subject_name,
        c.class_name,
        ts.section_id,
        sec.section_name
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

    // Update teacher record
    $update_stmt = $pdo->prepare("
        UPDATE teachers 
        SET qualification = ?, subject_specialization = ?, date_of_birth = ?, 
            gender = ?, address = ?, status = ?
        WHERE id = ?
    ");
    
    $update_result = $update_stmt->execute([
        $qualification, $subject_specialization, $date_of_birth,
        $gender, $address, $status, $teacher_id
    ]);

    if ($update_result) {
        $success = "Teacher information updated successfully.";
        
        // Handle subject assignments
        if (isset($_POST['subject_assignments'])) {
            $new_assignments = json_decode($_POST['subject_assignments'], true);
            
            // First, delete all existing assignments
            $delete_stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
            $delete_result = $delete_stmt->execute([$teacher_id]);
            
            if ($delete_result && is_array($new_assignments)) {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO teacher_subjects (teacher_id, class_subject_id, section_id)
                    VALUES (?, ?, ?)
                ");
                
                foreach ($new_assignments as $assignment) {
                    $subject_id = intval($assignment['subject_id']);
                    $class_id = intval($assignment['class_id']);
                    $section_id = !empty($assignment['section_id']) ? intval($assignment['section_id']) : null;
                    
                    // Find the class_subject_id
                    $key = $class_id . '_' . $subject_id;
                    $class_subject_id = $class_subjects_lookup[$key] ?? null;
                    
                    if ($class_subject_id) {
                        $insert_stmt->execute([$teacher_id, $class_subject_id, $section_id]);
                    }
                }
                $success .= " Subject assignments updated successfully.";
            }
        }
    } else {
        $error = "Failed to update teacher information.";
    }
    
    // Refresh teacher data
    $teacher_stmt->execute([$teacher_id]);
    $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
}

// Prepare data for JS
$js_data = [
    'teacherSubjects' => $teacher_subjects,
    'classes' => $classes,
    'sectionsByClass' => $sections_by_class,
    'classSubjects' => $class_subjects_lookup
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
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
                    
                    <form method="post" autocomplete="off" id="teacherForm">
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
                        </div>
                        <div class="subject-assignments">
                            <h3>Subject Assignments</h3>
                            <div id="new-assignment-form">
                                <div class="form-row">
                                    <div>
                                        <label for="new_class_id">Class *</label>
                                        <select id="new_class_id" class="select2-class" required>
                                            <option value="">-- Select Class --</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="new_subject_id">Subject *</label>
                                        <select id="new_subject_id" class="select2-subject" required disabled>
                                            <option value="">-- Select Subject --</option>
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
        <?php include 'includes/footer.php'; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const data = <?= json_encode($js_data) ?>;
            let assignments = [...data.teacherSubjects];
            
            // Initialize Select2
            $('.select2-class').select2();
            $('.select2-subject').select2();
            $('.select2-section').select2();
            
            // Render initial assignments
            renderAssignments();
            
            // Update sections dropdown when class changes
            $('#new_class_id').on('change', function() {
                const classId = $(this).val();
                const $sectionSelect = $('#new_section_id');
                const $subjectSelect = $('#new_subject_id');
                
                // Reset and enable subject dropdown
                $subjectSelect.empty().append('<option value="">-- Select Subject --</option>');
                
                // Update sections
                $sectionSelect.empty().append('<option value="">-- Select Section --</option>');
                if (classId && data.sectionsByClass[classId]) {
                    data.sectionsByClass[classId].forEach(section => {
                        $sectionSelect.append(`<option value="${section.id}">${section.section_name}</option>`);
                    });
                }
                
                // Load subjects for this class via AJAX
                if (classId) {
                    $subjectSelect.prop('disabled', false);
                    $.ajax({
                        url: 'ajax/get_subjects.php',
                        data: { class_id: classId },
                        success: function(subjects) {
                            $subjectSelect.empty().append('<option value="">-- Select Subject --</option>');
                            subjects.forEach(subject => {
                                $subjectSelect.append(`<option value="${subject.id}">${subject.subject_name}</option>`);
                            });
                        }
                    });
                } else {
                    $subjectSelect.prop('disabled', true);
                }
            });
            
            // Add new assignment
            $('#add-assignment').on('click', function() {
                const subjectId = $('#new_subject_id').val();
                const classId = $('#new_class_id').val();
                const sectionId = $('#new_section_id').val();
                
                if (!subjectId || !classId) {
                    alert('Please select both class and subject');
                    return;
                }
                
                // Find subject and class names
                const subjectName = $('#new_subject_id option:selected').text();
                const className = $('#new_class_id option:selected').text();
                const sectionName = sectionId ? $('#new_section_id option:selected').text() : 'Any Section';
                
                // Check if this assignment already exists
                const exists = assignments.some(a => 
                    a.subject_id == subjectId && 
                    a.class_id == classId
                );
                
                if (exists) {
                    alert('This teacher already has this subject assignment for the selected class');
                    return;
                }
                
                assignments.push({
                    subject_id: subjectId,
                    subject_name: subjectName,
                    class_id: classId,
                    class_name: className,
                    section_id: sectionId || null,
                    section_name: sectionName
                });
                
                renderAssignments();
                
                // Reset form
                $('#new_subject_id').val('').trigger('change');
                $('#new_class_id').val('').trigger('change');
                $('#new_section_id').val('').trigger('change');
                $('#new_subject_id').prop('disabled', true);
            });
            
            // Remove assignment
            $(document).on('click', '.remove-assignment', function() {
                const index = $(this).data('index');
                assignments.splice(index, 1);
                renderAssignments();
            });
            
            // Form submission validation
            $('#teacherForm').on('submit', function() {
                const assignmentsForSubmit = assignments.map(a => ({
                    subject_id: a.subject_id,
                    class_id: a.class_id,
                    section_id: a.section_id || null
                }));
                $('#subject_assignments').val(JSON.stringify(assignmentsForSubmit));
                return true;
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
                                <div>${assignment.section_name || 'Any Section'}</div>
                                <div class="assignment-actions">
                                    <button type="button" class="btn btn-danger btn-sm remove-assignment" data-index="${index}">Remove</button>
                                </div>
                            </div>
                        `);
                    });
                }
            }
        });
    </script>
</body>
</html>