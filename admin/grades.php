<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Grade Reports";

// Fetch students, subjects, sections for dropdowns
$students = $pdo->query("
    SELECT s.id, u.username, s.class_id, c.class_level_id
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.class_id IS NOT NULL
    ORDER BY u.username
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare a mapping: student_id => class_level_id
$studentClassLevel = [];
foreach ($students as $stu) {
    $studentClassLevel[$stu['id']] = $stu['class_level_id'];
}

// Fetch curriculums for subject and grading scale filtering
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects with curriculum and class level info
$subjects = $pdo->query("
    SELECT s.id, s.subject_name, s.class_level_id, cu.name AS curriculum, lv.level_name
    FROM subjects s
    LEFT JOIN curriculums cu ON s.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON s.class_level_id = lv.id
    WHERE s.class_level_id IS NOT NULL
    ORDER BY cu.name, lv.level_name, s.subject_name
")->fetchAll(PDO::FETCH_ASSOC);

// Group subjects by class_level_id for quick lookup
$subjectsByLevel = [];
foreach ($subjects as $subj) {
    $subjectsByLevel[$subj['class_level_id']][] = $subj;
}

// Fetch grading scales with curriculum info
$grading_scales = $pdo->query("
    SELECT gs.id, gs.scale_name, cu.name AS curriculum
    FROM grading_scales gs
    LEFT JOIN curriculums cu ON gs.curriculum_id = cu.id
    ORDER BY cu.name, gs.scale_name
")->fetchAll(PDO::FETCH_ASSOC);

// --- AUTO-INSERT COMMON CURRICULUMS, SUBJECTS, AND GRADING SCALES IF TABLES ARE EMPTY ---

// Insert common curriculums if not present
$curriculumCount = $pdo->query("SELECT COUNT(*) FROM curriculums")->fetchColumn();
if ($curriculumCount == 0) {
    $commonCurriculums = [
        'Cambridge',
        'Ethiopian',
        'American',
        'IB',
        'French',
        'Standard'
    ];
    $stmt = $pdo->prepare("INSERT INTO curriculums (name) VALUES (?)");
    foreach ($commonCurriculums as $c) {
        $stmt->execute([$c]);
    }
}

// Insert common class levels if not present
$classLevelCount = $pdo->query("SELECT COUNT(*) FROM class_levels")->fetchColumn();
if ($classLevelCount == 0) {
    // Map: curriculum => [levels]
    $levelsMap = [
        'Cambridge' => ['Foundation', 'Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5', 'Year 6', 'Year 7', 'Year 8', 'Year 9', 'Year 10', 'Year 11', 'Year 12'],
        'Ethiopian' => ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
        'American'  => ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
        'IB'        => ['PYP', 'MYP', 'DP'],
        'French'    => ['CP', 'CE1', 'CE2', 'CM1', 'CM2', '6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Terminale'],
        'Standard'  => ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5', 'Level 6']
    ];
    $curriculumIds = $pdo->query("SELECT id, name FROM curriculums")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->prepare("INSERT INTO class_levels (curriculum_id, level_name, level_order) VALUES (?, ?, ?)");
    foreach ($levelsMap as $currName => $levels) {
        if (!isset($curriculumIds[$currName])) continue;
        $currId = $curriculumIds[$currName];
        foreach ($levels as $order => $level) {
            $stmt->execute([$currId, $level, $order]);
        }
    }
}

// Insert common subjects if not present
$subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
if ($subjectCount == 0) {
    $subjectsMap = [
        'Cambridge' => [
            'English', 'Mathematics', 'Science', 'ICT', 'Geography', 'History', 'Art & Design', 'Physical Education', 'French', 'Biology', 'Chemistry', 'Physics', 'Business Studies', 'Economics'
        ],
        'Ethiopian' => [
            'English', 'Mathematics', 'Amharic', 'Science', 'Civics', 'ICT', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Economics'
        ],
        'American' => [
            'English', 'Mathematics', 'Science', 'Social Studies', 'Physical Education', 'Art', 'Music', 'Computer Science', 'Biology', 'Chemistry', 'Physics', 'Economics'
        ],
        'IB' => [
            'Language and Literature', 'Individuals and Societies', 'Sciences', 'Mathematics', 'Arts', 'Physical and Health Education', 'Design'
        ],
        'French' => [
            'Français', 'Mathématiques', 'Histoire-Géographie', 'Sciences', 'Anglais', 'Arts Plastiques', 'EPS', 'Physique-Chimie', 'SVT', 'Technologie'
        ],
        'Standard' => [
            'English', 'Mathematics', 'Science', 'Social Studies'
        ]
    ];
    $curriculumIds = $pdo->query("SELECT id, name FROM curriculums")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->prepare("INSERT INTO subjects (curriculum_id, subject_name) VALUES (?, ?)");
    foreach ($subjectsMap as $currName => $subjects) {
        if (!isset($curriculumIds[$currName])) continue;
        $currId = $curriculumIds[$currName];
        foreach ($subjects as $subject) {
            $stmt->execute([$currId, $subject]);
        }
    }
}

// Insert common grading scales if not present
$scaleCount = $pdo->query("SELECT COUNT(*) FROM grading_scales")->fetchColumn();
if ($scaleCount == 0) {
    $scales = [
        // Cambridge/IB/International
        ['A*', 90, 100, 'Excellent'],
        ['A', 80, 89.99, 'Very Good'],
        ['B', 70, 79.99, 'Good'],
        ['C', 60, 69.99, 'Satisfactory'],
        ['D', 50, 59.99, 'Pass'],
        ['E', 40, 49.99, 'Weak Pass'],
        ['F', 0, 39.99, 'Fail'],
        // American (GPA style)
        ['A', 90, 100, 'Excellent'],
        ['B', 80, 89.99, 'Good'],
        ['C', 70, 79.99, 'Average'],
        ['D', 60, 69.99, 'Below Average'],
        ['F', 0, 59.99, 'Fail'],
        // Ethiopian (can be similar to above)
    ];
    $curriculumIds = $pdo->query("SELECT id, name FROM curriculums")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->prepare("INSERT INTO grading_scales (curriculum_id, scale_name, min_score, max_score, grade_letter, remark) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($curriculumIds as $currId) {
        foreach ($scales as $scale) {
            $stmt->execute([$currId, 'Default', $scale[1], $scale[2], $scale[0], $scale[3]]);
        }
    }
}

// Handle add grade
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $section_id = $_POST['section_id'] ?? null;
    $grading_scale_id = $_POST['grading_scale_id'] ?? null;
    $score = $_POST['score'] ?? '';
    $grade_letter = trim($_POST['grade_letter'] ?? '');
    $term = trim($_POST['term'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    if ($student_id && $subject_id && $score !== '' && $grade_letter && $term && $academic_year) {
        $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, section_id, grading_scale_id, score, grade_letter, term, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $subject_id, $section_id ?: null, $grading_scale_id ?: null, $score, $grade_letter, $term, $academic_year]);
        header("Location: grades.php?msg=Grade+added");
        exit;
    } else {
        $error = "All fields except section and grading scale are required.";
    }
}

// Fetch all grades with student, subject, section, and grading scale info
$stmt = $pdo->query("
    SELECT g.id, u.username AS student, s.subject_name, sec.section_name, gs.scale_name, g.score, g.grade_letter, g.term, g.academic_year, g.created_at
    FROM grades g
    LEFT JOIN students st ON g.student_id = st.id
    LEFT JOIN users u ON st.user_id = u.id
    LEFT JOIN subjects s ON g.subject_id = s.id
    LEFT JOIN sections sec ON g.section_id = sec.id
    LEFT JOIN grading_scales gs ON g.grading_scale_id = gs.id
    ORDER BY g.created_at DESC
");
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>
    // Dynamically update subject dropdown based on selected student
    var subjectsByLevel = <?= json_encode($subjectsByLevel) ?>;
    var studentClassLevel = <?= json_encode($studentClassLevel) ?>;
    function updateSubjects() {
        var studentId = document.getElementById('student_id').value;
        var subjectSelect = document.getElementById('subject_id');
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        if (studentId && studentClassLevel[studentId] && subjectsByLevel[studentClassLevel[studentId]]) {
            subjectsByLevel[studentClassLevel[studentId]].forEach(function(subj) {
                var opt = document.createElement('option');
                opt.value = subj.id;
                opt.text = subj.subject_name;
                subjectSelect.appendChild(opt);
            });
        }
    }
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section" style="max-width:900px;">
                    <h2>Add Grade</h2>
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" style="margin-bottom:2rem;">
                        <div style="margin-bottom:1rem;">
                            <label for="student_id">Student</label>
                            <select name="student_id" id="student_id" required onchange="updateSubjects()">
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="subject_id">Subject</label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">-- Select Student First --</option>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="section_id">Section (optional)</label>
                            <select name="section_id" id="section_id">
                                <option value="">-- Any Section --</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="grading_scale_id">Grading Scale (optional)</label>
                            <select name="grading_scale_id" id="grading_scale_id">
                                <option value="">-- Any Scale --</option>
                                <?php foreach ($grading_scales as $gs): ?>
                                    <option value="<?= $gs['id'] ?>">
                                        <?= htmlspecialchars($gs['scale_name']) ?>
                                        (<?= htmlspecialchars($gs['curriculum'] ?? '-') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="score">Score</label>
                            <input type="number" step="0.01" name="score" id="score" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="grade_letter">Grade Letter</label>
                            <input type="text" name="grade_letter" id="grade_letter" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="term">Term</label>
                            <input type="text" name="term" id="term" required>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="academic_year">Academic Year</label>
                            <input type="text" name="academic_year" id="academic_year" required>
                        </div>
                        <button type="submit" name="add_grade" class="btn" style="background:#3498db;color:#fff;">Add Grade</button>
                    </form>
                    <h2>Grade Reports</h2>
                    <div class="table-responsive">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Grading Scale</th>
                                    <th>Score</th>
                                    <th>Grade Letter</th>
                                    <th>Term</th>
                                    <th>Academic Year</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($grades)): ?>
                                    <?php foreach ($grades as $grade): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($grade['id']) ?></td>
                                            <td><?= htmlspecialchars($grade['student'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($grade['subject_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($grade['section_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($grade['scale_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($grade['score']) ?></td>
                                            <td><?= htmlspecialchars($grade['grade_letter']) ?></td>
                                            <td><?= htmlspecialchars($grade['term']) ?></td>
                                            <td><?= htmlspecialchars($grade['academic_year']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($grade['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10">No grades found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <h2>Curriculum Subjects & Grading Scales Reference</h2>
                    <div class="table-responsive">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th>Curriculum</th>
                                    <th>Subjects</th>
                                    <th>Grading Scales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($curriculums as $curriculum): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curriculum['name']) ?></td>
                                        <td>
                                            <?php
                                            $currSubjects = array_filter($subjects, function($s) use ($curriculum) {
                                                return $s['curriculum'] === $curriculum['name'];
                                            });
                                            if ($currSubjects) {
                                                echo '<ul style="margin:0;padding-left:1.2em;">';
                                                foreach ($currSubjects as $s) {
                                                    echo '<li>' . htmlspecialchars($s['subject_name']);
                                                    if ($s['level_name']) echo ' <small>(' . htmlspecialchars($s['level_name']) . ')</small>';
                                                    echo '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<em>No subjects</em>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $currScales = array_filter($grading_scales, function($gs) use ($curriculum) {
                                                return $gs['curriculum'] === $curriculum['name'];
                                            });
                                            if ($currScales) {
                                                echo '<ul style="margin:0;padding-left:1.2em;">';
                                                foreach ($currScales as $gs) {
                                                    echo '<li>' . htmlspecialchars($gs['scale_name']) . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<em>No grading scales</em>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <?php include 'includes/footer.php'; ?>

    <script>
    // Initialize subject dropdown on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        updateSubjects();
    });
    </script>
</body>
</html>
