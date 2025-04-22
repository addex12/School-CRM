<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/db.php';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch all teachers for selection
$teachers = $db->query("SELECT t.id, t.name, t.email, t.username, t.subject_id, t.class_name_id, t.section_id, u.id as user_id FROM teachers t LEFT JOIN users u ON t.username = u.username")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all subjects, grades, and sections for dropdowns
$subjects = $db->query("SELECT id, subject_name FROM subjects")->fetchAll(PDO::FETCH_ASSOC);
$class_names = $db->query("SELECT id, grade FROM class_names")->fetchAll(PDO::FETCH_ASSOC);
$sections = $db->query("SELECT id, section FROM sections")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$selected_teacher = null;

// Handle teacher selection
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $db->prepare("SELECT t.*, u.id as user_id, u.email as user_email FROM teachers t LEFT JOIN users u ON t.username = u.username WHERE t.id = ?");
    $stmt->execute([$edit_id]);
    $selected_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $subject_id = $_POST['subject_id'];
    $class_name_id = $_POST['class_name_id'];
    $section_id = $_POST['section_id'];

    // Fetch current teacher info
    $stmt = $db->prepare("SELECT t.*, u.id as user_id FROM teachers t LEFT JOIN users u ON t.username = u.username WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check for duplicate email/username in users table (excluding current user)
    $check_stmt = $db->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $check_stmt->execute([$email, $username, $current['user_id']]);
    if ($check_stmt->fetch(PDO::FETCH_ASSOC)) {
        $message = "A user with this email or username already exists.";
    } else {
        // Update teachers table with subject, grade, section
        $update_teacher = $db->prepare("UPDATE teachers SET name = ?, email = ?, username = ?, subject_id = ?, class_name_id = ?, section_id = ? WHERE id = ?");
        $update_teacher->execute([$name, $email, $username, $subject_id, $class_name_id, $section_id, $teacher_id]);

        // Update users table
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_user = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $update_user->execute([$username, $email, $hashed_password, $current['user_id']]);
        } else {
            $update_user = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $update_user->execute([$username, $email, $current['user_id']]);
        }
        $message = "Teacher details updated successfully!";
        header("Location: edit_teacher.php?edit_id=" . $teacher_id . "&updated=1");
        exit;
    }
}

if (isset($_GET['updated'])) {
    $message = "Teacher details updated successfully!";
}

