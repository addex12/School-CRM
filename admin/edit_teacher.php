<?php
require_once '../includes/db.php';
require_once '../includes/config.php';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch all teachers for selection
$teachers = $db->query("SELECT t.id, t.name, t.email, t.username, u.id as user_id FROM teachers t LEFT JOIN users u ON t.username = u.username")->fetchAll(PDO::FETCH_ASSOC);

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
        // Update teachers table
        $update_teacher = $db->prepare("UPDATE teachers SET name = ?, email = ?, username = ? WHERE id = ?");
        $update_teacher->execute([$name, $email, $username, $teacher_id]);

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
        // Refresh selected teacher info
        header("Location: edit_teacher.php?edit_id=" . $teacher_id . "&updated=1");
        exit;
    }
}

if (isset($_GET['updated'])) {
    $message = "Teacher details updated successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Teacher</title>
</head>
<body>
    <h2>Edit Teacher</h2>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="get" action="edit_teacher.php">
        <label for="edit_id">Select Teacher:</label>
        <select name="edit_id" id="edit_id" onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <?php foreach ($teachers as $teacher): ?>
                <option value="<?= $teacher['id'] ?>" <?= (isset($selected_teacher) && $selected_teacher['id'] == $teacher['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacher['username']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Edit</button></noscript>
    </form>

    <?php if ($selected_teacher): ?>
        <h3>Editing: <?= htmlspecialchars($selected_teacher['name']) ?></h3>
        <form method="post">
            <input type="hidden" name="teacher_id" value="<?= $selected_teacher['id'] ?>">
            <label for="name">Full Name:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($selected_teacher['name']) ?>" required>
            <br><br>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($selected_teacher['email']) ?>" required>
            <br><br>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($selected_teacher['username']) ?>" required>
            <br><br>
            <label for="password">New Password (leave blank to keep current):</label>
            <input type="password" name="password" id="password">
            <br><br>
            <button type="submit" name="update_teacher">Update Teacher</button>
        </form>
    <?php endif; ?>

    <h3>All Teachers</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
        </tr>
        <?php foreach ($teachers as $teacher): ?>
        <tr>
            <td><?= htmlspecialchars($teacher['id']) ?></td>
            <td><?= htmlspecialchars($teacher['name']) ?></td>
            <td><?= htmlspecialchars($teacher['email']) ?></td>
            <td><?= htmlspecialchars($teacher['username']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
