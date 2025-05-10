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

$pageTitle = "Dashboard";

// Fetch survey statistics
try {
    // Total available surveys
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM surveys s
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN survey_responses r ON s.id = r.survey_id AND r.user_id = ?
        WHERE (sr.role_id = ? OR s.is_public = 1)
          AND s.is_active = 1
          AND s.starts_at <= NOW()
          AND s.ends_at >= NOW()
          AND r.id IS NULL
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $availableSurveys = $stmt->fetchColumn();

    // Completed surveys count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT survey_id) 
        FROM survey_responses 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $completedSurveys = $stmt->fetchColumn();

    // Pending surveys
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM surveys s
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN survey_responses r ON s.id = r.survey_id AND r.user_id = ?
        WHERE (sr.role_id = ? OR s.is_public = 1)
          AND s.is_active = 1
          AND s.starts_at <= NOW()
          AND s.ends_at >= NOW()
          AND r.id IS NULL
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $pendingSurveys = $stmt->fetchColumn();

    // Recent surveys (limit to 3)
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.ends_at,
               (SELECT COUNT(*) FROM survey_responses r 
                WHERE r.survey_id = s.id AND r.user_id = ?) as completed
        FROM surveys s
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE (sr.role_id = ? OR s.is_public = 1)
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY s.ends_at ASC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $recentSurveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $availableSurveys = 0;
    $completedSurveys = 0;
    $pendingSurveys = 0;
    $recentSurveys = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Developer Info -->
    <!--
        Developer: Adugna Gizaw
        Email: gizawadugna@gmail.com
        LinkedIn: https://www.linkedin.com/in/eleganceict
        Twitter: https://twitter.com/eleganceict1
        GitHub: https://github.com/addex12
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Adugna: Main background and font */
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; margin:0; }

        /* Adugna: Main content container, responsive and centered */
        .adugna-main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 10px 0 10px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            width: 100%;
        }

        /* Adugna: Dashboard container */
        .adugna-dashboard-container {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        /* Adugna: Dashboard header, compact and branded */
        .adugna-dashboard-header {
            display: flex;
            align-items: center;
            gap: 0.7em;
            margin-bottom: 1.2em;
        }
        .adugna-dashboard-header h1 {
            color: #007bfc;
            font-size: 1.5em;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .adugna-dashboard-header i {
            font-size: 1.1em;
            color: #215967;
        }

        /* Adugna: Stats grid, responsive */
        .adugna-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        /* Adugna: Stat card, compact and branded */
        .adugna-stat-card {
            background: #fff;
            border-radius: 7px;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            padding: 18px 10px 14px 10px;
            text-align: center;
            transition: box-shadow 0.18s;
            border-left: 4px solid #007bfc;
            min-width: 0;
        }
        .adugna-stat-card.adugna-completed { border-left: 4px solid #28a745; }
        .adugna-stat-card.adugna-pending { border-left: 4px solid #ffc107; }
        .adugna-stat-card h3 {
            margin-top: 0;
            color: #215967;
            font-size: 1em;
            font-weight: 600;
        }
        .adugna-stat-value {
            font-size: 1.7em;
            font-weight: bold;
            margin: 7px 0;
            color: #007bfc;
        }
        .adugna-stat-card.adugna-completed .adugna-stat-value { color: #28a745; }
        .adugna-stat-card.adugna-pending .adugna-stat-value { color: #ffc107; }
        .adugna-stat-card p { color: #888; margin: 0; font-size: 0.93em; }
        .adugna-stat-percentage {
            font-size: 0.92em;
            color: #215967;
            margin-top: 4px;
        }

        /* Adugna: Quick actions, compact and responsive */
        .adugna-quick-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .adugna-btn, .adugna-quick-action {
            background: #007bfc;
            color: #fff;
            border: 1px solid #007bfc;
            border-radius: 3px;
            padding: 6px 14px;
            font-weight: 500;
            font-size: 0.97em;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 1px;
            min-width: 0;
        }
        .adugna-btn:hover, .adugna-quick-action:hover {
            background: #215967;
            color: #fff;
            box-shadow: 0 1px 4px rgba(44,62,80,0.12);
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-quick-action i {
            font-size: 0.95em;
            margin-right: 3px;
        }

        /* Adugna: Section title, compact and branded */
        .adugna-section-title {
            margin-top: 18px;
            color: #215967;
            border-bottom: 2px solid #eee;
            padding-bottom: 7px;
            font-size: 1.08em;
            font-weight: 600;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .adugna-section-title i {
            font-size: 1em;
            color: #007bfc;
        }

        /* Adugna: Survey cards grid, responsive */
        .adugna-survey-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        /* Adugna: Survey card, compact and branded */
        .adugna-survey-card {
            background: #fff;
            border-radius: 7px;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            padding: 14px 10px 10px 10px;
            position: relative;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: box-shadow 0.18s;
            border-left: 4px solid #007bfc;
        }
        .adugna-survey-card.adugna-completed { border-left: 4px solid #28a745; }
        .adugna-survey-card[style*="border-left:5px solid #f1c40f;"] { border-left: 4px solid #f1c40f !important; }
        .adugna-survey-card[style*="border-left:5px solid #007bfc;"] { border-left: 4px solid #007bfc !important; }
        .adugna-survey-card h3 {
            margin-top: 0;
            color: #215967;
            font-size: 1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .adugna-survey-description {
            color: #36414c;
            margin: 7px 0 6px 0;
            font-size: 0.97em;
        }
        .adugna-survey-meta {
            font-size: 0.91em;
            color: #888;
            margin-top: auto;
        }
        .adugna-survey-status {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.85em;
            padding: 2px 7px;
            border-radius: 3px;
        }
        .adugna-status-completed {
            background: #d4edda;
            color: #28a745;
        }
        .adugna-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .adugna-time-left {
            font-weight: bold;
            color: #dc3545;
        }

        /* Adugna: Responsive adjustments */
        @media (max-width: 1000px) {
            .adugna-main-content { padding: 12px 2vw 0 2vw; }
            .adugna-dashboard-header h1 { font-size: 1.15em; }
        }
        @media (max-width: 700px) {
            .adugna-main-content { padding: 4px 1vw 0 1vw; }
            .adugna-dashboard-header { flex-direction: column; gap: 0.3em; }
            .adugna-survey-cards { grid-template-columns: 1fr; }
            .adugna-stats-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .adugna-dashboard-header h1 { font-size: 1em; }
            .adugna-section-title { font-size: 0.98em; }
            .adugna-stat-value { font-size: 1.1em; }
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- Adugna: Header include -->
    <?php include 'includes/header.php'; ?>
    <div class="adugna-main-content">
        <div class="adugna-dashboard-container">
            <!-- Adugna: Dashboard header -->
            <div class="adugna-dashboard-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </div>
            <!-- Adugna: Stats grid -->
            <div class="adugna-stats-grid">
                <div class="adugna-stat-card">
                    <h3>Available Surveys</h3>
                    <div class="adugna-stat-value"><?= $availableSurveys ?></div>
                    <p>Surveys you can take</p>
                    <div class="adugna-stat-percentage">
                        <?= ($availableSurveys + $completedSurveys + $pendingSurveys) > 0
                            ? round(($availableSurveys / ($availableSurveys + $completedSurveys + $pendingSurveys)) * 100, 2)
                            : 0 ?>% of total surveys
                    </div>
                </div>
                <div class="adugna-stat-card adugna-completed">
                    <h3>Completed Surveys</h3>
                    <div class="adugna-stat-value"><?= $completedSurveys ?></div>
                    <p>Surveys you've finished</p>
                </div>
                <div class="adugna-stat-card adugna-pending">
                    <h3>Pending Surveys</h3>
                    <div class="adugna-stat-value"><?= $pendingSurveys ?></div>
                    <p>Surveys awaiting your response</p>
                </div>
            </div>
            <!-- Adugna: Quick actions -->
            <div class="adugna-quick-actions">
                <a href="survey.php" class="adugna-btn adugna-quick-action">
                    <i class="fas fa-poll"></i> All Surveys
                </a>
                <a href="feedback.php" class="adugna-btn adugna-quick-action">
                    <i class="fas fa-comment-alt"></i> Feedback
                </a>
                <a href="messages.php" class="adugna-btn adugna-quick-action">
                    <i class="fas fa-comments"></i> Chat
                </a>
            </div>
            <!-- Adugna: Recent Surveys Section -->
            <h2 class="adugna-section-title"><i class="fas fa-list"></i> Recent Surveys</h2>
            <?php if (empty($recentSurveys)): ?>
                <div class="adugna-survey-card" style="color:#888;">No recent surveys available.</div>
            <?php else: ?>
                <div class="adugna-survey-cards">
                    <?php foreach ($recentSurveys as $survey): 
                        $now = new DateTime();
                        $end = new DateTime($survey['ends_at']);
                        $diff = $now->diff($end);
                        $daysLeft = $diff->format('%a');
                    ?>
                        <div class="adugna-survey-card <?= $survey['completed'] ? 'adugna-completed' : '' ?>">
                            <h3><?= htmlspecialchars($survey['title']) ?></h3>
                            <p class="adugna-survey-description"><?= htmlspecialchars($survey['description']) ?></p>
                            <div class="adugna-survey-meta">
                                <p><strong>Deadline:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                                <p><strong>Time Left:</strong> <span class="adugna-time-left"><?= $daysLeft ?> days</span></p>
                            </div>
                            <?php if ($survey['completed']): ?>
                                <div class="adugna-survey-status adugna-status-completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php else: ?>
                                <div class="adugna-survey-status adugna-status-pending">
                                    <i class="fas fa-exclamation-circle"></i> Pending
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Adugna: Announcements Section -->
            <h2 class="adugna-section-title"><i class="fas fa-bullhorn"></i> Announcements</h2>
            <?php
            $userRoleId = $_SESSION['role_id'] ?? null;
            $announcements = $pdo->query("
                SELECT title, content, start_date, end_date, is_public, target_roles 
                FROM announcements 
                WHERE NOW() BETWEEN start_date AND end_date 
                ORDER BY start_date DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Filter: show if public OR assigned to user role
            $visibleAnnouncements = [];
            foreach ($announcements as $a) {
                $showToRole = false;
                if ($a['is_public']) {
                    $showToRole = true;
                }
                if ($userRoleId && !empty($a['target_roles'])) {
                    $rolesArr = array_map('trim', explode(',', $a['target_roles']));
                    if (in_array($userRoleId, $rolesArr)) {
                        $showToRole = true;
                    }
                }
                if ($showToRole) {
                    $visibleAnnouncements[] = $a;
                }
            }
            ?>
            <?php if (empty($visibleAnnouncements)): ?>
                <div class="adugna-survey-card" style="background:#fff3cd;color:#856404;">No announcements at this time.</div>
            <?php else: ?>
                <div class="adugna-survey-cards">
                    <?php foreach ($visibleAnnouncements as $idx => $a): ?>
                        <div class="adugna-survey-card" style="border-left:4px solid #f1c40f; position:relative;">
                            <h3><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($a['title']) ?></h3>
                            <div class="adugna-survey-description" id="ann-content-<?= $idx ?>">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($a['content'], 0, 250, '...'))) ?>
                                <?php if (mb_strlen($a['content']) > 250): ?>
                                    <a href="javascript:void(0);" 
                                       class="adugna-btn adugna-btn-sm read-more-link" 
                                       style="background:#f1c40f;color:#215967;margin-left:8px;"
                                       data-type="announcement"
                                       data-title="<?= htmlspecialchars($a['title'], ENT_QUOTES) ?>"
                                       data-content="<?= htmlspecialchars($a['content'], ENT_QUOTES) ?>">
                                       Read more
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="adugna-survey-meta">
                                <strong>From:</strong> <?= date('M j, Y', strtotime($a['start_date'])) ?>
                                <strong>To:</strong> <?= date('M j, Y', strtotime($a['end_date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Adugna: Knowledge Base Section -->
            <h2 class="adugna-section-title"><i class="fas fa-book"></i> Knowledge Base</h2>
            <?php
            $kb = $pdo->query("SELECT title, content, updated_at FROM knowledge_base ORDER BY updated_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (empty($kb)): ?>
                <div class="adugna-survey-card" style="background:#f8f9fa;color:#888;">No knowledge base articles yet.</div>
            <?php else: ?>
                <div class="adugna-survey-cards">
                    <?php foreach ($kb as $kidx => $article): ?>
                        <div class="adugna-survey-card" style="border-left:4px solid #007bfc; position:relative;">
                            <h3><i class="fas fa-book"></i> <?= htmlspecialchars($article['title']) ?></h3>
                            <div class="adugna-survey-description" id="kb-content-<?= $kidx ?>">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($article['content'], 0, 250, '...'))) ?>
                                <?php if (mb_strlen($article['content']) > 250): ?>
                                    <a href="javascript:void(0);" 
                                       class="adugna-btn adugna-btn-sm read-more-link" 
                                       style="background:#007bfc;color:#fff;margin-left:8px;"
                                       data-type="kb"
                                       data-title="<?= htmlspecialchars($article['title'], ENT_QUOTES) ?>"
                                       data-content="<?= htmlspecialchars($article['content'], ENT_QUOTES) ?>">
                                       Read more
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="adugna-survey-meta">
                                <strong>Last updated:</strong> <?= date('M j, Y', strtotime($article['updated_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Adugna: Popup Modal for Read More -->
            <div id="popupModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);z-index:9999;overflow-y:auto;">
                <div style="background:#fff;max-width:420px;margin:7% auto;padding:18px 12px 10px 12px;border-radius:7px;box-shadow:0 1px 8px rgba(0,0,0,0.13);position:relative;">
                    <span onclick="closePopup()" style="position:absolute;top:7px;right:12px;font-size:1.2em;color:#888;cursor:pointer;">&times;</span>
                    <h3 id="popupTitle" style="color:#215967;margin-top:0;font-size:1em;"></h3>
                    <div id="popupContent" style="font-size:0.97em;color:#36414c;max-height:70vh;overflow-y:auto;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Adugna: Footer include -->
    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
    // Adugna: Show popup modal for read more
    function showPopup(type, title, content) {
        var icon = '';
        if (type === 'announcement') {
            icon = '<i class="fas fa-bullhorn"></i> ';
        } else if (type === 'kb' || type === 'Knowledge Base') {
            icon = '<i class="fas fa-book"></i> ';
        }
        document.getElementById('popupTitle').innerHTML = icon + htmlspecialchars(title);
        document.getElementById('popupContent').innerHTML = nl2br(htmlspecialchars(content));
        document.getElementById('popupModal').style.display = 'block';
    }
    // Adugna: Close popup modal
    function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
    }
    // Adugna: Utility functions for HTML escaping and nl2br
    function htmlspecialchars(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    function nl2br(str) {
        return String(str).replace(/\r\n|\r|\n/g, "<br>");
    }
    // Adugna: Attach event listeners for read more links
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.read-more-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var type = this.getAttribute('data-type');
                var title = this.getAttribute('data-title');
                var content = this.getAttribute('data-content');
                showPopup(type, title, content);
            });
        });
    });
    </script>
    <script src="../includes/activity-tracker.js"></script>
</body>
</html>
