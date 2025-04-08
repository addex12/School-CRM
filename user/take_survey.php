<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid survey ID.";
    header("Location: dashboard.php");
    exit();
}

$survey_id = $_GET['id'];

// Fetch survey details
$stmt = $pdo->prepare("
    SELECT s.*, GROUP_CONCAT(sf.field_label) AS questions
    FROM surveys s
    LEFT JOIN survey_fields sf ON s.id = sf.survey_id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        foreach ($_POST['responses'] as $field_id => $response) {
            $stmt = $pdo->prepare("INSERT INTO survey_responses (survey_id, user_id, field_id, response) VALUES (?, ?, ?, ?)");
            $stmt->execute([$survey_id, $_SESSION['user_id'], $field_id, $response]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Survey submitted successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error submitting survey: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($survey['title']); ?></h1>
        <p><?= htmlspecialchars($survey['description']); ?></p>
        <form method="POST">
            <?php foreach (explode(',', $survey['questions']) as $index => $question): ?>
                <div class="form-group">
                    <label for="response_<?= $index; ?>"><?= htmlspecialchars($question); ?></label>
                    <input type="text" id="response_<?= $index; ?>" name="responses[<?= $index; ?>]" required>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Submit Survey</button>
            <a href="dashboard.php" class="btn">Cancel</a>
        </form>
    </div>
</body>
</html>
