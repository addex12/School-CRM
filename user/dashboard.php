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
        FROM surveys s    JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE (sr.role_id = ?
        OR (s.is_public = 1 AND sr.role_id IS NULL)) AND s.is_active = 1
        AND s.starts_at <= NOW() 
        AND s.ends_at >= NOW()"
    );
    $stmt->execute([$_SESSION['role_id']]);
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
        JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN survey_responses r ON s.id = r.survey_id AND r.user_id = ?
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.is_public = 1
          OR s.starts_at <= NOW()
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
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.is_public = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY s.ends_at ASC
        LIMIT 3
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .main-content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
        .dashboard-container {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        .dashboard-header {
            display: flex;
            align-items: center;
            gap: 1em;
            margin-bottom: 1.5em;
        }
        .dashboard-header h1 {
            color: #007bfc;
            font-size: 2.1em;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 28px 18px 22px 18px;
            text-align: center;
            transition: box-shadow 0.18s;
            border-left: 5px solid #007bfc;
        }
        .stat-card.completed { border-left: 5px solid #28a745; }
        .stat-card.pending { border-left: 5px solid #ffc107; }
        .stat-card h3 {
            margin-top: 0;
            color: #215967;
            font-size: 1.13em;
            font-weight: 600;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: #007bfc;
        }
        .stat-card.completed .stat-value { color: #28a745; }
        .stat-card.pending .stat-value { color: #ffc107; }
        .stat-card p { color: #888; margin: 0; }
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .erpnext-btn, .quick-action, .btn {
            background: #007bfc;
            color: #fff;
            border: 1px solid #007bfc;
            border-radius: 4px;
            padding: 10px 22px;
            font-weight: 500;
            font-size: 1em;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 2px;
        }
        .erpnext-btn:hover, .quick-action:hover, .btn:hover {
            background: #215967;
            color: #fff;
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 0.97em;
            border-radius: 4px;
        }
        .section-title {
            margin-top: 30px;
            color: #215967;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            font-size: 1.25em;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        .survey-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 22px;
        }
        .survey-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 22px 18px 18px 18px;
            position: relative;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: box-shadow 0.18s;
        }
        .survey-card.completed { border-left: 5px solid #28a745; }
        .survey-card[style*="border-left:4px solid #f1c40f;"] { border-left: 5px solid #f1c40f !important; }
        .survey-card[style*="border-left:4px solid #007bfc;"] { border-left: 5px solid #007bfc !important; }
        .survey-card h3 {
            margin-top: 0;
            color: #215967;
            font-size: 1.13em;
            font-weight: 600;
        }
        .survey-description {
            color: #36414c;
            margin: 10px 0 8px 0;
            font-size: 1em;
        }
        .survey-meta {
            font-size: 0.97em;
            color: #888;
            margin-top: auto;
        }
        .survey-status {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.9em;
            padding: 3px 10px;
            border-radius: 4px;
        }
        .status-completed {
            background: #d4edda;
            color: #28a745;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .time-left {
            font-weight: bold;
            color: #dc3545;
        }
        @media (max-width: 1000px) {
            .main-content-container { padding: 18px 4vw 0 4vw; }
            .dashboard-header h1 { font-size: 1.4em; }
        }
        @media (max-width: 700px) {
            .main-content-container { padding: 8px 2vw 0 2vw; }
            .dashboard-header { flex-direction: column; gap: 0.5em; }
            .survey-cards { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Available Surveys</h3>
                    <div class="stat-value"><?= $availableSurveys ?></div>
                    <p>Surveys you can take</p>
                </div>
                <div class="stat-card completed">
                    <h3>Completed Surveys</h3>
                    <div class="stat-value"><?= $completedSurveys ?></div>
                    <p>Surveys you've finished</p>
                </div>
                <div class="stat-card pending">
                    <h3>Pending Surveys</h3>
                    <div class="stat-value"><?= $pendingSurveys ?></div>
                    <p>Surveys awaiting your response</p>
                </div>
            </div>
            <div class="quick-actions">
                <a href="survey.php" class="erpnext-btn quick-action">
                    <i class="fas fa-poll"></i> View All Surveys
                </a>
                <a href="feedback.php" class="erpnext-btn quick-action">
                    <i class="fas fa-comment-alt"></i> Submit Feedback
                </a>
                <a href="messages.php" class="erpnext-btn quick-action">
                    <i class="fas fa-comments"></i> Start Chat
                </a>
            </div>
            <h2 class="section-title"><i class="fas fa-list"></i> Recent Surveys</h2>
            <?php if (empty($recentSurveys)): ?>
                <div class="survey-card" style="color:#888;">No recent surveys available.</div>
            <?php else: ?>
                <div class="survey-cards">
                    <?php foreach ($recentSurveys as $survey): 
                        $now = new DateTime();
                        $end = new DateTime($survey['ends_at']);
                        $diff = $now->diff($end);
                        $daysLeft = $diff->format('%a');
                    ?>
                        <div class="survey-card <?= $survey['completed'] ? 'completed' : '' ?>">
                            <h3><?= htmlspecialchars($survey['title']) ?></h3>
                            <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
                            <div class="survey-meta">
                                <p><strong>Deadline:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                                <p><strong>Time Left:</strong> <span class="time-left"><?= $daysLeft ?> days</span></p>
                            </div>
                            <?php if ($survey['completed']): ?>
                                <div class="survey-status status-completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php else: ?>
                                <div class="survey-status status-pending">
                                    <i class="fas fa-exclamation-circle"></i> Pending
                                </div>
                                <a href="survey_response.php?id=<?= $survey['id'] ?>" class="erpnext-btn btn">
                                    Take Survey
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Announcements Section -->
            <h2 class="section-title"><i class="fas fa-bullhorn"></i> Announcements</h2>
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
                <div class="survey-card" style="background:#fff3cd;color:#856404;">No announcements at this time.</div>
            <?php else: ?>
                <div class="survey-cards">
                    <?php foreach ($visibleAnnouncements as $idx => $a): ?>
                        <div class="survey-card" style="border-left:5px solid #f1c40f; position:relative;">
                            <h3><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($a['title']) ?></h3>
                            <div class="survey-description" id="ann-content-<?= $idx ?>">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($a['content'], 0, 250, '...'))) ?>
                                <?php if (mb_strlen($a['content']) > 250): ?>
                                    <a href="javascript:void(0);" 
                                       class="erpnext-btn btn-sm read-more-link" 
                                       style="background:#f1c40f;color:#215967;margin-left:8px;"
                                       data-type="announcement"
                                       data-title="<?= htmlspecialchars($a['title'], ENT_QUOTES) ?>"
                                       data-content="<?= htmlspecialchars($a['content'], ENT_QUOTES) ?>">
                                       Read more
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="survey-meta">
                                <strong>From:</strong> <?= date('M j, Y', strtotime($a['start_date'])) ?>
                                <strong>To:</strong> <?= date('M j, Y', strtotime($a['end_date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Knowledge Base Section -->
            <h2 class="section-title"><i class="fas fa-book"></i> Knowledge Base</h2>
            <?php
            $kb = $pdo->query("SELECT title, content, updated_at FROM knowledge_base ORDER BY updated_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (empty($kb)): ?>
                <div class="survey-card" style="background:#f8f9fa;color:#888;">No knowledge base articles yet.</div>
            <?php else: ?>
                <div class="survey-cards">
                    <?php foreach ($kb as $kidx => $article): ?>
                        <div class="survey-card" style="border-left:5px solid #007bfc; position:relative;">
                            <h3><i class="fas fa-book"></i> <?= htmlspecialchars($article['title']) ?></h3>
                            <div class="survey-description" id="kb-content-<?= $kidx ?>">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($article['content'], 0, 250, '...'))) ?>
                                <?php if (mb_strlen($article['content']) > 250): ?>
                                    <a href="javascript:void(0);" 
                                       class="erpnext-btn btn-sm read-more-link" 
                                       style="background:#007bfc;color:#fff;margin-left:8px;"
                                       data-type="kb"
                                       data-title="<?= htmlspecialchars($article['title'], ENT_QUOTES) ?>"
                                       data-content="<?= htmlspecialchars($article['content'], ENT_QUOTES) ?>">
                                       Read more
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="survey-meta">
                                <strong>Last updated:</strong> <?= date('M j, Y', strtotime($article['updated_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Popup Modal -->
            <div id="popupModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);z-index:9999;">
                <div style="background:#fff;max-width:480px;margin:7% auto;padding:28px 22px 18px 22px;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.13);position:relative;">
                    <span onclick="closePopup()" style="position:absolute;top:10px;right:18px;font-size:1.5em;color:#888;cursor:pointer;">&times;</span>
                    <h3 id="popupTitle" style="color:#215967;margin-top:0;"></h3>
                    <div id="popupContent" style="font-size:1em;color:#36414c;"></div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
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
    function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
    }
    // Utility functions for HTML escaping and nl2br
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

    // Responsive: Attach event listeners after DOM is ready
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