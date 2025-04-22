<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Grade Reports";

// Fetch students, subjects, sections for dropdowns
$students = $pdo->query("SELECT s.id, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$sections = $pdo->query("SELECT id, section_name FROM sections ORDER BY section_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch curriculums for subject and grading scale filtering
$curriculums = $pdo->query("SELECT id, name FROM curriculums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects with curriculum and class level info
$subjects = $pdo->query("
    SELECT s.id, s.subject_name, cu.name AS curriculum, lv.level_name
    FROM subjects s
    LEFT JOIN curriculums cu ON s.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON s.class_level_id = lv.id
    ORDER BY cu.name, lv.level_name, s.subject_name
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch grading scales with curriculum info
$grading_scales = $pdo->query("
    SELECT gs.id, gs.scale_name, cu.name AS curriculum
    FROM grading_scales gs
    LEFT JOIN curriculums cu ON gs.curriculum_id = cu.id
    ORDER BY cu.name, gs.scale_name
")->fetchAll(PDO::FETCH_ASSOC);

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
                            <select name="student_id" id="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="subject_id">Subject</label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['subject_name']) ?>
                                        (<?= htmlspecialchars($s['curriculum'] ?? '-') ?>
                                        <?= $s['level_name'] ? ' - ' . htmlspecialchars($s['level_name']) : '' ?>)
                                    </option>
                                <?php endforeach; ?>
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
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
