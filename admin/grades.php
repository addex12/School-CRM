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

// Fetch grading scales by class_level_id for auto grade letter
$gradingScalesByLevel = [];
$grading_scale_rows = $pdo->query("
    SELECT gs.*, lv.id AS class_level_id
    FROM grading_scales gs
    LEFT JOIN curriculums cu ON gs.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON cu.id = lv.curriculum_id
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($grading_scale_rows as $row) {
    if ($row['class_level_id']) {
        $gradingScalesByLevel[$row['class_level_id']][] = $row;
    }
}

// Fetch sections for all classes (for AJAX)
$sectionsByClass = [];
$sections = $pdo->query("SELECT id, class_id, section_name FROM sections")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sections as $sec) {
    $sectionsByClass[$sec['class_id']][] = $sec;
}

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

// Fetch all academic terms and years for dropdowns
$terms = $pdo->query("SELECT term_name FROM academic_terms ORDER BY start_date DESC")->fetchAll(PDO::FETCH_COLUMN);
$currentTerm = $pdo->query("SELECT term_name FROM academic_terms WHERE is_current=1 ORDER BY start_date DESC LIMIT 1")->fetchColumn();
$years = $pdo->query("SELECT year_name FROM academic_years ORDER BY start_date DESC")->fetchAll(PDO::FETCH_COLUMN);
$currentAcademicYear = $pdo->query("SELECT year_name FROM academic_years WHERE end_date >= CURDATE() ORDER BY start_date DESC LIMIT 1")->fetchColumn();

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

// Handle delete grade
if (isset($_GET['delete_grade']) && is_numeric($_GET['delete_grade'])) {
    $grade_id = intval($_GET['delete_grade']);
    $pdo->prepare("DELETE FROM grades WHERE id=?")->execute([$grade_id]);
    header("Location: grades.php?msg=Grade+deleted");
    exit;
}

// Handle edit grade (fetch for form)
$editGrade = null;
if (isset($_GET['edit_grade']) && is_numeric($_GET['edit_grade'])) {
    $edit_id = intval($_GET['edit_grade']);
    $editGrade = $pdo->prepare("SELECT * FROM grades WHERE id=?");
    $editGrade->execute([$edit_id]);
    $editGrade = $editGrade->fetch(PDO::FETCH_ASSOC);
}

