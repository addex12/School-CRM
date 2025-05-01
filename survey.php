<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */

// Set timezone for survey display
date_default_timezone_set('Africa/Nairobi');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection file and public header
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/public_header.php';

// Display the success message if it exists
if (isset($_SESSION['success'])): ?>
    <div class="adugna-alert adugna-alert-success" style="margin: 20px auto; max-width: 800px;">
        <?= htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif;

// Fetch all public surveys (active and public, regardless of date)
try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, starts_at, ends_at, is_anonymous
        FROM surveys 
        WHERE is_public = 1 
          AND is_active = 1
        ORDER BY starts_at DESC
    ");
    $stmt->execute();
    $public_surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $public_surveys = [];
}
?>

<!--
    Adugna Gizaw: Responsive, ERPNext/frappe-inspired survey list with adugna- prefix for all custom styles.
    Uses compact cards and buttons, and adapts to all screen sizes.
-->
<div class="adugna-survey-list-container">
    <h2 class="adugna-title">Available Public Surveys</h2>
    <?php if (!empty($public_surveys)): ?>
        <div class="adugna-survey-list-flex">
        <?php foreach ($public_surveys as $survey): ?>
            <?php
                // Determine survey status for display and button logic
                $now = date('Y-m-d H:i:s');
                $status = '';
                if (!empty($survey['starts_at']) && $now < $survey['starts_at']) {
                    $status = 'upcoming';
                } elseif (
                    (!empty($survey['starts_at']) && $now >= $survey['starts_at']) &&
                    (empty($survey['ends_at']) || $now <= $survey['ends_at'])
                ) {
                    $status = 'ongoing';
                } elseif (!empty($survey['ends_at']) && $now > $survey['ends_at']) {
                    $status = 'ended';
                }
            ?>
            <div class="adugna-survey-card">
                <div class="adugna-survey-card-header">
                    <span class="adugna-survey-title"><?= htmlspecialchars($survey['title']) ?></span>
                    <?php if ($status === 'ongoing'): ?>
                        <span class="adugna-status adugna-status-ongoing" title="Ongoing">&#9679;</span>
                    <?php elseif ($status === 'upcoming'): ?>
                        <span class="adugna-status adugna-status-upcoming" title="Upcoming">&#9679;</span>
                    <?php else: ?>
                        <span class="adugna-status adugna-status-ended" title="Ended">&#9679;</span>
                    <?php endif; ?>
                </div>
                <div class="adugna-survey-description"><?= htmlspecialchars($survey['description']) ?></div>
                <div class="adugna-survey-dates">
                    <span class="adugna-icon">&#128197;</span>
                    <span>
                        <?= htmlspecialchars($survey['starts_at']) ?> - <?= htmlspecialchars($survey['ends_at']) ?>
                    </span>
                </div>
                <div class="adugna-survey-action">
                    <?php if ($status === 'ongoing'): ?>
                        <?php if ($survey['is_anonymous']): ?>
                            <!-- Anonymous survey: direct link -->
                            <a href="/survey_response.php?id=<?= $survey['id'] ?>" class="adugna-btn adugna-btn-primary" title="Take Survey">
                                <span class="adugna-icon">&#9998;</span> Take Survey
                            </a>
                        <?php else: ?>
                            <!-- Not anonymous: ask for email before proceeding -->
                            <form action="/survey_response.php" method="get" class="adugna-email-form">
                                <input type="hidden" name="id" value="<?= $survey['id'] ?>">
                                <input type="email" name="email" class="adugna-input-email" placeholder="Your Email" required title="Enter your email">
                                <button type="submit" class="adugna-btn adugna-btn-primary" title="Take Survey">
                                    <span class="adugna-icon">&#9998;</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($status === 'upcoming'): ?>
                        <span class="adugna-btn adugna-btn-warning" title="Not Yet Open" style="pointer-events:none;">
                            <span class="adugna-icon">&#9203;</span> Not Yet Open
                        </span>
                    <?php else: ?>
                        <span class="adugna-btn adugna-btn-disabled" title="Closed" style="pointer-events:none;">
                            <span class="adugna-icon">&#10060;</span> Closed
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="adugna-empty-message">No public surveys are currently available.</p>
    <?php endif; ?>
</div>

