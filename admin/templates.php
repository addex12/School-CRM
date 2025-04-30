<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Templates";

// Fetch all templates (fallback to ordering by id if created_at does not exist)
try {
    $stmt = $pdo->query("SELECT * FROM templates ORDER BY created_at DESC");
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback: order by id if created_at column does not exist
    $stmt = $pdo->query("SELECT * FROM templates ORDER BY id DESC");
    $templates = $stmt->fetchAll();
}

// Handle form submission for adding a new template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_template'])) {
    try {
        $name = trim($_POST['name']);
        $content = trim($_POST['content']);

        if (empty($name)) {
            throw new Exception("Template name is required.");
        }

        $stmt = $pdo->prepare("INSERT INTO templates (name, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $content]);

        $_SESSION['success'] = "Template added successfully!";
        header("Location: templates.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle template deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template'])) {
    try {
        $template_id = intval($_POST['template_id']);
        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
        $stmt->execute([$template_id]);

        $_SESSION['success'] = "Template deleted successfully!";
        header("Location: templates.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2.5vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 2.2rem 2vw 2vw 2vw;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }
        .adugna-form-group label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.3em;
            font-size: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group input[type="text"],
        .adugna-form-group textarea {
            width: 100%;
            border-radius: 0.5em;
            border: 1.2px solid #e5e7eb;
            background: #f3f4f6;
            padding: 0.7em 1em;
            font-size: 1em;
            transition: border 0.18s, box-shadow 0.18s;
            box-sizing: border-box;
            margin-bottom: 0.05em;
        }
        .adugna-form-group textarea {
            min-height: 80px;
            resize: vertical;
            font-family: inherit;
        }
        .adugna-form-group input:focus,
        .adugna-form-group textarea:focus {
            border: 1.2px solid #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 2px #a5b4fc33;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.18rem 1.1rem;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .adugna-btn-primary {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
        }
        .adugna-btn-primary:hover {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
        }
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 1em;
        }
        .adugna-table th, .adugna-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-table td:last-child, .adugna-table th:last-child { text-align: right; }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 8px 4px; font-size: 0.97em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="admin-header" style="width:100%;max-width:700px;margin:0 auto 1.5rem auto;">
                <h1 style="color:#215967;font-weight:700;font-size:clamp(1.3rem,2.5vw,2rem);text-align:center;">Templates</h1>
            </header>
            <div class="content" style="width:100%;max-width:700px;margin:0 auto;">
                <?php include 'includes/alerts.php'; ?>
                <!-- Adugna Gizaw: Add New Template Card -->
                <div class="adugna-card">
                    <h2>Add New Template</h2>
                    <form method="POST">
                        <div class="adugna-form-group">
                            <label for="name">Template Name</label>
                            <input type="text" name="name" id="name" required>
                        </div>
                        <div class="adugna-form-group">
                            <label for="content">Content</label>
                            <textarea name="content" id="content" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="add_template" class="adugna-btn adugna-btn-primary"><i class="fas fa-plus"></i> Add Template</button>
                    </form>
                </div>
                <!-- Adugna Gizaw: Existing Templates Card -->
                <div class="adugna-card">
                    <h2>Existing Templates</h2>
                    <?php if (count($templates) > 0): ?>
                        <div class="adugna-table-responsive">
                        <table class="adugna-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['id']) ?></td>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($template['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                                <button type="submit" name="delete_template" class="adugna-btn adugna-btn-danger" onclick="return confirm('Are you sure you want to delete this template?')"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php else: ?>
                        <p>No templates found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
