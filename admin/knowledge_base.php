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

$pageTitle = "Knowledge Base";

// Handle CRUD actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$message = '';
$error = '';

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO knowledge_base (title, content, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$title, $content]);
        $message = "Article added successfully!";
    } else {
        $error = "Title and content are required.";
    }
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE knowledge_base SET title=?, content=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $content, $id]);
        $message = "Article updated successfully!";
        $id = null;
    } else {
        $error = "Title and content are required.";
    }
}

// Delete
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM knowledge_base WHERE id=?");
    $stmt->execute([$id]);
    $message = "Article deleted.";
    $id = null;
}

// Fetch articles
$articles = $pdo->query("SELECT * FROM knowledge_base ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch single article for edit
$editArticle = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM knowledge_base WHERE id=?");
    $stmt->execute([$id]);
    $editArticle = $stmt->fetch(PDO::FETCH_ASSOC);
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
            max-width: 900px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
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
        .adugna-form-group {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group textarea {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 80px;
            resize: vertical;
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
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
            border: 1px solid #e74c3c;
        }
        .adugna-btn-danger:hover { background: #c82333; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
        .adugna-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-kb-card {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(25,118,210,0.04);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.2rem;
            position: relative;
        }
        .adugna-kb-card-title {
            font-size: 1.13em;
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.5em;
        }
        .adugna-kb-card-content {
            color: #36414c;
            margin-bottom: 0.7em;
        }
        .adugna-kb-card-meta {
            font-size: 0.93em;
            color: #888;
            margin-bottom: 0.5em;
        }
        .adugna-kb-card-actions {
            display: flex;
            gap: 0.5em;
        }
        .adugna-kb-form-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 2px rgba(25,118,210,0.03);
        }
        .adugna-kb-form-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #215967;
            margin-bottom: 1em;
        }
        .adugna-kb-empty {
            color: #888;
            text-align: center;
            font-size: 1.05em;
            margin: 2em 0 1em 0;
        }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-card { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-book"></i> <?= htmlspecialchars($pageTitle) ?>
            </div>
            <?php if ($message): ?>
                <div class="adugna-alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="adugna-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Add/Edit Form -->
            <div class="adugna-kb-form-section">
                <div class="adugna-kb-form-title">
                    <?= $editArticle ? 'Edit Article' : 'Add New Article' ?>
                </div>
                <form method="post" style="margin-bottom:0;">
                    <input type="hidden" name="action" value="<?= $editArticle ? 'edit' : 'add' ?>">
                    <?php if ($editArticle): ?>
                        <input type="hidden" name="id" value="<?= $editArticle['id'] ?>">
                    <?php endif; ?>
                    <div class="adugna-form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" value="<?= htmlspecialchars($editArticle['title'] ?? '') ?>" required>
                    </div>
                    <div class="adugna-form-group">
                        <label for="content">Content</label>
                        <textarea name="content" id="content" rows="5" required><?= htmlspecialchars($editArticle['content'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="adugna-btn"><?= $editArticle ? 'Update' : 'Add' ?> Article</button>
                    <?php if ($editArticle): ?>
                        <a href="knowledge_base.php" class="adugna-btn adugna-btn-secondary" style="margin-left:0.7em;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Article List -->
            <?php if (count($articles) > 0): ?>
                <?php foreach ($articles as $article): ?>
                    <div class="adugna-kb-card">
                        <div class="adugna-kb-card-title"><?= htmlspecialchars($article['title']) ?></div>
                        <div class="adugna-kb-card-meta">
                            Last updated: <?= date('M j, Y g:i a', strtotime($article['updated_at'])) ?>
                        </div>
                        <div class="adugna-kb-card-content"><?= nl2br(htmlspecialchars(mb_strimwidth($article['content'], 0, 300, '...'))) ?></div>
                        <div class="adugna-kb-card-actions">
                            <a href="knowledge_base.php?action=edit&id=<?= $article['id'] ?>" class="adugna-btn adugna-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                            <a href="knowledge_base.php?action=delete&id=<?= $article['id'] ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm" onclick="return confirm('Delete this article?');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="adugna-kb-empty">
                    No knowledge base articles yet.<br>
                    <span style="font-size:1.2em;">Start by adding your first article!</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
