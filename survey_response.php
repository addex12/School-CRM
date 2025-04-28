<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
require_once __DIR__ . '/includes/db.php';

// Check if the database connection is successful
if (!$db) {
    die("Database connection failed.");
}

// Validate survey access and get survey details
try {
    $stmt = $conn->prepare("
        SELECT s.id, s.title, s.description, s.is_anonymous,
               sf.id AS field_id, sf.field_type, sf.field_label, 
               sf.field_options, sf.is_required, sf.display_order
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        WHERE s.id = ? 
          AND s.is_public = 1
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY sf.display_order
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $survey_data = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($survey_data)) {
        die("Survey not found or not available.");
    }
} catch (Exception $e) {
    error_log("Error validating survey access: " . $e->getMessage());
    die("An error occurred while loading the survey.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Collect all answers for JSON storage
        $answers = [];
        foreach ($survey_data as $question) {
            $field_id = $question['field_id'];
            $value = $_POST['field_' . $field_id] ?? null;

            // Validate required fields
            if ($question['is_required'] && (empty($value) && $value !== "0")) {
                throw new Exception("Required question '{$question['field_label']}' was not answered");
            }

            $answers[$field_id] = is_array($value) ? $value : (string)$value;
        }

        // Insert survey response with JSON answers
        $stmt = $conn->prepare("
            INSERT INTO survey_responses 
            (survey_id, user_id, submitted_at, answers) 
            VALUES (?, NULL, NOW(), ?)
        ");
        $json_answers = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $stmt->bind_param("is", $survey_id, $json_answers);
        $stmt->execute();
        $response_id = $conn->insert_id;

        // Insert individual answers into response_data
        foreach ($survey_data as $question) {
            $field_id = $question['field_id'];
            $value = $_POST['field_' . $field_id] ?? null;
            if (!empty($value) || $value === "0") {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if ($val !== "" && $val !== null) {
                        $stmt = $conn->prepare("
                            INSERT INTO response_data 
                            (response_id, survey_id, field_id, field_value) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->bind_param("iiis", $response_id, $survey_id, $field_id, $val);
                        $stmt->execute();
                    }
                }
            }
        }

        $conn->commit();
        echo "Thank you for completing the survey!";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving survey response: " . $e->getMessage());
        die("An error occurred while submitting the survey.");
    }
}

// Prepare survey data for display
$survey = [
    'id' => $survey_data[0]['id'],
    'title' => $survey_data[0]['title'],
    'description' => $survey_data[0]['description'],
    'is_anonymous' => $survey_data[0]['is_anonymous'],
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> - Survey</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="survey-container">
        <h1><?= htmlspecialchars($survey['title']) ?></h1>
        <p><?= htmlspecialchars($survey['description']) ?></p>
        
        <form method="POST">
            <?php foreach ($survey['questions'] as $question): ?>
                <div>
                    <label>
                        <?= htmlspecialchars($question['label']) ?>
                        <?php if ($question['required']): ?>
                            <span>*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php switch ($question['type']):
                        case 'text': ?>
                            <input type="text" name="field_<?= $question['id'] ?>" required>
                            <?php break; 
                        case 'textarea': ?>
                            <textarea name="field_<?= $question['id'] ?>" required></textarea>
                            <?php break; 
                        case 'radio': ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <label>
                                    <input type="radio" name="field_<?= $question['id'] ?>" value="<?= htmlspecialchars($option) ?>" required>
                                    <?= htmlspecialchars($option) ?>
                                </label>
                            <?php endforeach; ?>
                            <?php break; 
                        case 'checkbox': ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <label>
                                    <input type="checkbox" name="field_<?= $question['id'] ?>[]" value="<?= htmlspecialchars($option) ?>">
                                    <?= htmlspecialchars($option) ?>
                                </label>
                            <?php endforeach; ?>
                            <?php break; 
                        case 'select': ?>
                            <select name="field_<?= $question['id'] ?>" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($question['options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php break; 
                    endswitch; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
