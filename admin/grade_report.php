<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Grade Reports & Analytics";

// Fetch filters
$students = $pdo->query("SELECT s.id, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);
$sections = $pdo->query("SELECT id, section_name FROM sections ORDER BY section_name")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);
$terms = $pdo->query("SELECT term_name FROM academic_terms ORDER BY start_date DESC")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT year_name FROM academic_years ORDER BY start_date DESC")->fetchAll(PDO::FETCH_COLUMN);

// Handle filters
$where = [];
$params = [];
if (!empty($_GET['student_id'])) {
    $where[] = "g.student_id = ?";
    $params[] = $_GET['student_id'];
}
if (!empty($_GET['class_id'])) {
    $where[] = "st.class_id = ?";
    $params[] = $_GET['class_id'];
}
if (!empty($_GET['section_id'])) {
    $where[] = "g.section_id = ?";
    $params[] = $_GET['section_id'];
}
if (!empty($_GET['subject_id'])) {
    $where[] = "g.subject_id = ?";
    $params[] = $_GET['subject_id'];
}
if (!empty($_GET['term'])) {
    $where[] = "g.term = ?";
    $params[] = $_GET['term'];
}
if (!empty($_GET['academic_year'])) {
    $where[] = "g.academic_year = ?";
    $params[] = $_GET['academic_year'];
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch grades for report
$stmt = $pdo->prepare("
    SELECT g.*, u.username AS student, s.subject_name, sec.section_name, gs.scale_name, c.class_name
    FROM grades g
    LEFT JOIN students st ON g.student_id = st.id
    LEFT JOIN users u ON st.user_id = u.id
    LEFT JOIN classes c ON st.class_id = c.id
    LEFT JOIN subjects s ON g.subject_id = s.id
    LEFT JOIN sections sec ON g.section_id = sec.id
    LEFT JOIN grading_scales gs ON g.grading_scale_id = gs.id
    $whereSql
    ORDER BY g.academic_year DESC, g.term DESC, u.username, s.subject_name
");
$stmt->execute($params);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export as CSV
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grade_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student', 'Class', 'Section', 'Subject', 'Score', 'Grade Letter', 'Term', 'Academic Year', 'Created At']);
    foreach ($grades as $g) {
        fputcsv($out, [
            $g['student'], $g['class_name'], $g['section_name'], $g['subject_name'],
            $g['score'], $g['grade_letter'], $g['term'], $g['academic_year'], $g['created_at']
        ]);
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
    <style>
        .report-filters { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .report-filters label { font-weight: 500; color: #215967; }
        .report-filters select { padding: 5px 8px; border-radius: 3px; border: 1px solid #bdbdbd; }
        .excel-table { border-collapse: collapse; width: 100%; background: #fff; }
        .excel-table th, .excel-table td { border: 1px solid #bdbdbd; padding: 8px 10px; text-align: left; font-size: 1em; }
        .excel-table th { background: #e2efda; color: #215967; font-weight: bold; }
        .excel-table tr:nth-child(even) { background: #f9f9f9; }
        .excel-table tr:hover { background: #f4f8fb; }
        .btn { background: #3498db; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: 500; text-decoration: none; }
        .btn-secondary { background: #888; color: #fff; }
        .btn-success { background: #27ae60; color: #fff; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-sm { font-size: 0.95em; padding: 4px 10px; }
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
                <div class="dashboard-section" style="max-width:1200px;">
                    <h2>Grade Report & Analytics</h2>
                    <form method="get" class="report-filters">
                        <label>Student:
                            <select name="student_id">
                                <option value="">All</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($_GET['student_id']) && $_GET['student_id'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Class:
                            <select name="class_id">
                                <option value="">All</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($_GET['class_id']) && $_GET['class_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Section:
                            <select name="section_id">
                                <option value="">All</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>" <?= (isset($_GET['section_id']) && $_GET['section_id'] == $sec['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sec['section_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Subject:
                            <select name="subject_id">
                                <option value="">All</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" <?= (isset($_GET['subject_id']) && $_GET['subject_id'] == $sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Term:
                            <select name="term">
                                <option value="">All</option>
                                <?php foreach ($terms as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>" <?= (isset($_GET['term']) && $_GET['term'] == $t) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Academic Year:
                            <select name="academic_year">
                                <option value="">All</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= htmlspecialchars($y) ?>" <?= (isset($_GET['academic_year']) && $_GET['academic_year'] == $y) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($y) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="btn">Filter</button>
                        <a href="grade_report.php?<?= http_build_query(array_merge($_GET, ['export_csv' => 1])) ?>" class="btn btn-success" style="margin-left:10px;">Export CSV</a>
                    </form>
                    <div class="table-responsive">
                        <table class="excel-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Grade Letter</th>
                                    <th>Term</th>
                                    <th>Academic Year</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($grades)): ?>
                                    <?php foreach ($grades as $g): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($g['student']) ?></td>
                                            <td><?= htmlspecialchars($g['class_name']) ?></td>
                                            <td><?= htmlspecialchars($g['section_name']) ?></td>
                                            <td><?= htmlspecialchars($g['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($g['score']) ?></td>
                                            <td><?= htmlspecialchars($g['grade_letter']) ?></td>
                                            <td><?= htmlspecialchars($g['term']) ?></td>
                                            <td><?= htmlspecialchars($g['academic_year']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($g['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9">No grades found for the selected filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:2rem;">
                        <h3>Bulk Report Card Export</h3>
                        <form method="get" action="grade_report.php">
                            <input type="hidden" name="bulk_export_word" value="1">
                            <label>Academic Year:
                                <select name="academic_year" required>
                                    <option value="">Select Year</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Term:
                                <select name="term" required>
                                    <option value="">Select Term</option>
                                    <?php foreach ($terms as $t): ?>
                                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit" class="btn btn-success">Export All Report Cards (Word)</button>
                        </form>
                        <?php
                        // Bulk export logic (Word) - generates a zip of report cards
                        if (isset($_GET['bulk_export_word']) && $_GET['academic_year'] && $_GET['term']) {
                            require_once '../vendor/autoload.php';
                            $templatePath = '../templates/report_card_template.docx';
                            $bulkGrades = $pdo->prepare("
                                SELECT g.*, u.username AS student, s.subject_name, sec.section_name, gs.scale_name
                                FROM grades g
                                LEFT JOIN students st ON g.student_id = st.id
                                LEFT JOIN users u ON st.user_id = u.id
                                LEFT JOIN subjects s ON g.subject_id = s.id
                                LEFT JOIN sections sec ON g.section_id = sec.id
                                LEFT JOIN grading_scales gs ON g.grading_scale_id = gs.id
                                WHERE g.academic_year=? AND g.term=?
                                ORDER BY u.username, s.subject_name
                            ");
                            $bulkGrades->execute([$_GET['academic_year'], $_GET['term']]);
                            $allGrades = $bulkGrades->fetchAll(PDO::FETCH_ASSOC);

                            // Group by student
                            $studentReports = [];
                            foreach ($allGrades as $g) {
                                $studentReports[$g['student']][] = $g;
                            }

                            $zip = new ZipArchive();
                            $zipFile = tempnam(sys_get_temp_dir(), 'report_cards_') . '.zip';
                            $zip->open($zipFile, ZipArchive::CREATE);

                            foreach ($studentReports as $student => $gradesArr) {
                                $phpWord = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
                                // Example: set placeholders for first subject, or loop for all
                                $phpWord->setValue('student', htmlspecialchars($student));
                                $phpWord->setValue('academic_year', htmlspecialchars($_GET['academic_year']));
                                $phpWord->setValue('term', htmlspecialchars($_GET['term']));
                                // You can add more placeholders or a table for all subjects/grades
                                // For demo, just set first subject/grade
                                if (!empty($gradesArr)) {
                                    $phpWord->setValue('subject', htmlspecialchars($gradesArr[0]['subject_name']));
                                    $phpWord->setValue('score', htmlspecialchars($gradesArr[0]['score']));
                                    $phpWord->setValue('grade_letter', htmlspecialchars($gradesArr[0]['grade_letter']));
                                }
                                $docFile = tempnam(sys_get_temp_dir(), 'rc_') . '.docx';
                                $phpWord->saveAs($docFile);
                                $zip->addFile($docFile, $student . '_report_card.docx');
                            }
                            $zip->close();

                            header('Content-Type: application/zip');
                            header('Content-Disposition: attachment; filename="report_cards_' . $_GET['academic_year'] . '_' . $_GET['term'] . '.zip"');
                            readfile($zipFile);
                            unlink($zipFile);
                            exit;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
