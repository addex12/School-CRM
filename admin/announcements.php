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

$pageTitle = "Announcements";

// Handle CRUD actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$message = '';
$error = '';

// Fetch all roles for assignment
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $target_roles = isset($_POST['target_roles']) && is_array($_POST['target_roles']) ? implode(',', $_POST['target_roles']) : '';
    $created_by = $_SESSION['user_id'] ?? null;
    if ($title && $content && $created_by) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by, start_date, end_date, is_public, target_roles, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $content, $created_by, $start_date, $end_date, $is_public, $target_roles]);
        $message = "Announcement added successfully!";
    } else {
        $error = "Title, content, and creator are required.";
    }
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $target_roles = isset($_POST['target_roles']) && is_array($_POST['target_roles']) ? implode(',', $_POST['target_roles']) : '';
    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, start_date=?, end_date=?, is_public=?, target_roles=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $content, $start_date, $end_date, $is_public, $target_roles, $id]);
        $message = "Announcement updated successfully!";
        $id = null;
    } else {
        $error = "Title and content are required.";
    }
}

// Delete
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->execute([$id]);
    $message = "Announcement deleted.";
    $id = null;
}

// Fetch announcements
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch single announcement for edit
$editAnnouncement = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id=?");
    $stmt->execute([$id]);
    $editAnnouncement = $stmt->fetch(PDO::FETCH_ASSOC);
    $editAnnouncement['target_roles'] = !empty($editAnnouncement['target_roles']) ? explode(',', $editAnnouncement['target_roles']) : [];
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
         * Adugna Gizaw: adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }

        .adugna-main {
            min-height: 100vh;
            background: #f7f9fb;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .adugna-content-container {
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
        .adugna-card, .adugna-ann-form-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 20px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header, .adugna-ann-form-title {
            font-size: 1.13em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
            letter-spacing: 0.01em;
        }
        .adugna-card-body {
            font-size: 0.97em;
            color: #444;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 4px 12px;
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
        .adugna-btn:hover {
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
        .adugna-btn-warning {
            background: #ffc107;
            color: #fff;
            border: 1px solid #ffc107;
        }
        .adugna-btn-warning:hover {
            background: #e0a800;
        }
        .adugna-btn-danger {
            background: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .adugna-btn-danger:hover {
            background: #c82333;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-input {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 0.97em;
            background: #f5f7fa;
            color: #36414c;
            width: 100%;
            margin-bottom: 0.7em;
        }
        .adugna-ann-card {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(44,62,80,0.04);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.2rem;
            position: relative;
        }
        .adugna-ann-card-title {
            font-size: 1.08em;
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.5em;
        }
        .adugna-ann-card-content {
            color: #36414c;
            margin-bottom: 0.7em;
        }
        .adugna-ann-card-meta {
            font-size: 0.93em;
            color: #888;
            margin-bottom: 0.5em;
        }
        .adugna-ann-card-actions {
            display: flex;
            gap: 0.5em;
        }
        .adugna-ann-empty { color: #888; font-size: 1.1rem; }
        .adugna-alert-success {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        @media (max-width: 1100px) {
            .adugna-content-container {
                max-width: 99vw;
                margin: 18px 2vw 0 2vw;
                padding: 10px 4px 18px 4px;
            }
        }
        @media (max-width: 900px) {
            .adugna-card, .adugna-ann-form-section, .adugna-content-container {
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
        }
        @media (max-width: 600px) {
            .adugna-card, .adugna-ann-form-section, .adugna-content-container {
                padding: 0.5rem 0.2rem 0.7rem 0.2rem;
            }
            .adugna-header-title {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-content-container">
                <div class="adugna-header-title"><i class="fas fa-bullhorn"></i> Announcements</div>
                <?php if ($message): ?>
                    <div class="adugna-alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="adugna-alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Add/Edit Form -->
                <div class="adugna-ann-form-section">
                    <div class="adugna-ann-form-title">
                        <?= $editAnnouncement ? 'Edit Announcement' : 'Add New Announcement' ?>
                    </div>
                    <form method="post" style="margin-bottom:0;">
                        <input type="hidden" name="action" value="<?= $editAnnouncement ? 'edit' : 'add' ?>">
                        <?php if ($editAnnouncement): ?>
                            <input type="hidden" name="id" value="<?= $editAnnouncement['id'] ?>">
                        <?php endif; ?>
                        <input type="text" name="title" class="adugna-input" placeholder="Title" value="<?= htmlspecialchars($editAnnouncement['title'] ?? '') ?>" required>
                        <textarea name="content" class="adugna-input" placeholder="Content" rows="5" required><?= htmlspecialchars($editAnnouncement['content'] ?? '') ?></textarea>
                        <div style="display:flex;gap:1em;flex-wrap:wrap;">
                            <div>
                                <label>Start Date & Time:</label>
                                <?php
                                    $now = date('Y-m-d\TH:i');
                                    $defaultStart = $editAnnouncement['start_date'] ?? $now;
                                    $defaultEnd = $editAnnouncement['end_date'] ?? date('Y-m-d\TH:i', strtotime('+5 days'));
                                ?>
                                <input type="datetime-local" name="start_date" class="adugna-input"
                                    value="<?= htmlspecialchars(str_replace(' ', 'T', $defaultStart)) ?>"
                                    min="<?= $now ?>" required>
                            </div>
                            <div>
                                <label>End Date & Time:</label>
                                <input type="datetime-local" name="end_date" class="adugna-input"
                                    value="<?= htmlspecialchars(str_replace(' ', 'T', $defaultEnd)) ?>"
                                    min="<?= date('Y-m-d\TH:i', strtotime('+5 days')) ?>" required>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5em;">
                                <input type="checkbox" name="is_public" id="is_public" value="1" <?= !empty($editAnnouncement['is_public']) ? 'checked' : '' ?>>
                                <label for="is_public" style="margin:0;">Public (Show to everyone)</label>
                            </div>
                        </div>
                        <div style="margin:1em 0;">
                            <label>Target Role (if not public):</label>
                            <select name="target_roles[]" class="adugna-input" style="min-width:180px;">
                                <option value="">-- Select Role --</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= isset($editAnnouncement['target_roles']) && in_array($role['id'], $editAnnouncement['target_roles']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:#888;">Choose a role to target (leave blank for none).</small>
                        </div>
                        <button type="submit" class="adugna-btn"><?= $editAnnouncement ? 'Update' : 'Add' ?> Announcement</button>
                        <?php if ($editAnnouncement): ?>
                            <a href="announcements.php" class="adugna-btn adugna-btn-secondary" style="margin-left:0.7em;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Announcement List -->
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div class="adugna-ann-card">
                            <div class="adugna-ann-card-title"><?= htmlspecialchars($ann['title']) ?>
                                <?php if ($ann['is_public']): ?>
                                    <span style="background:#007bfc;color:#fff;font-size:0.8em;padding:2px 8px;border-radius:4px;margin-left:8px;">Public</span>
                                <?php endif; ?>
                            </div>
                            <div class="adugna-ann-card-meta">
                                <span>From: <?= date('M j, Y', strtotime($ann['start_date'])) ?></span>
                                <span>To: <?= date('M j, Y', strtotime($ann['end_date'])) ?></span>
                                <span style="margin-left:1em;">Last updated: <?= date('M j, Y g:i a', strtotime($ann['updated_at'])) ?></span>
                            </div>
                            <div class="adugna-ann-card-content"><?= nl2br(htmlspecialchars(mb_strimwidth($ann['content'], 0, 300, '...'))) ?></div>
                            <div class="adugna-ann-card-actions">
                                <a href="announcements.php?action=edit&id=<?= $ann['id'] ?>" class="adugna-btn adugna-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="announcements.php?action=delete&id=<?= $ann['id'] ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm" onclick="return confirm('Delete this announcement?');"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                            <?php if (!$ann['is_public'] && !empty($ann['target_roles'])): ?>
                                <div style="font-size:0.93em;color:#888;margin-top:0.5em;">
                                    <i class="fas fa-users"></i> Targeted to roles: 
                                    <?php
                                    $roleIds = explode(',', $ann['target_roles']);
                                    $roleNames = array_map(function($rid) use ($roles) {
                                        foreach ($roles as $r) if ($r['id'] == $rid) return $r['role_name'];
                                        return '';
                                    }, $roleIds);
                                    echo htmlspecialchars(implode(', ', array_filter($roleNames)));
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="adugna-ann-empty">
                        No announcements yet.<br>
                        <span style="font-size:1.2em;">Start by adding your first announcement!</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
