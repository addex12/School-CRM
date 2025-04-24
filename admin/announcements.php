<?php
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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .dashboard-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            margin-bottom: 2rem;
            padding: 2rem 2.5rem;
        }
        .ann-header { font-size: 1.5rem; color: #215967; font-weight: 700; margin-bottom: 1.5rem; }
        .erpnext-btn {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .erpnext-btn.btn-primary {
            background: #007bfc;
            color: #fff;
            border-color: #007bfc;
        }
        .erpnext-btn.btn-primary:hover {
            background: #0056b3;
            color: #fff;
        }
        .erpnext-btn.btn-secondary {
            background: #f5f7fa;
            color: #36414c;
            border-color: #d1d8dd;
        }
        .erpnext-btn.btn-secondary:hover {
            background: #e4e8ec;
        }
        .erpnext-input {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #f5f7fa;
            color: #36414c;
            width: 100%;
            margin-bottom: 1em;
        }
        .ann-card {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(44,62,80,0.04);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.2rem;
            position: relative;
        }
        .ann-card-title {
            font-size: 1.18em;
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.5em;
        }
        .ann-card-content {
            color: #36414c;
            margin-bottom: 0.7em;
        }
        .ann-card-meta {
            font-size: 0.93em;
            color: #888;
            margin-bottom: 0.5em;
        }
        .ann-card-actions {
            display: flex;
            gap: 0.5em;
        }
        .ann-form-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 2px rgba(44,62,80,0.03);
        }
        .ann-form-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #215967;
            margin-bottom: 1em;
        }
        .alert-success {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        .alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        .ann-empty { color: #888; font-size: 1.1rem; }
        @media (max-width: 900px) {
            .admin-main, .dashboard-section { padding: 1rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="dashboard-section">
                <div class="ann-header"><i class="fas fa-bullhorn"></i> Announcements</div>
                <?php if ($message): ?>
                    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Add/Edit Form -->
                <div class="ann-form-section">
                    <div class="ann-form-title">
                        <?= $editAnnouncement ? 'Edit Announcement' : 'Add New Announcement' ?>
                    </div>
                    <form method="post" style="margin-bottom:0;">
                        <input type="hidden" name="action" value="<?= $editAnnouncement ? 'edit' : 'add' ?>">
                        <?php if ($editAnnouncement): ?>
                            <input type="hidden" name="id" value="<?= $editAnnouncement['id'] ?>">
                        <?php endif; ?>
                        <input type="text" name="title" class="erpnext-input" placeholder="Title" value="<?= htmlspecialchars($editAnnouncement['title'] ?? '') ?>" required>
                        <textarea name="content" class="erpnext-input" placeholder="Content" rows="5" required><?= htmlspecialchars($editAnnouncement['content'] ?? '') ?></textarea>
                        <div style="display:flex;gap:1em;flex-wrap:wrap;">
                            <div>
                                <label>Start Date:</label>
                                <input type="date" name="start_date" class="erpnext-input" value="<?= htmlspecialchars($editAnnouncement['start_date'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div>
                                <label>End Date:</label>
                                <input type="date" name="end_date" class="erpnext-input" value="<?= htmlspecialchars($editAnnouncement['end_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>" required>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5em;">
                                <input type="checkbox" name="is_public" id="is_public" value="1" <?= !empty($editAnnouncement['is_public']) ? 'checked' : '' ?>>
                                <label for="is_public" style="margin:0;">Public (Show to everyone)</label>
                            </div>
                        </div>
                        <div style="margin:1em 0;">
                            <label>Target Roles (if not public):</label>
                            <select name="target_roles[]" class="erpnext-input" multiple style="min-width:180px;">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= isset($editAnnouncement['target_roles']) && in_array($role['id'], $editAnnouncement['target_roles']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:#888;">Hold Ctrl (Windows) or Cmd (Mac) to select multiple roles.</small>
                        </div>
                        <button type="submit" class="erpnext-btn btn-primary"><?= $editAnnouncement ? 'Update' : 'Add' ?> Announcement</button>
                        <?php if ($editAnnouncement): ?>
                            <a href="announcements.php" class="erpnext-btn btn-secondary" style="margin-left:0.7em;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Announcement List -->
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div class="ann-card">
                            <div class="ann-card-title"><?= htmlspecialchars($ann['title']) ?>
                                <?php if ($ann['is_public']): ?>
                                    <span style="background:#007bfc;color:#fff;font-size:0.8em;padding:2px 8px;border-radius:4px;margin-left:8px;">Public</span>
                                <?php endif; ?>
                            </div>
                            <div class="ann-card-meta">
                                <span>From: <?= date('M j, Y', strtotime($ann['start_date'])) ?></span>
                                <span>To: <?= date('M j, Y', strtotime($ann['end_date'])) ?></span>
                                <span style="margin-left:1em;">Last updated: <?= date('M j, Y g:i a', strtotime($ann['updated_at'])) ?></span>
                            </div>
                            <div class="ann-card-content"><?= nl2br(htmlspecialchars(mb_strimwidth($ann['content'], 0, 300, '...'))) ?></div>
                            <div class="ann-card-actions">
                                <a href="announcements.php?action=edit&id=<?= $ann['id'] ?>" class="erpnext-btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="announcements.php?action=delete&id=<?= $ann['id'] ?>" class="erpnext-btn btn-secondary btn-sm" onclick="return confirm('Delete this announcement?');"><i class="fas fa-trash"></i> Delete</a>
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
                    <div class="ann-empty">
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
