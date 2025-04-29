<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = "Profile";

// Fetch user profile data
try {
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("No user data found for user_id: " . $_SESSION['user_id']);
        $user = [
            'id' => '',
            'username' => '',
            'email' => '',
            'first_name' => '',
            'last_name' => '',
            'avatar' => 'default.jpg'
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching profile data: " . $e->getMessage());
    $user = [
        'id' => '',
        'username' => '',
        'email' => '',
        'first_name' => '',
        'last_name' => '',
        'avatar' => 'default.jpg'
    ];
}

// Debugging: Log the user ID being used for the query
if (!isset($_SESSION['user_id'])) {
    error_log("User ID is not set in the session.");
} else {
    error_log("Fetching profile data for user_id: " . $_SESSION['user_id']);
}

// Ensure the query fetches all required fields
if (!$user || !is_array($user)) {
    error_log("No user data found for user_id: " . $_SESSION['user_id']);
    $user = [
        'first_name' => '',
        'last_name' => '',
        'username' => '',
        'email' => '',
        'profile_picture' => ''
    ];
} else {
    error_log("User data successfully fetched for user_id: " . $_SESSION['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $_SESSION['user_id']]);
        $_SESSION['success'] = "Profile updated successfully.";
        header("Location: profile.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error updating profile: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update profile. Please try again later.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $uploadDir = '../uploads/profile_pictures/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['profile_picture'];
    $fileName = basename($file['name']);
    $targetFilePath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$fileName, $_SESSION['user_id']]);
                $_SESSION['success'] = "Profile picture updated successfully.";
            } catch (PDOException $e) {
                error_log("Error updating profile picture: " . $e->getMessage());
                $_SESSION['error'] = "Failed to update profile picture. Please try again later.";
            }
        } else {
            $_SESSION['error'] = "Failed to upload the profile picture. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
    }
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
        }
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin-bottom: 2rem;
        }
        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #0984e3;
        }
        .profile-header h1 {
            font-size: 1.8rem;
            color: #34495e;
            margin-top: 1rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #34495e;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #dfe6e9;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #2d3436;
        }
        .form-control:focus {
            outline: none;
            border-color: #0984e3;
            box-shadow: 0 0 0 3px rgba(9, 132, 227, 0.2);
        }
        .btn-primary {
            background-color: #0984e3;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #74b9ff;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #dfe6e9;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="profile-container">
                <div class="profile-header">
                    <?php if (!empty($user['avatar']) && file_exists("../uploads/profile_pictures/" . $user['avatar'])): ?>
                        <img src="../uploads/profile_pictures/<?= htmlspecialchars($user['avatar']) ?>?t=<?= time() ?>" alt="Profile Picture">                    <?php else: ?>
                        <img src="../assets/images/default-profile.png" alt="Default Profile Picture">
                    <?php endif; ?>
                    <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
                </div>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>

                <div class="password-section">
                    <h2>Change Password</h2>
                    <form method="POST" action="change_password.php">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>