// Helper function to safely escape output and avoid deprecated warnings
function esc($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Teacher | Flipper School CRM</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-main {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px #0001;
            padding: 32px;
        }
        h2, h3 { color: #003366; }
        .form-section { margin-bottom: 32px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        label { display: block; margin-top: 12px; font-weight: 500; }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px;
        }
        button { background: #003366; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; cursor: pointer; margin-top: 16px; }
        button:hover { background: #00509e; }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; background: #fff; }
        th, td { padding: 8px 12px; border: 1px solid #e0e0e0; }
        th { background: #e9ecef; }
        .footer-dev { font-size: 13px; color: #b0c4de; }
        @media (max-width: 900px) {
            .admin-main { padding: 1rem 0.5rem; }
        }
        @media (max-width: 600px) {
            .admin-main { padding: 10px 2px 80px; }
            th, td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard" style="display:flex;min-height:100vh;background:#f4f6fa;">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header style="background:#003366;color:#fff;padding:16px 0;text-align:center;">
                <h1>Flipper School CRM - Admin Panel</h1>
                <div class="footer-dev">
                    Developed by: Adugna Gizaw &bull; Email: gizawadugna@gmail.com &bull;
                    <a href="https://www.linkedin.com/in/eleganceict" style="color:#b0c4de" target="_blank">LinkedIn</a> &bull;
                    <a href="https://twitter.com/eleganceict1" style="color:#b0c4de" target="_blank">Twitter</a> &bull;
                    <a href="https://github.com/addex12" style="color:#b0c4de" target="_blank">GitHub</a>
                </div>
            </header>
            <main>
                <h2>Edit Teacher</h2>
                <?php if ($message): ?>
                    <p class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>"><?= esc($message) ?></p>
                <?php endif; ?>

                <div class="form-section">
                    <form method="get" action="edit_teacher.php">
                        <label for="edit_id">Select Teacher:</label>
                        <select name="edit_id" id="edit_id" onchange="this.form.submit()">
                            <option value="">-- Select --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= esc($teacher['id']) ?>" <?= (isset($selected_teacher) && ($selected_teacher['id'] ?? null) == $teacher['id']) ? 'selected' : '' ?>>
                                    <?php
                                        $display_name = trim(esc($teacher['name']));
                                        $display_username = trim(esc($teacher['username']));
                                        if ($display_name && $display_username) {
                                            echo $display_name . " (" . $display_username . ")";
                                        } elseif ($display_name) {
                                            echo $display_name;
                                        } elseif ($display_username) {
                                            echo $display_username;
                                        } else {
                                            echo "Teacher #" . esc($teacher['id']);
                                        }
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button type="submit">Edit</button></noscript>
                    </form>
                </div>

                <?php if ($selected_teacher): ?>
                    <div class="form-section">
                        <h3>Editing: <?= esc($selected_teacher['name']) ?></h3>
                        <form method="post">
                            <input type="hidden" name="teacher_id" value="<?= esc($selected_teacher['id']) ?>">
                            <label for="name">Full Name:</label>
                            <input type="text" name="name" id="name" value="<?= esc($selected_teacher['name']) ?>" required>
                            <label for="email">Email:</label>
                            <input type="email" name="email" id="email" value="<?= esc($selected_teacher['email']) ?>" required>
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" value="<?= esc($selected_teacher['username']) ?>" required>
                            <label for="subject_id">Subject:</label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= esc($subject['id']) ?>" <?= ($selected_teacher['subject_id'] ?? '') == $subject['id'] ? 'selected' : '' ?>>
                                        <?= esc($subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="class_name_id">Grade:</label>
                            <select name="class_name_id" id="class_name_id" required>
                                <option value="">-- Select Grade --</option>
                                <?php foreach ($class_names as $grade): ?>
                                    <option value="<?= esc($grade['id']) ?>" <?= ($selected_teacher['class_name_id'] ?? '') == $grade['id'] ? 'selected' : '' ?>>
                                        <?= esc($grade['grade']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="section_id">Section:</label>
                            <select name="section_id" id="section_id" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= esc($section['id']) ?>" <?= ($selected_teacher['section_id'] ?? '') == $section['id'] ? 'selected' : '' ?>>
                                        <?= esc($section['section']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="password">New Password (leave blank to keep current):</label>
                            <input type="password" name="password" id="password">
                            <button type="submit" name="update_teacher">Update Teacher</button>
                        </form>
                    </div>
                <?php endif; ?>

                <h3>All Teachers</h3>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Subject</th>
                        <th>Grade</th>
                        <th>Section</th>
                    </tr>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?= esc($teacher['id']) ?></td>
                        <td><?= esc($teacher['name']) ?></td>
                        <td><?= esc($teacher['email']) ?></td>
                        <td><?= esc($teacher['username']) ?></td>
                        <td>
                            <?php
                            if (!empty($teacher['subject_id'])) {
                                $subj = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
                                $subj->execute([$teacher['subject_id']]);
                                echo esc($subj->fetchColumn());
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($teacher['class_name_id'])) {
                                $grd = $db->prepare("SELECT grade FROM class_names WHERE id = ?");
                                $grd->execute([$teacher['class_name_id']]);
                                echo esc($grd->fetchColumn());
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($teacher['section_id'])) {
                                $sec = $db->prepare("SELECT section FROM sections WHERE id = ?");
                                $sec->execute([$teacher['section_id']]);
                                echo esc($sec->fetchColumn());
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </main>
            <footer style="background:#003366;color:#fff;padding:16px 0;text-align:center;">
                <div class="footer-dev">
                    &copy; <?= date('Y') ?> Flipper School CRM. Developed by Adugna Gizaw &bull; Email: gizawadugna@gmail.com &bull;
                    <a href="https://www.linkedin.com/in/eleganceict" style="color:#b0c4de" target="_blank">LinkedIn</a> &bull;
                    <a href="https://twitter.com/eleganceict1" style="color:#b0c4de" target="_blank">Twitter</a> &bull;
                    <a href="https://github.com/addex12" style="color:#b0c4de" target="_blank">GitHub</a>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