// Handle update grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    $grade_id = intval($_POST['grade_id']);
    $student_id = $_POST['student_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $section_id = $_POST['section_id'] ?? null;
    $grading_scale_id = $_POST['grading_scale_id'] ?? null;
    $score = $_POST['score'] ?? '';
    $grade_letter = trim($_POST['grade_letter'] ?? '');
    $term = trim($_POST['term'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    if ($grade_id && $student_id && $subject_id && $score !== '' && $grade_letter && $term && $academic_year) {
        $stmt = $pdo->prepare("UPDATE grades SET student_id=?, subject_id=?, section_id=?, grading_scale_id=?, score=?, grade_letter=?, term=?, academic_year=? WHERE id=?");
        $stmt->execute([$student_id, $subject_id, $section_id ?: null, $grading_scale_id ?: null, $score, $grade_letter, $term, $academic_year, $grade_id]);
        header("Location: grades.php?msg=Grade+updated");
        exit;
    } else {
        $error = "All fields except section and grading scale are required.";
    }
}

// Export report card as Word (docx) using placeholders
if (isset($_GET['export_word']) && is_numeric($_GET['export_word'])) {
    $grade_id = intval($_GET['export_word']);
    $grade = $pdo->prepare("
        SELECT g.*, u.username AS student, s.subject_name, sec.section_name, gs.scale_name, g.term, g.academic_year
        FROM grades g
        LEFT JOIN students st ON g.student_id = st.id
        LEFT JOIN users u ON st.user_id = u.id
        LEFT JOIN subjects s ON g.subject_id = s.id
        LEFT JOIN sections sec ON g.section_id = sec.id
        LEFT JOIN grading_scales gs ON g.grading_scale_id = gs.id
        WHERE g.id=?
    ");
    $grade->execute([$grade_id]);
    $grade = $grade->fetch(PDO::FETCH_ASSOC);

    // Use an absolute path for the template
    require_once '../vendor/autoload.php';
    $templatePath = realpath(__DIR__ . '/../templates/report_card_template.docx');
    if (!$templatePath || !file_exists($templatePath)) {
        // Show a user-friendly error and stop further execution
        echo '<div style="color:red;font-weight:bold;padding:2em;text-align:center;">';
        echo 'Report card template not found at: <code>' . htmlspecialchars(__DIR__ . '/../templates/report_card_template.docx') . '</code><br>';
        echo 'Please upload <b>report_card_template.docx</b> to the <b>templates</b> folder.';
        echo '</div>';
        exit;
    }
    $phpWord = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

    // Set placeholders (example: {{student}}, {{subject}}, etc.)
    $phpWord->setValue('student', htmlspecialchars($grade['student'] ?? ''));
    $phpWord->setValue('subject', htmlspecialchars($grade['subject_name'] ?? ''));
    $phpWord->setValue('section', htmlspecialchars($grade['section_name'] ?? ''));
    $phpWord->setValue('score', htmlspecialchars($grade['score'] ?? ''));
    $phpWord->setValue('grade_letter', htmlspecialchars($grade['grade_letter'] ?? ''));
    $phpWord->setValue('term', htmlspecialchars($grade['term'] ?? ''));
    $phpWord->setValue('academic_year', htmlspecialchars($grade['academic_year'] ?? ''));
    $phpWord->setValue('scale_name', htmlspecialchars($grade['scale_name'] ?? ''));

    // Download the file
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="report_card_' . $grade_id . '.docx"');
    $phpWord->saveAs('php://output');
    exit;
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
    <style>
        /* Excel-like table style */
        .excel-table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #bdbdbd;
            padding: 8px 10px;
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
        .excel-form-row {
            display: flex;
            gap: 0;
        }
        .excel-form-row > div {
            flex: 1 1 0;
            margin: 0;
            padding: 0 2px;
        }
        .excel-form-row label {
            display: block;
            font-size: 0.95em;
            color: #215967;
            margin-bottom: 2px;
        }
        .excel-form-row input, .excel-form-row select {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #bdbdbd;
            border-radius: 2px;
            font-size: 1em;
        }
        @media (max-width: 900px) {
            .excel-form-row { flex-direction: column; }
        }
    </style>
    <script>
    // Dynamically update subject dropdown based on selected student
    var subjectsByLevel = <?= json_encode($subjectsByLevel) ?>;
    var studentClassLevel = <?= json_encode($studentClassLevel) ?>;
    var gradingScalesByLevel = <?= json_encode($gradingScalesByLevel) ?>;
    var sectionsByClass = <?= json_encode($sectionsByClass) ?>;
    var students = <?= json_encode($students) ?>;
    var currentTerm = <?= json_encode($currentTerm ?: '') ?>;
    var currentAcademicYear = <?= json_encode($currentAcademicYear ?: '') ?>;

    function updateSubjects() {
        var studentId = document.getElementById('student_id').value;
        var subjectSelect = document.getElementById('subject_id');
        var sectionSelect = document.getElementById('section_id');
        var infoDiv = document.getElementById('student_info');
        var termInput = document.getElementById('term');
        var yearInput = document.getElementById('academic_year');
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        sectionSelect.innerHTML = '<option value="">-- Any Section --</option>';

        // Detect student's class and section, and show as info
        var student = students.find(function(s) { return String(s.id) === String(studentId); });
        if (student) {
            var classText = student.class_id ? 'Class ID: ' + student.class_id : 'Class: -';
            // Find section name if available
            var sectionName = '-';
            if (student.class_id && sectionsByClass[student.class_id]) {
                var secList = sectionsByClass[student.class_id];
                if (secList.length === 1) {
                    sectionName = secList[0].section_name;
                }
            }
            infoDiv.textContent = classText + (sectionName !== '-' ? ', Section: ' + sectionName : '');
        } else {
            infoDiv.textContent = '';
        }

        // Populate subjects based on student's class_level_id
        if (student && student.class_level_id && subjectsByLevel[student.class_level_id]) {
            subjectsByLevel[student.class_level_id].forEach(function(subj) {
                var opt = document.createElement('option');
                opt.value = subj.id;
                opt.text = subj.subject_name;
                subjectSelect.appendChild(opt);
            });
        }

        // Populate sections based on student's class_id
        if (student && student.class_id && sectionsByClass[student.class_id]) {
            sectionsByClass[student.class_id].forEach(function(sec) {
                var opt = document.createElement('option');
                opt.value = sec.id;
                opt.text = sec.section_name;
                sectionSelect.appendChild(opt);
            });
        }

        // Autofill current term and academic year
        if (termInput) termInput.value = currentTerm;
        if (yearInput) yearInput.value = currentAcademicYear;
    }

    function autoFillGradeLetter() {
        var studentId = document.getElementById('student_id').value;
        var score = parseFloat(document.getElementById('score').value);
        var gradeLetterInput = document.getElementById('grade_letter');
        if (!studentId || isNaN(score)) {
            gradeLetterInput.value = '';
            return;
        }
        var classLevelId = studentClassLevel[studentId];
        var scales = gradingScalesByLevel[classLevelId] || [];
        var found = false;
        for (var i = 0; i < scales.length; i++) {
            var min = parseFloat(scales[i].min_score);
            var max = parseFloat(scales[i].max_score);
            if (score >= min && score <= max) {
                gradeLetterInput.value = scales[i].grade_letter;
                found = true;
                break;
            }
        }
        if (!found) gradeLetterInput.value = '';
    }

    // Initialize subject and section dropdowns on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('student_id').addEventListener('change', function() {
            updateSubjects();
            autoFillGradeLetter();
        });
        document.getElementById('score').addEventListener('input', autoFillGradeLetter);
        updateSubjects();
    });
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
                <div class="dashboard-section" >
                    <h2><?= $editGrade ? 'Edit Grade' : 'Add Grade' ?></h2>
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" style="margin-bottom:2rem;">
                        <?php if ($editGrade): ?>
                            <input type="hidden" name="grade_id" value="<?= $editGrade['id'] ?>">
                        <?php endif; ?>
                        <div class="excel-form-row">
                            <div>
                                <label for="student_id">Student</label>
                                <select name="student_id" id="student_id" required>
                                    <option value="">-- Select Student --</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($editGrade && $editGrade['student_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="subject_id">Subject</label>
                                <select name="subject_id" id="subject_id" required>
                                    <option value="">-- Select Student First --</option>
                                </select>
                            </div>
                            <div>
                                <label for="section_id">Section (optional)</label>
                                <select name="section_id" id="section_id">
                                    <option value="">-- Any Section --</option>
                                </select>
                            </div>
                            <div>
                                <label for="grading_scale_id">Grading Scale (optional)</label>
                                <select name="grading_scale_id" id="grading_scale_id">
                                    <option value="">-- Any Scale --</option>
                                    <?php foreach ($grading_scales as $gs): ?>
                                        <option value="<?= $gs['id'] ?>" <?= ($editGrade && $editGrade['grading_scale_id'] == $gs['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($gs['scale_name']) ?>
                                            (<?= htmlspecialchars($gs['curriculum'] ?? '-') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="excel-form-row" style="margin-top:8px;">
                            <div>
                                <label for="score">Score</label>
                                <input type="number" step="0.01" name="score" id="score" required oninput="autoFillGradeLetter()" value="<?= $editGrade ? htmlspecialchars($editGrade['score']) : '' ?>">
                            </div>
                            <div>
                                <label for="grade_letter">Grade Letter</label>
                                <input type="text" name="grade_letter" id="grade_letter" required readonly value="<?= $editGrade ? htmlspecialchars($editGrade['grade_letter']) : '' ?>">
                            </div>
                            <div>
                                <label for="term">Term</label>
                                <select name="term" id="term" required>
                                    <?php foreach ($terms as $t): ?>
                                        <option value="<?= htmlspecialchars($t) ?>" <?= (($editGrade && $editGrade['term'] == $t) || (!$editGrade && $t == $currentTerm)) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="academic_year">Academic Year</label>
                                <select name="academic_year" id="academic_year" required>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?= htmlspecialchars($y) ?>" <?= (($editGrade && $editGrade['academic_year'] == $y) || (!$editGrade && $y == $currentAcademicYear)) ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="<?= $editGrade ? 'update_grade' : 'add_grade' ?>" class="btn" style="background:#3498db;color:#fff;margin-top:10px;">
                            <?= $editGrade ? 'Update Grade' : 'Add Grade' ?>
                        </button>
                        <?php if ($editGrade): ?>
                            <a href="grades.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
                        <?php endif; ?>
                    </form>
                    <h2>Grade Reports</h2>
                    <div class="table-responsive">
                        <table class="excel-table">
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
                                    <th>Actions</th>
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
                                            <td>
                                                <a href="grades.php?edit_grade=<?= $grade['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                <a href="grades.php?delete_grade=<?= $grade['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this grade?')">Delete</a>
                                                <a href="grades.php?export_word=<?= $grade['id'] ?>" class="btn btn-success btn-sm">Export Word</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11">No grades found.</td>
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
    // Dynamically update subject dropdown based on selected student
    var subjectsByLevel = <?= json_encode($subjectsByLevel) ?>;
    var studentClassLevel = <?= json_encode($studentClassLevel) ?>;
    var gradingScalesByLevel = <?= json_encode($gradingScalesByLevel) ?>;
    var sectionsByClass = <?= json_encode($sectionsByClass) ?>;
    var students = <?= json_encode($students) ?>;
    // Fetch current term and academic year from the server (PHP)
    var currentTerm = <?= json_encode($pdo->query("SELECT term_name FROM academic_terms WHERE is_current=1 ORDER BY start_date DESC LIMIT 1")->fetchColumn() ?: '') ?>;
    var currentAcademicYear = <?= json_encode($pdo->query("SELECT year_name FROM academic_years WHERE end_date >= CURDATE() ORDER BY start_date DESC LIMIT 1")->fetchColumn() ?: '') ?>;

    function updateSubjects() {
        var studentId = document.getElementById('student_id').value;
        var subjectSelect = document.getElementById('subject_id');
        var sectionSelect = document.getElementById('section_id');
        var infoDiv = document.getElementById('student_info');
        var termInput = document.getElementById('term');
        var yearInput = document.getElementById('academic_year');
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        sectionSelect.innerHTML = '<option value="">-- Any Section --</option>';

        // Detect student's class and section, and show as info
        var student = students.find(function(s) { return s.id == studentId; });
        if (student) {
            var classText = student.class_id ? 'Class ID: ' + student.class_id : 'Class: -';
            // Find section name if available
            var sectionName = '-';
            if (student.class_id && sectionsByClass[student.class_id]) {
                var secList = sectionsByClass[student.class_id];
                if (secList.length === 1) {
                    sectionName = secList[0].section_name;
                }
            }
            infoDiv.textContent = classText + (sectionName !== '-' ? ', Section: ' + sectionName : '');
        } else {
            infoDiv.textContent = '';
        }

        // Populate subjects based on student's class_level_id
        if (studentId && studentClassLevel[studentId] && subjectsByLevel[studentClassLevel[studentId]]) {
            subjectsByLevel[studentClassLevel[studentId]].forEach(function(subj) {
                var opt = document.createElement('option');
                opt.value = subj.id;
                opt.text = subj.subject_name;
                subjectSelect.appendChild(opt);
            });
        }

        // Populate sections based on student's class_id
        if (student && student.class_id && sectionsByClass[student.class_id]) {
            sectionsByClass[student.class_id].forEach(function(sec) {
                var opt = document.createElement('option');
                opt.value = sec.id;
                opt.text = sec.section_name;
                sectionSelect.appendChild(opt);
            });
        }

        // Set dropdown to current term/year if student changes (if not already selected)
        if (termInput && currentTerm) termInput.value = currentTerm;
        if (yearInput && currentAcademicYear) yearInput.value = currentAcademicYear;
    }

    function autoFillGradeLetter() {
        var studentId = document.getElementById('student_id').value;
        var score = parseFloat(document.getElementById('score').value);
        var gradeLetterInput = document.getElementById('grade_letter');
        if (!studentId || isNaN(score)) {
            gradeLetterInput.value = '';
            return;
        }
        var classLevelId = studentClassLevel[studentId];
        var scales = gradingScalesByLevel[classLevelId] || [];
        var found = false;
        for (var i = 0; i < scales.length; i++) {
            var min = parseFloat(scales[i].min_score);
            var max = parseFloat(scales[i].max_score);
            if (score >= min && score <= max) {
                gradeLetterInput.value = scales[i].grade_letter;
                found = true;
                break;
            }
        }
        if (!found) gradeLetterInput.value = '';
    }

    // Initialize subject and section dropdowns on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('student_id').addEventListener('change', function() {
            updateSubjects();
            autoFillGradeLetter();
        });
        document.getElementById('score').addEventListener('input', autoFillGradeLetter);
        updateSubjects();
        <?php if ($editGrade): ?>
        setTimeout(function() {
            document.getElementById('student_id').value = "<?= $editGrade['student_id'] ?>";
            updateSubjects();
            document.getElementById('subject_id').value = "<?= $editGrade['subject_id'] ?>";
            document.getElementById('section_id').value = "<?= $editGrade['section_id'] ?>";
        }, 100);
        <?php endif; ?>
    });
    </script>
</body>
</html>
