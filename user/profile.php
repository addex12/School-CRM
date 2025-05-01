<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Require authentication and config
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = "My Profile";

// Fetch user details from DB (join roles for role name)
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.created_at, u.last_active, u.role_id, r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback if user not found
if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - School CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Main style, do not touch Admin_sidebar/footer CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, outstanding, responsive profile UI.
         * All custom styles use adugna- prefix for patenting.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-profile-main {
            max-width: 420px;
            margin: 38px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 22px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-profile-header {
            text-align: center;
            margin-bottom: 1.5em;
        }
        .adugna-profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #e3eafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.7em auto;
            font-size: 2.2em;
            color: #1976d2;
        }
        .adugna-profile-username {
            font-size: 1.18em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.2em;
        }
        .adugna-profile-role {
            font-size: 0.97em;
            color: #215967;
            background: #e3eafc;
            border-radius: 4px;
            padding: 2px 10px;
            display: inline-block;
            margin-bottom: 0.7em;
        }
        .adugna-profile-details {
            margin: 1.2em 0 0.7em 0;
            display: flex;
            flex-direction: column;
            gap: 0.7em;
        }
        .adugna-profile-row {
            display: flex;
            align-items: center;
            gap: 0.7em;
            font-size: 0.97em;
            color: #36414c;
        }
        .adugna-profile-row i {
            font-size: 1em;
            color: #1976d2;
            min-width: 18px;
        }
        .adugna-profile-actions {
            display: flex;
            gap: 0.7em;
            margin-top: 1.2em;
            justify-content: center;
            flex-wrap: wrap;
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
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
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
        @media (max-width: 600px) {
            .adugna-profile-main { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-profile-header { margin-bottom: 1em; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="adugna-profile-main">
        <!-- Adugna: Profile header with avatar and username -->
        <div class="adugna-profile-header">
            <div class="adugna-profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="adugna-profile-username"><?= htmlspecialchars($user['username']) ?></div>
            <div class="adugna-profile-role">
                <i class="fas fa-user-tag"></i>
                <?= htmlspecialchars(ucfirst($user['role_name'] ?? 'User')) ?>
            </div>
        </div>
        <!-- Adugna: Profile details -->
        <div class="adugna-profile-details">
            <div class="adugna-profile-row">
                <i class="fas fa-envelope"></i>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="adugna-profile-row">
                <i class="fas fa-calendar-plus"></i>
                <span>Joined: <?= date('M j, Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="adugna-profile-row">
                <i class="fas fa-clock"></i>
                <span>Last Active: <?= date('M j, Y g:i A', strtotime($user['last_active'])) ?></span>
            </div>
            <div class="adugna-profile-row">
                <i class="fas fa-id-badge"></i>
                <span>User ID: <?= htmlspecialchars($user['id']) ?></span>
            </div>
        </div>
        <!-- Adugna: Profile actions -->
        <div class="adugna-profile-actions">
            <a href="edit_profile.php" class="adugna-btn adugna-btn-sm"><i class="fas fa-edit"></i> Edit Profile</a>
            <a href="../logout.php" class="adugna-btn adugna-btn-secondary adugna-btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

