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

$pageTitle = "Add Teacher";

// Check if 'classes' table exists, if not, skip class fetching and show warning
$classes = [];
$class_table_exists = false;
try {
    $pdo->query("SELECT 1 FROM classes LIMIT 1");
    $class_table_exists = true;
} catch (PDOException $e) {
    $class_table_exists = false;
}

// Only fetch classes if table exists
if ($class_table_exists) {
    $class_stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
    $classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $class_id = $_POST['class_id'] ?? null;

    if (!$user_id) {
        $error = "User selection is required.";
    } else {
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $check->execute([$user_id]);
        if ($check->fetch()) {
            $error = "Teacher already exists.";
        } else {
            $insert = $pdo->prepare("INSERT INTO teachers (user_id, class_id) VALUES (?, ?)");
            if ($insert->execute([$user_id, $class_id ?: null])) {
                header("Location: teachers.php?msg=Teacher+added+successfully");
                exit;
            } else {
                $error = "Failed to add teacher.";
            }
        }
    }
}

// If coming from teachers.php with user_id param, pre-select user
$preselect_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin: 2rem auto;
            max-width: 500px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card h2 {
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
        .adugna-form-group select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 14px;
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
        .adugna-error-message {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        @media (max-width: 600px) {
            .adugna-card { padding: 0.7rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <?php if ($error): ?>
                    <div class="adugna-error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!$class_table_exists): ?>
                    <div class="adugna-error-message">
                        <b>Class table not found.</b> Please run the migration to create the <code>classes</code> table.<br>
                        <span style="font-size:0.95em;">
                            <b>Migration SQL:</b>
                            <pre style="background:#f5f7fa;padding:8px;border-radius:4px;overflow-x:auto;font-size:0.93em;">
ALTER TABLE teachers ADD COLUMN class_id INT NULL;
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
                            </pre>
                            <b>How to apply:</b> Place the above SQL in a migration file in your <code>migrations/</code> folder and run it.
                        </span>
                    </div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="adugna-form-group">
                        <label for="user_id">Select Teacher User</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($preselect_user_id == $user['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adugna-form-group">
                        <label for="class_id">Assign to Class (optional)</label>
                        <select name="class_id" id="class_id" <?= !$class_table_exists ? 'disabled' : '' ?>>
                            <option value="">-- None --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="adugna-btn">
                        <i class="fas fa-user-plus"></i> Add Teacher
                    </button>
                    <a href="teachers.php" class="adugna-btn adugna-btn-secondary" style="margin-left:10px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
