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
$classes = $pdo->query("
    SELECT c.id, c.class_name, cu.name AS curriculum_name 
    FROM classes c
    JOIN curriculums cu ON c.curriculum_id = cu.id
    ORDER BY c.class_name
")->fetchAll(PDO::FETCH_ASSOC);

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

// Fetch all sections grouped by class
$sections_by_class = [];
$sections = $pdo->query("
    SELECT s.id, s.section_name, s.class_id, c.class_name
    FROM sections s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.section_name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($sections as $section) {
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
            // Get all class-subject combinations
            $class_subjects = [];
            $cs_stmt = $pdo->query("SELECT id, class_id, subject_id FROM class_subjects");
            while ($row = $cs_stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = ($row['class_id'] ?: '0') . '_' . $row['subject_id'];
                $class_subjects[$key] = $row['id'];
            }
            
            $insert_stmt = $pdo->prepare("
                INSERT INTO teacher_subjects (teacher_id, class_subject_id, section_id)
                VALUES (?, ?, ?)
            ");
            
            foreach ($new_assignments as $assignment) {
                $subject_id = intval($assignment['subject_id']);
                $class_id = !empty($assignment['class_id']) ? intval($assignment['class_id']) : null;
                $section_id = !empty($assignment['section_id']) ? intval($assignment['section_id']) : null;
                
                // Find the class_subject_id
                $key = ($class_id ?: '0') . '_' . $subject_id;
                $class_subject_id = $class_subjects[$key] ?? null;
                
                if ($class_subject_id) {
                    $insert_stmt->execute([$teacher_id, $class_subject_id, $section_id]);
                }
            }
        }
    }
}

// Prepare data for JS
$js_data = [
    'teacherSubjects' => $teacher_subjects,
    'classes' => $classes,
    'sectionsByClass' => $sections_by_class
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
    <style>
        /* Your existing CSS styles */
    </style>
</head>
<body>
    <!-- Your existing HTML structure -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const data = <?= json_encode($js_data) ?>;
            let assignments = [...data.teacherSubjects];
            
            // Initialize Select2
            $('.select2-subject').select2();
            $('.select2-class').select2();
            $('.select2-section').select2();
            
            // Render initial assignments
            renderAssignments();
            
            // Update sections dropdown when class changes
            $('#new_class_id').on('change', function() {
                const classId = $(this).val();
                const $sectionSelect = $('#new_section_id');
                
                $sectionSelect.empty().append('<option value="">-- Select Section --</option>');
                
                if (classId && data.sectionsByClass[classId]) {
                    data.sectionsByClass[classId].forEach(section => {
                        $sectionSelect.append(`<option value="${section.id}">${section.section_name}</option>`);
                    });
                }
                
                $sectionSelect.trigger('change');
            });
            
            // AJAX call to get subjects for selected class
            $('#new_class_id').on('change', function() {
                const classId = $(this).val();
                const $subjectSelect = $('#new_subject_id');
                
                $subjectSelect.empty().append('<option value="">-- Select Subject --</option>');
                
                if (classId) {
                    $.ajax({
                        url: 'ajax/get_subjects.php',
                        data: { class_id: classId },
                        success: function(subjects) {
                            subjects.forEach(subject => {
                                $subjectSelect.append(`<option value="${subject.id}">${subject.subject_name}</option>`);
                            });
                            $subjectSelect.trigger('change');
                        }
                    });
                }
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
                const subjectName = $('#new_subject_id option:selected').text();
                const className = classId ? $('#new_class_id option:selected').text() : 'Any Class';
                const sectionName = sectionId ? $('#new_section_id option:selected').text() : 'Any Section';
                
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