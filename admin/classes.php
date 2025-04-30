<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Set timezone for classes management
date_default_timezone_set('Africa/Nairobi');

require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Classes";

// Fetch all classes with curriculum, class level, and sections
$stmt = $pdo->query("
    SELECT cl.id, cl.class_name, cu.name AS curriculum, lv.level_name, cl.created_at
    FROM classes cl
    LEFT JOIN curriculums cu ON cl.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON cl.class_level_id = lv.id
    ORDER BY cu.name, lv.level_order, cl.class_name
");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sections for all classes
$sections_stmt = $pdo->query("SELECT id, class_id, section_name FROM sections ORDER BY class_id, section_name");
$sections = [];
foreach ($sections_stmt->fetchAll(PDO::FETCH_ASSOC) as $section) {
    $sections[$section['class_id']][] = $section['section_name'];
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
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }

        .adugna-main-content {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-classes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .adugna-classes-header h2 {
            margin: 0;
            font-size: 1.15em;
            color: #1976d2;
            font-weight: 700;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-table-responsive {
            overflow-x: auto;
            margin-top: 1em;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-table-actions {
            display: flex;
            gap: 0.5em;
        }
        .adugna-table-actions a {
            color: #1976d2;
            text-decoration: none;
            font-size: 1em;
            padding: 2px 6px;
            border-radius: 4px;
            transition: background 0.15s;
        }
        .adugna-table-actions a:hover {
            background: #e3eafc;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-card { padding: 0.7rem 0.5rem 1rem 0.5rem; }
            .adugna-classes-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-card { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
            .adugna-header-title { font-size: 1.1em; }
            .adugna-table th, .adugna-table td { padding: 5px 4px; font-size: 0.95em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <header class="adugna-header-title">
                <i class="fas fa-school"></i> <?= htmlspecialchars($pageTitle) ?>
            </header>
            <div class="adugna-card">
                <div class="adugna-classes-header">
                    <h2><i class="fas fa-list"></i> Class List</h2>
                    <div style="display:flex;gap:0.7em;flex-wrap:wrap;">
                        <a href="add_class.php" class="adugna-btn adugna-btn-sm"><i class="fas fa-plus"></i> Add Class</a>
                        <a href="curriculums.php" class="adugna-btn adugna-btn-secondary adugna-btn-sm"><i class="fas fa-list"></i> Manage Curriculums</a>
                    </div>
                </div>
                <div class="adugna-table-responsive">
                    <table class="adugna-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Curriculum</th>
                                <th>Class Level</th>
                                <th>Class Name</th>
                                <th>Sections</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($classes)): ?>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($class['id']) ?></td>
                                        <td><?= htmlspecialchars($class['curriculum'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($class['level_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($class['class_name']) ?></td>
                                        <td>
                                            <?php if (!empty($sections[$class['id']])): ?>
                                                <?= htmlspecialchars(implode(', ', $sections[$class['id']])) ?>
                                            <?php else: ?>
                                                <a href="add_section.php?class_id=<?= $class['id'] ?>" class="adugna-btn adugna-btn-sm adugna-btn-secondary" style="font-size:0.93em;"><i class="fas fa-plus"></i> Add Section</a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($class['created_at'])) ?></td>
                                        <td class="adugna-table-actions">
                                            <a href="edit_class.php?id=<?= $class['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete_class.php?id=<?= $class['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this class?')"><i class="fas fa-trash-alt"></i></a>
                                            <a href="add_section.php?class_id=<?= $class['id'] ?>" title="Add Section"><i class="fas fa-plus"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="color:#888;">No classes found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
