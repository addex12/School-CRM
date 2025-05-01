<?php
ob_start(); // Start output buffering

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection file
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Get survey_id from GET parameter and validate
$survey_id = null;
if (isset($_GET['survey_id']) && is_numeric($_GET['survey_id'])) {
    $survey_id = (int)$_GET['survey_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $survey_id = (int)$_GET['id'];
} else {
    die("Invalid or missing survey ID.");
}

// Validate survey access and get survey details
try {

    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.is_anonymous, s.starts_at, s.ends_at,
               sf.id AS field_id, sf.field_type, sf.field_label, 
               sf.field_options, sf.is_required, sf.display_order
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        WHERE s.id = :survey_id
          AND s.is_public = 1
          AND s.is_active = 1
        ORDER BY sf.display_order
    ");
    $stmt->execute([':survey_id' => $survey_id]);
    $survey_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($survey_data)) {
        // User-friendly message for surveys that exist but are not accessible
        $debug_stmt = $pdo->prepare("
            SELECT * FROM surveys 
            WHERE id = :survey_id
        ");
        $debug_stmt->execute([':survey_id' => $survey_id]);
        $debug_survey = $debug_stmt->fetch(PDO::FETCH_ASSOC);

        if ($debug_survey) {
            $now = date('Y-m-d H:i:s');
            if (empty($debug_survey['is_public']) || !$debug_survey['is_active']) {
                die('<div class="adugna-alert adugna-alert-warning">Sorry, this survey is not available to the public.</div>');
            }
            if (!empty($debug_survey['starts_at']) && $now < $debug_survey['starts_at']) {
                die('<div class="adugna-alert adugna-alert-warning">This survey is not yet open. It will be available from <b>' . htmlspecialchars($debug_survey['starts_at']) . '</b>.</div>');
            }
            if (!empty($debug_survey['ends_at']) && $now > $debug_survey['ends_at']) {
                die('<div class="adugna-alert adugna-alert-danger">This survey has ended. It was available until <b>' . htmlspecialchars($debug_survey['ends_at']) . '</b>.</div>');
            }
            die('<div class="adugna-alert adugna-alert-info">This survey is currently not available.</div>');
        } else {
            die('<div class="adugna-alert adugna-alert-danger">Survey not found.</div>');
        }
    }

    // Only initialize $survey if $survey_data is not empty
    if (!empty($survey_data)) {
        $survey = [
            'id' => $survey_data[0]['id'],
            'title' => $survey_data[0]['title'],
            'description' => $survey_data[0]['description'],
            'is_anonymous' => $survey_data[0]['is_anonymous'],
            'starts_at' => $survey_data[0]['starts_at'],
            'ends_at' => $survey_data[0]['ends_at'],
            'questions' => []
        ];

        foreach ($survey_data as $row) {
            $survey['questions'][] = [
                'id' => $row['field_id'],
                'type' => $row['field_type'],
                'label' => $row['field_label'],
                'options' => $row['field_options'] ? json_decode($row['field_options']) : [],
                'required' => $row['is_required']
            ];
        }
    } else {
        $survey = null;
    }

    // Determine survey status
    $now = date('Y-m-d H:i:s');
    if (!empty($survey['starts_at']) && $now < $survey['starts_at']) {
        $survey_status = 'upcoming';
    } elseif (
        (!empty($survey['starts_at']) && $now >= $survey['starts_at']) &&
        (empty($survey['ends_at']) || $now <= $survey['ends_at'])
    ) {
        $survey_status = 'ongoing';
    } elseif (!empty($survey['ends_at']) && $now > $survey['ends_at']) {
        $survey_status = 'ended';
    } else {
        $survey_status = 'unknown';
    }
} catch (Exception $e) {
    error_log("Error validating survey access: " . $e->getMessage());
    die("An error occurred while loading the survey.");
}

// Process form submission (only if ongoing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($survey_status !== 'ongoing') {
        die("This survey is not open for responses at this time.");
    }
    try {
        $pdo->beginTransaction();

        // Collect all answers for JSON storage
        $answers = [];
        foreach ($survey['questions'] as $question) {
            $field_id = $question['id'];
            $value = $_POST['field_' . $field_id] ?? null;

            // Validate required fields
            if ($question['required'] && (empty($value) && $value !== "0")) {
                throw new Exception("Required question '{$question['label']}' was not answered");
            }

            $answers[$field_id] = is_array($value) ? $value : (string)$value;
        }

        // If the survey is not anonymous, validate and collect the email
        $email = null;
        if (!$survey['is_anonymous']) {
            $email = $_POST['email'] ?? null;
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("A valid email address is required for this survey.");
            }
        }

        // Determine user_id for public surveys
        $user_id = $_SESSION['user_id'] ?? null; // Use NULL for anonymous/public users

        // Insert survey response with JSON answers and email
        $stmt = $pdo->prepare("
            INSERT INTO survey_responses 
            (survey_id, user_id, email, submitted_at, answers) 
            VALUES (:survey_id, :user_id, :email, NOW(), :answers)
        ");
        $stmt->execute([
            ':survey_id' => $survey_id,
            ':user_id' => $user_id,
            ':email' => $email,
            ':answers' => json_encode($answers, JSON_UNESCAPED_UNICODE)
        ]);
        $response_id = $pdo->lastInsertId();

        // Insert individual answers into response_data
        foreach ($survey['questions'] as $question) {
            $field_id = $question['id'];
            $value = $_POST['field_' . $field_id] ?? null;
            if (!empty($value) || $value === "0") {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if ($val !== "" && $val !== null) {
                        $stmt = $pdo->prepare("
                            INSERT INTO response_data 
                            (response_id, survey_id, field_id, field_value) 
                            VALUES (:response_id, :survey_id, :field_id, :field_value)
                        ");
                        $stmt->execute([
                            ':response_id' => $response_id,
                            ':survey_id' => $survey_id,
                            ':field_id' => $field_id,
                            ':field_value' => $val
                        ]);
                    }
                }
            }
        }

        $pdo->commit();
        // Store the success message in the session
        $_SESSION['success'] = "Thank you for completing the survey!";
        header("Location: survey.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving survey response: " . $e->getMessage());
        die("An error occurred while submitting the survey. Please try again later. Debug Info: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> - Survey</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!--
        Adugna Gizaw: Responsive, ERPNext/frappe-inspired survey form with adugna- prefix for all custom styles.
        Uses compact cards and buttons, and adapts to all screen sizes.
    -->
    <style>
    /* Developer: Adugna Gizaw - All adugna- styles are custom and patentable */
    .adugna-alert {
        padding: 0.7em 1em;
        border-radius: 6px;
        margin: 1vw auto 1vw auto;
        max-width: 600px;
        font-size: 1em;
        font-family: "Inter", "Segoe UI", Arial, sans-serif;
        border: 1px solid #e3e6f0;
        box-shadow: 0 1px 4px rgba(46,90,172,0.07);
    }
    .adugna-alert-success { background: #e6f9ed; color: #1c7c3c; border-color: #b6e7c9; }
    .adugna-alert-warning { background: #fffbe6; color: #b8860b; border-color: #ffe58f; }
    .adugna-alert-danger { background: #fff1f0; color: #b71c1c; border-color: #ffa39e; }
    .adugna-alert-info { background: #e6f7ff; color: #096dd9; border-color: #91d5ff; }

    .adugna-survey-container {
        max-width: 700px;
        margin: 2vw auto;
        padding: 2vw 1vw;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        font-family: "Inter", "Segoe UI", Arial, sans-serif;
    }
    .adugna-survey-title {
        font-size: clamp(1.2rem, 2vw, 2rem);
        font-weight: 700;
        color: #2e5aac;
        margin-bottom: 0.7vw;
        letter-spacing: 0.5px;
    }
    .adugna-survey-description {
        font-size: clamp(0.95rem, 1.1vw, 1.05rem);
        color: #4a4a4a;
        margin-bottom: 1vw;
    }
    .adugna-status-label {
        font-size: 0.95em;
        font-weight: 500;
        margin-bottom: 1vw;
        display: block;
    }
    .adugna-status-ongoing { color: #28a745; }
    .adugna-status-upcoming { color: #ffc107; }
    .adugna-status-ended { color: #dc3545; }
    .adugna-status-unknown { color: #888; }
    .adugna-question-group {
        margin-bottom: 1.2vw;
        padding: 1vw;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e3e6f0;
    }
    .adugna-form-label {
        font-weight: 500;
        color: #36414c;
        margin-bottom: 0.5em;
        display: block;
        font-size: 1em;
    }
    .adugna-form-control {
        width: 100%;
        padding: 0.5em 0.7em;
        border: 1px solid #b3c6f7;
        border-radius: 4px;
        background: #f5f7fa;
        color: #36414c;
        font-size: 1em;
    }
    .adugna-form-control:focus {
        outline: none;
        border-color: #2e5aac;
        background: #fff;
    }
    .adugna-form-check {
        margin-bottom: 0.5em;
        display: flex;
        align-items: center;
    }
    .adugna-form-check-label {
        margin-left: 0.4em;
        font-size: 0.97em;
    }
    .adugna-btn-submit {
        background: #2e5aac;
        color: white;
        padding: 0.5em 1.2em;
        border: none;
        border-radius: 4px;
        font-size: 1em;
        cursor: pointer;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.3em;
    }
    .adugna-btn-submit:disabled,
    .adugna-btn-submit[disabled] {
        background: #e3e6f0;
        color: #b0b0b0;
        cursor: not-allowed;
    }
    .adugna-btn-submit:hover:not(:disabled) {
        background: #1c387a;
        color: #fff;
    }
    .adugna-anonymous-notice {
        background: #e7f5fe;
        padding: 0.7em 1em;
        border-radius: 4px;
        margin-bottom: 1vw;
        border-left: 4px solid #3498db;
        font-size: 0.97em;
        color: #007bff;
    }
    .adugna-options-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }
    .adugna-options-list li {
        margin-bottom: 0.3em;
    }
    .adugna-icon {
        font-size: 1em;
        vertical-align: middle;
        margin-right: 0.2em;
    }
    @media (max-width: 700px) {
        .adugna-survey-container {
            padding: 3vw 2vw;
        }
    }
    @media (max-width: 500px) {
        .adugna-survey-container {
            padding: 2vw 1vw;
        }
        .adugna-survey-title {
            font-size: 1.1rem;
        }
    }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/public_header.php'; ?>

    <div class="adugna-survey-container">
        <h1 class="adugna-survey-title"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="adugna-survey-description"><?= htmlspecialchars($survey['description']) ?></p>
        <div class="adugna-status-label
            <?= $survey_status === 'upcoming' ? 'adugna-status-upcoming' : '' ?>
            <?= $survey_status === 'ended' ? 'adugna-status-ended' : '' ?>
            <?= $survey_status === 'ongoing' ? 'adugna-status-ongoing' : '' ?>
            <?= $survey_status === 'unknown' ? 'adugna-status-unknown' : '' ?>">
            <?php if ($survey_status === 'upcoming'): ?>
                <span class="adugna-icon">&#9203;</span>
                This survey is not yet open. It will be available from <b><?= htmlspecialchars($survey['starts_at']) ?></b>.
            <?php elseif ($survey_status === 'ended'): ?>
                <span class="adugna-icon">&#10060;</span>
                This survey has ended. It was available until <b><?= htmlspecialchars($survey['ends_at']) ?></b>.
            <?php elseif ($survey_status === 'ongoing'): ?>
                <span class="adugna-icon">&#9989;</span>
                This survey is currently open.
            <?php endif; ?>
        </div>
        <form method="POST">
            <?php if ($survey['is_anonymous']): ?>
                <div class="adugna-anonymous-notice">
                    <span class="adugna-icon">&#128373;</span>
                    This survey is anonymous. Your responses will not be linked to your identity.
                </div>
            <?php else: ?>
                <div class="adugna-question-group">
                    <label class="adugna-form-label">Email Address <span style="color:#dc3545;">*</span></label>
                    <input type="email" name="email" class="adugna-form-control" required <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                </div>
            <?php endif; ?>
            
            <?php foreach ($survey['questions'] as $question): ?>
                <div class="adugna-question-group">
                    <label class="adugna-form-label">
                        <?= htmlspecialchars($question['label']) ?>
                        <?php if ($question['required']): ?>
                            <span style="color:#dc3545;">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php switch ($question['type']):
                        case 'text': ?>
                            <input type="text" name="field_<?= $question['id'] ?>" class="adugna-form-control" <?= $question['required'] ? 'required' : '' ?> <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                            <?php break; 
                        case 'textarea': ?>
                            <textarea name="field_<?= $question['id'] ?>" class="adugna-form-control" <?= $question['required'] ? 'required' : '' ?> <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>></textarea>
                            <?php break;  
                        case 'radio': ?>
                            <ul class="adugna-options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="adugna-form-check">
                                        <input type="radio" name="field_<?= $question['id'] ?>" value="<?= htmlspecialchars($option) ?>" <?= $question['required'] ? 'required' : '' ?> <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                                        <label class="adugna-form-check-label"><?= htmlspecialchars($option) ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                        case 'checkbox': ?>
                            <ul class="adugna-options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="adugna-form-check">
                                        <input type="checkbox" name="field_<?= $question['id'] ?>[]" value="<?= htmlspecialchars($option) ?>" <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                                        <label class="adugna-form-check-label"><?= htmlspecialchars($option) ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                        case 'select': ?>
                            <select name="field_<?= $question['id'] ?>" class="adugna-form-control" <?= $question['required'] ? 'required' : '' ?> <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                                <option value="">-- Select --</option>
                                <?php foreach ($question['options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php break; 
                    endswitch; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align:center;">
                <button type="submit" class="adugna-btn-submit" <?= $survey_status !== 'ongoing' ? 'disabled' : '' ?>>
                    <span class="adugna-icon">&#10148;</span> Submit Survey
                </button>
            </div>
        </form>
    </div>
</body>
</html>

<?php require_once __DIR__ . '/footer.php';
ob_end_flush(); // Flush the output buffer
?>