<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Classes";

// Fetch all classes
$stmt = $pdo->query("SELECT id, class_name, created_at FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .classes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .classes-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .classes-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .classes-header .btn:hover {
            background: #217dbb;
        }
        .classes-table {
            width: 100%;
            border-collapse: collapse;
        }
        .classes-table th, .classes-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .classes-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .classes-table tr:hover {
            background: #f4f8fb;
        }
        .class-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .class-actions a:last-child {
            margin-right: 0;
        }
        @media (max-width: 900px) {
            .classes-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .classes-table th, .classes-table td {
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
                    <div class="classes-header">
                        <h2>Class List</h2>
                        <a href="add_class.php" class="btn"><i class="fas fa-plus"></i> Add Class</a>
                    </div>
                    <div class="table-responsive">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($classes)): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['id']) ?></td>
                                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($class['created_at'])) ?></td>
                                            <td class="class-actions">
                                                <a href="edit_class.php?id=<?= $class['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_class.php?id=<?= $class['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this class?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No classes found.</td>
                                    </tr>
                                <?php endif; ?>
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
