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
$sections = $db->query("SELECT id, section_name FROM sections ORDER BY section_name")->fetchAll(PDO::FETCH_ASSOC);

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
        .admin-dashboard {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            background: #f4f6fa;
        }
        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 2rem 2.5rem;
            max-width: 100%;
            background: none;
            border-radius: 0;
            box-shadow: none;
            transition: margin-left 0.2s;
        }
        @media (max-width: 900px) {
            .admin-dashboard {
                flex-direction: column;
            }
            .admin-main {
                margin-left: 60px;
                padding: 10px 5px 80px;
            }
        }
        @media (max-width: 600px) {
            .admin-dashboard {
                flex-direction: column;
            }
            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 5px 2px 80px;
            }
            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.3);
                z-index: 199;
            }
        }
        .sidebar-overlay {
            display: none;
        }
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .users-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .users-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .users-header .btn:hover {
            background: #217dbb;
        }
        .dashboard-section, .form-section {
            background: linear-gradient(135deg, #f8fafc 80%, #e3e9f7 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(44,62,80,0.10);
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
            transition: box-shadow 0.2s;
        }
        .dashboard-section:hover, .form-section:hover {
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
        }
        .dashboard-section h3, .dashboard-section h2 {
            font-size: 1.5rem;
            color: #2d3a4b;
            margin-bottom: 1.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .teacher-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #3498db;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
        }
        .badge-role {
            background: #eaf6ff;
            color: #3498db;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.95em;
            font-weight: 600;
            margin-left: 6px;
        }
        .success, .error {
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 1.1em;
            margin-bottom: 1.2rem;
        }
        .success { background: #eafaf1; color: #27ae60; }
        .error { background: #fee2e2; color: #e74c3c; }
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            background: #fff;
        }
        table th, table td {
            vertical-align: middle;
        }
        .dashboard-section label {
            font-weight: 600;
            color: #34495e;
        }
        .dashboard-section input, .dashboard-section select {
            margin-bottom: 1rem;
        }
        .dashboard-section button {
            background: linear-gradient(90deg, #3498db 60%, #217dbb 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            padding: 10px 28px;
            margin-top: 10px;
            transition: background 0.18s;
        }
        .dashboard-section button:hover {
            background: #00509e;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .search-bar input {
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 8px 14px;
            font-size: 1em;
            width: 220px;
        }
        @media (max-width: 900px) {
            .dashboard-section, .form-section { padding: 1.2rem 0.5rem; }
        }
        @media (max-width: 600px) {
            .dashboard-section, .form-section { padding: 0.7rem 0.2rem; }
            .teacher-avatar { width: 30px; height: 30px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="document.getElementById('adminSidebar').classList.remove('open');this.style.display='none';"></div>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Teacher</h1>
            </header>
            <div class="content">
                <?php if ($message): ?>
                    <p class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>"><?= esc($message) ?></p>
                <?php endif; ?>

                <div class="dashboard-section">
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
                    <div class="dashboard-section">
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
                                        <?= esc($section['section_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="password">New Password (leave blank to keep current):</label>
                            <input type="password" name="password" id="password">
                            <button type="submit" name="update_teacher">Update Teacher</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="dashboard-section">
                    <h3>All Teachers</h3>
                    <div class="table-container">
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
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td>
                                        <span class="teacher-avatar">
                                            <?php
                                            $initials = '';
                                            if (!empty($teacher['name'])) {
                                                $parts = explode(' ', $teacher['name']);
                                                foreach ($parts as $p) { $initials .= strtoupper($p[0]); if (strlen($initials) == 2) break; }
                                            } else {
                                                $initials = strtoupper(substr($teacher['username'], 0, 2));
                                            }
                                            echo esc($initials);
                                            ?>
                                        </span>
                                        <?= esc($teacher['id']) ?>
                                    </td>
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
                                            $sec = $db->prepare("SELECT section_name FROM sections WHERE id = ?");
                                            $sec->execute([$teacher['section_id']]);
                                            echo esc($sec->fetchColumn());
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Show overlay when sidebar is open on mobile
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('adminSidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var toggle = document.getElementById('sidebarToggle');
        if (sidebar && overlay && toggle) {
            toggle.addEventListener('click', function() {
                if (window.innerWidth <= 600) {
                    setTimeout(function() {
                        if (sidebar.classList.contains('open')) {
                            overlay.style.display = 'block';
                        } else {
                            overlay.style.display = 'none';
                        }
                    }, 10);
                }
            });
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth > 600) {
                overlay.style.display = 'none';
            }
        });
    });
    </script>
                <?php include 'includes/footer.php'; ?>
</body>
</html>
