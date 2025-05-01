<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireLogin();

// Fetch user info for avatar
$user = null;
if (isset($_SESSION['user_id'])) {
    require_once '../includes/config.php';
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $avatar = !empty($user['avatar']) ? $user['avatar'] : 'default.jpg';
} else {
    $avatar = 'default.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System - <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src=../includes/activity-tracker.js></script>
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .main-header {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            padding: 0;
            margin: 0;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            position: relative;
            z-index: 100;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.7em 2.2em 0.7em 1.2em;
            max-width: 1400px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 1.45em;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            margin: 0;
            white-space: nowrap;
        }
        .main-nav {
            display: flex;
            align-items: center;
            gap: 0.5em;
            flex-wrap: wrap;
        }
        .main-nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 5px;
            margin: 0 2px;
            font-size: 1em;
            transition: background 0.18s, color 0.18s;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5em;
            white-space: nowrap;
        }
        .main-nav a.active, .main-nav a:hover, .main-nav a:focus {
            background: #e2efda;
            color: #215967;
            font-weight: 600;
        }
        .main-nav a.logout {
            background: #e74c3c;
            color: #fff;
            font-weight: 600;
        }
        .main-nav a.logout:hover {
            background: #c0392b;
            color: #fff;
        }
        .header-avatar-container {
            display: flex;
            align-items: center;
            margin-left: 1.5em;
        }
        .header-avatar-img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        @media (max-width: 1100px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.7em;
                padding: 0.7em 1em;
            }
            .main-nav {
                gap: 0.3em;
                flex-wrap: wrap;
            }
            .header-avatar-container {
                margin-left: 0;
                margin-top: 0.5em;
            }
        }
        @media (max-width: 700px) {
            .header-content {
                padding: 0.5em 0.5em;
            }
            .logo {
                font-size: 1.1em;
            }
            .main-nav a {
                font-size: 0.97em;
                padding: 7px 10px;
            }
            .header-avatar-img {
                width: 36px;
                height: 36px;
            }
            .main-nav {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                gap: 0.2em;
            }
        }
        .erpnext-btn {
            background: #007bfc;
            color: #fff;
            border: 1px solid #007bfc;
            border-radius: 4px;
            padding: 7px 16px;
            font-weight: 500;
            font-size: 1em;
            transition: background 0.18s, color 0.18s;
            cursor: pointer;
            margin-left: 8px;
        }
        .erpnext-btn:hover, .erpnext-btn:focus {
            background: #215967;
            color: #fff;
        }
        .announcement-bar {
            background: #f1c40f;
            color: #215967;
            padding: 7px 1.5em;
            font-size: 1em;
            font-weight: 600;
            text-align: center;
            letter-spacing: 0.2px;
            word-break: break-word;
        }
        .announcement-bar i {
            margin-right: 7px;
        }
        @media (max-width: 500px) {
            .announcement-bar { font-size: 0.95em; padding: 6px 0.3em; }
            .header-content { padding: 0.3em 0.2em; }
        }
        .notification-bell-container {
            position: relative;
            margin-left: 1.5em;
        }
        .notification-bell {
            font-size: 1.7em;
            color: #fff;
            cursor: pointer;
            position: relative;
            transition: color 0.18s;
        }
        .notification-bell:hover {
            color: #f1c40f;
        }
        .notification-badge {
            position: absolute;
            top: -7px;
            right: -8px;
            background: #e74c3c;
            color: #fff;
            font-size: 0.85em;
            font-weight: bold;
            border-radius: 50%;
            padding: 2px 7px;
            min-width: 22px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.13);
        }
        .notification-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 38px;
            background: #fff;
            min-width: 260px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.13);
            border-radius: 8px;
            z-index: 1001;
            padding: 0.5em 0;
        }
        .notification-dropdown.active {
            display: block;
        }
        .notification-dropdown a {
            display: flex;
            align-items: center;
            gap: 0.7em;
            padding: 10px 18px;
            color: #215967;
            text-decoration: none;
            font-size: 1em;
            border-bottom: 1px solid #f5f7fa;
            transition: background 0.18s;
        }
        .notification-dropdown a:last-child {
            border-bottom: none;
        }
        .notification-dropdown a:hover {
            background: #e2efda;
        }
        .notification-count {
            background: #2563eb;
            color: #fff;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.93em;
            margin-left: auto;
            font-weight: 600;
        }
        @media (max-width: 700px) {
            .notification-dropdown { min-width: 180px; }
            .notification-bell { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <h1 class="logo">School CRM System</h1>
            <nav class="main-nav">
                <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="feedback.php" class="<?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a href="contact.php" class="<?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-phone"></i> Contact
                </a>
                <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Account
                </a>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
            <div style="display:flex;align-items:center;">
                <!-- Notification Bell -->
                <?php
                // --- Notification counts logic ---
                $userId = $_SESSION['user_id'] ?? null;
                $userRoleId = $_SESSION['role_id'] ?? null;
                $newSurveyCount = $newMessageCount = $newAnnouncementCount = $newTicketResponseCount = 0;

                if ($userId) {
                    // New Surveys: surveys the user has not responded to
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM surveys s
                        WHERE NOT EXISTS (
                            SELECT 1 FROM survey_responses sr
                            WHERE sr.survey_id = s.id AND sr.user_id = ?
                        )
                    ");
                    $stmt->execute([$userId]);
                    $newSurveyCount = (int)$stmt->fetchColumn();

                    // New Messages: unread messages for user (use correct column, e.g., to_user_id)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
                    $stmt->execute([$userId]);
                    $newMessageCount = (int)$stmt->fetchColumn();

                    // New Announcements: Example - announcements not yet seen by user
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcements a
                        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                        WHERE NOW() BETWEEN a.start_date AND a.end_date
                        AND (a.is_public = 1 OR (a.target_roles IS NOT NULL AND FIND_IN_SET(?, a.target_roles)))
                        AND ar.id IS NULL
                    ");
                    $stmt->execute([$userId, $userRoleId]);
                    $newAnnouncementCount = (int)$stmt->fetchColumn();

                    // New Ticket Responses: Example - tickets where user is owner and there are unread responses
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket_responses tr
                        INNER JOIN tickets t ON tr.ticket_id = t.id
                        WHERE t.user_id = ? AND tr.is_read_by_user = 0
                    ");
                    $stmt->execute([$userId]);
                    $newTicketResponseCount = (int)$stmt->fetchColumn();
                }
                $totalNotifications = $newSurveyCount + $newMessageCount + $newAnnouncementCount + $newTicketResponseCount;
                ?>
                <div class="notification-bell-container">
                    <span class="notification-bell" id="notificationBell" tabindex="0" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($totalNotifications > 0): ?>
                            <span class="notification-badge"><?= $totalNotifications ?></span>
                        <?php endif; ?>
                    </span>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <a href="surveys.php">
                            <i class="fas fa-poll"></i>
                            New Surveys
                            <span class="notification-count"><?= $newSurveyCount ?></span>
                        </a>
                        <a href="messages.php">
                            <i class="fas fa-envelope"></i>
                            New Messages
                            <span class="notification-count"><?= $newMessageCount ?></span>
                        </a>
                        <a href="announcements.php">
                            <i class="fas fa-bullhorn"></i>
                            New Announcements
                            <span class="notification-count"><?= $newAnnouncementCount ?></span>
                        </a>
                        <a href="tickets.php">
                            <i class="fas fa-ticket-alt"></i>
                            Ticket Responses
                            <span class="notification-count"><?= $newTicketResponseCount ?></span>
                        </a>
                    </div>
                </div>
                <script>
                // Toggle notification dropdown
                document.addEventListener('DOMContentLoaded', function() {
                    const bell = document.getElementById('notificationBell');
                    const dropdown = document.getElementById('notificationDropdown');
                    bell.addEventListener('click', function(e) {
                        dropdown.classList.toggle('active');
                        e.stopPropagation();
                    });
                    document.addEventListener('click', function() {
                        dropdown.classList.remove('active');
                    });
                    bell.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            dropdown.classList.toggle('active');
                            e.preventDefault();
                        }
                    });
                });
                </script>
                <!-- End Notification Bell -->
                <div class="header-avatar-container">
                    <a href="profile.php" title="My Profile">
                        <img src="../uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Profile Picture" class="header-avatar-img" onerror="this.src='../uploads/avatars/default.jpg'">
                    </a>
                </div>
            </div>
        </div>
        <?php
        // Show latest announcement bar if available (public or assigned to user role, or both)
        require_once '../includes/config.php';
        $userRoleId = $_SESSION['role_id'] ?? null;
        $announcements = $pdo->query("
            SELECT id, title, content, is_public, target_roles 
            FROM announcements 
            WHERE NOW() BETWEEN start_date AND end_date 
            ORDER BY start_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $showAnnouncement = null;
        foreach ($announcements as $ann) {
            $showToRole = false;
            if ($ann['is_public']) {
                $showToRole = true;
            }
            if ($userRoleId && !empty($ann['target_roles'])) {
                $rolesArr = array_map('trim', explode(',', $ann['target_roles']));
                if (in_array($userRoleId, $rolesArr)) {
                    $showToRole = true;
                }
            }
            if ($showToRole) {
                $showAnnouncement = $ann;
                break;
            }
        }
        if ($showAnnouncement):
        ?>
        <div class="announcement-bar" style="font-size:0.93em; cursor:pointer;" onclick="showAnnouncementPopup()">
            <i class="fas fa-bullhorn"></i>
            <strong><?= htmlspecialchars($showAnnouncement['title']) ?></strong>
            <span style="font-size:0.93em; color:#215967; margin-left:8px;">(Click to view details)</span>
        </div>
        <div id="announcementPopup" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);z-index:9999;">
            <div style="background:#fff;max-width:420px;margin:8% auto;padding:28px 22px 18px 22px;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.13);position:relative;">
                <span onclick="document.getElementById('announcementPopup').style.display='none';" style="position:absolute;top:10px;right:18px;font-size:1.5em;color:#888;cursor:pointer;">&times;</span>
                <h3 style="color:#215967;margin-top:0;"><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($showAnnouncement['title']) ?></h3>
                <div style="font-size:1em;color:#36414c;"><?= nl2br(htmlspecialchars($showAnnouncement['content'])) ?></div>
            </div>
        </div>
        <script>
        function showAnnouncementPopup() {
            document.getElementById('announcementPopup').style.display = 'block';
        }
        </script>
        <?php endif; ?>
    </header>
    <main class="content-wrapper"></main>
</body>
</html>