<!--
    Adugna Gizaw: Responsive, ERPNext/frappe-inspired custom styles for survey cards and buttons.
    All classes prefixed with adugna- for branding and patenting.
-->
<style>
/* Developer: Adugna Gizaw - All adugna- styles are custom and patentable */
.adugna-survey-list-container {
    max-width: 900px;
    margin: 2vw auto;
    padding: 2vw 1vw;
    background: #f8fafc;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
}
.adugna-title {
    font-size: clamp(1.2rem, 2vw, 2rem);
    font-weight: 700;
    color: #2e5aac;
    margin-bottom: 1.5vw;
    letter-spacing: 0.5px;
}
.adugna-survey-list-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5vw;
    justify-content: flex-start;
}
.adugna-survey-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(46,90,172,0.07);
    border: 1px solid #e3e6f0;
    padding: 1.2vw 1vw 1vw 1vw;
    min-width: 260px;
    max-width: 340px;
    flex: 1 1 260px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: box-shadow 0.2s;
    margin-bottom: 0.5vw;
}
.adugna-survey-card:hover {
    box-shadow: 0 4px 16px rgba(46,90,172,0.13);
    border-color: #b3c6f7;
}
.adugna-survey-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5vw;
}
.adugna-survey-title {
    font-size: clamp(1rem, 1.5vw, 1.2rem);
    font-weight: 600;
    color: #2e5aac;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.adugna-status {
    font-size: 0.9em;
    margin-left: 0.5em;
    vertical-align: middle;
}
.adugna-status-ongoing { color: #28a745; }
.adugna-status-upcoming { color: #ffc107; }
.adugna-status-ended { color: #dc3545; }
.adugna-survey-description {
    color: #4a4a4a;
    font-size: clamp(0.95rem, 1.1vw, 1.05rem);
    margin-bottom: 0.7vw;
    min-height: 2.2em;
}
.adugna-survey-dates {
    font-size: 0.93em;
    color: #888;
    margin-bottom: 0.7vw;
    display: flex;
    align-items: center;
    gap: 0.3em;
}
.adugna-icon {
    font-size: 1em;
    vertical-align: middle;
    margin-right: 0.2em;
}
.adugna-survey-action {
    display: flex;
    align-items: center;
    gap: 0.5em;
    margin-top: 0.5vw;
}
.adugna-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3em;
    padding: 0.35em 0.8em;
    font-size: 0.97em;
    border-radius: 4px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    text-decoration: none;
    min-width: 0;
    min-height: 0;
}
.adugna-btn-primary {
    background: #2e5aac;
    color: #fff;
}
.adugna-btn-primary:hover, .adugna-btn-primary:focus {
    background: #1c387a;
    color: #fff;
}
.adugna-btn-warning {
    background: #ffc107;
    color: #fff;
}
.adugna-btn-disabled {
    background: #e3e6f0;
    color: #b0b0b0;
    cursor: not-allowed;
}
.adugna-email-form {
    display: flex;
    align-items: center;
    gap: 0.3em;
}
.adugna-input-email {
    font-size: 0.97em;
    padding: 0.25em 0.5em;
    border: 1px solid #b3c6f7;
    border-radius: 4px;
    outline: none;
    min-width: 120px;
    max-width: 160px;
    transition: border 0.15s;
}
.adugna-input-email:focus {
    border-color: #2e5aac;
}
.adugna-empty-message {
    color: #888;
    font-size: 1.1em;
    text-align: center;
    margin: 2vw 0;
}

/* Responsive design for all screens */
@media (max-width: 900px) {
    .adugna-survey-list-flex {
        gap: 2vw;
    }
    .adugna-survey-card {
        min-width: 180px;
        max-width: 100%;
        padding: 2vw 2vw 1.5vw 2vw;
    }
}
@media (max-width: 600px) {
    .adugna-survey-list-container {
        padding: 2vw 2vw;
    }
    .adugna-survey-list-flex {
        flex-direction: column;
        gap: 2vw;
    }
    .adugna-survey-card {
        min-width: 0;
        width: 100%;
        max-width: 100%;
        margin-bottom: 2vw;
    }
    .adugna-title {
        font-size: 1.2rem;
    }
}
</style>
<!-- End Adugna Gizaw custom adugna- styles -->

<?php require_once __DIR__ . '/footer.php'; ?>