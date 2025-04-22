<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Sections";

// Fetch all sections with class, curriculum, and class level info
$stmt = $pdo->query("
    SELECT s.id, s.section_name, s.created_at, 
           cl.class_name, cu.name AS curriculum, lv.level_name
    FROM sections s
    LEFT JOIN classes cl ON s.class_id = cl.id
    LEFT JOIN curriculums cu ON cl.curriculum_id = cu.id
    LEFT JOIN class_levels lv ON cl.class_level_id = lv.id
    ORDER BY cu.name, lv.level_order, cl.class_name, s.section_name
");
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .sections-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .sections-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .sections-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .sections-header .btn:hover {
            background: #217dbb;
        }
        .sections-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sections-table th, .sections-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .sections-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .sections-table tr:hover {
            background: #f4f8fb;
        }
        .section-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .section-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .sections-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .sections-table th, .sections-table td {
                padding: 8px 6px;
            }
        }
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
                <div class="dashboard-section">
                    <div class="sections-header">
                        <h2>Section List</h2>
                        <a href="add_section.php" class="btn"><i class="fas fa-plus"></i> Add Section</a>
                    </div>
                    <div class="table-responsive">
                        <table class="sections-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Section Name</th>
                                    <th>Class</th>
                                    <th>Class Level</th>
                                    <th>Curriculum</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sections)): ?>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($section['id']) ?></td>
                                            <td><?= htmlspecialchars($section['section_name']) ?></td>
                                            <td><?= htmlspecialchars($section['class_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($section['level_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($section['curriculum'] ?? '-') ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($section['created_at'])) ?></td>
                                            <td class="section-actions">
                                                <a href="edit_section.php?id=<?= $section['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_section.php?id=<?= $section['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this section?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                        
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">No sections found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Section Name</th>
            <th>Section</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // ...existing code to fetch $sections...
        foreach ($sections as $section) {
            echo "<tr>";
            echo "<td>{$section['id']}</td>";
            echo "<td>{$section['section_name']}</td>";
            echo "<td>{$section['section']}</td>";
            echo "<td>
                <a href='edit_section.php?id={$section['id']}'>Edit</a> |
                <a href='delete_section.php?id={$section['id']}' onclick=\"return confirm('Are you sure you want to delete this section?');\">Delete</a>
            </td>";
            echo "</tr>";
        }
        ?>
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
