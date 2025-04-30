<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "View Survey";

$survey_id = $_GET['survey_id'] ?? null;

if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 2rem 2vw 2vw 2vw;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            letter-spacing: 0.01em;
        }
        .adugna-survey-meta {
            margin-bottom: 1.5rem;
            color: #444;
            font-size: 1em;
        }
        .adugna-fields-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .adugna-fields-list li {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5em;
            margin-bottom: 0.7em;
            padding: 0.8em 1em;
            display: flex;
            align-items: center;
            gap: 0.7em;
            font-size: 1em;
        }
        .adugna-fields-list .adugna-field-label {
            font-weight: 600;
            color: #215967;
            min-width: 120px;
        }
        .adugna-fields-list .adugna-field-type {
            font-size: 0.93em;
            color: #888;
            background: #e2efda;
            border-radius: 0.3em;
            padding: 0.1em 0.6em;
            margin-left: auto;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.4em;
            padding: 0.13rem 0.7rem;
            font-size: 0.92em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.92em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-card">
                <h2><i class="fas fa-poll"></i> <?= htmlspecialchars($survey['title']) ?></h2>
                <div class="adugna-survey-meta">
                    <div><b>Description:</b> <?= htmlspecialchars($survey['description'] ?? 'No description') ?></div>
                    <div><b>Created:</b> <?= date('M j, Y g:i A', strtotime($survey['created_at'])) ?></div>
                    <div><b>Status:</b> <span style="color:<?= $survey['status'] === 'active' ? '#27ae60' : '#e74c3c' ?>;font-weight:600;">
                        <?= ucfirst($survey['status']) ?></span></div>
                </div>
                <h3 style="font-size:1.08rem;color:#215967;margin-bottom:0.7em;">Survey Fields</h3>
                <ul class="adugna-fields-list">
                    <?php foreach ($fields as $field): ?>
                        <li>
                            <span class="adugna-field-label"><i class="fas fa-dot-circle"></i> <?= htmlspecialchars($field['label']) ?></span>
                            <span class="adugna-field-type">Type: <?= htmlspecialchars($field['type']) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($fields)): ?>
                        <li style="color:#888;">No fields defined for this survey.</li>
                    <?php endif; ?>
                </ul>
                <a href="surveys.php" class="adugna-btn" style="margin-top:1.5em;"><i class="fas fa-arrow-left"></i> Back to Surveys</a>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
