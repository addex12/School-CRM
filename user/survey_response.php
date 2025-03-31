<?php
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM surveys s
    WHERE s.id = ? 
    AND s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}

// Check permission using role ID
$allowed_roles = json_decode($survey['target_roles'], true);
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role_id = $stmt->fetchColumn();

if (!in_array($user_role_id, $allowed_roles)) {
    header("Location: dashboard.php?error=not_authorized");
    exit();
}

// Check if user has already completed this survey
$stmt = $pdo->prepare("SELECT id FROM survey_responses WHERE survey_id = ? AND user_id = ?");
$stmt->execute([$survey_id, $_SESSION['user_id']]);
$completed = $stmt->fetch();

if ($completed) {
    header("Location: dashboard.php?error=already_completed");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $survey_id = $_POST['survey_id'];
    $user_id = $_SESSION['user_id'];
    $answers = [];

    // Collect and validate answers
    foreach ($fields as $field) {
        $field_name = $field['field_name'];
        if (isset($_POST[$field_name])) {
            $answers[$field_name] = is_array($_POST[$field_name]) 
                ? $_POST[$field_name] // Keep array for checkboxes
                : htmlspecialchars($_POST[$field_name]); // Sanitize input
        } else {
            $answers[$field_name] = null; // Handle unanswered fields
        }
    }

    $encoded_answers = json_encode($answers, JSON_UNESCAPED_UNICODE); // Encode the answers as JSON

    // Insert the response into the database
    $stmt = $pdo->prepare("
        INSERT INTO survey_responses (survey_id, user_id, answers, submitted_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$survey_id, $user_id, $encoded_answers]);

    $_SESSION['success'] = "Your responses have been submitted successfully!";
    header("Location: thank_you.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <header>
        <h1>Survey: <?php echo htmlspecialchars($survey['title']); ?></h1>
        <nav>
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    
    <div class="survey-content">
        <div class="survey-header">
            <h2 class="survey-title"><?= htmlspecialchars($survey['title']) ?></h2>
            <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
            <div class="survey-meta">
                <p>Available from <?= date('M j, Y g:i A', strtotime($survey['starts_at'])) ?> to <?= date('M j, Y g:i A', strtotime($survey['ends_at'])) ?></p>
            </div>
        </div>
        <?php if (isset($errors['system'])): ?>
            <div class="error-message"><?= $errors['system'] ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?php foreach ($fields as $field): ?>
                <div class="form-field">
                    <!-- Render input fields dynamically -->
                    <?php if ($field['field_type'] === 'text'): ?>
                        <input type="text" name="<?= $field['field_name'] ?>" class="form-control"
                               value="<?= htmlspecialchars($_POST[$field['field_name']] ?? '') ?>">
                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                        <textarea name="<?= $field['field_name'] ?>" class="form-control" rows="4"><?= 
                            htmlspecialchars($_POST[$field['field_name']] ?? '') ?></textarea>
                    <?php elseif ($field['field_type'] === 'radio'): ?>
                        <?php foreach (json_decode($field['field_options']) as $option): ?>
                            <div class="form-check">
                                <input type="radio" name="<?= $field['field_name'] ?>" 
                                       value="<?= htmlspecialchars($option) ?>" class="form-check-input">
                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                        <?php foreach (json_decode($field['field_options']) as $option): ?>
                            <div class="form-check">
                                <input type="checkbox" name="<?= $field['field_name'] ?>[]" 
                                       value="<?= htmlspecialchars($option) ?>" class="form-check-input">
                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($field['field_type'] === 'file'): ?>
                        <input type="file" name="<?= $field['field_name'] ?>" class="form-control">
                    <?php endif; ?>
                    <?php if (isset($errors[$field['field_name']])): ?>
                        <div class="error"><?= $errors[$field['field_name']] ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Submit Response</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Initialize rating fields
    document.querySelectorAll('.rating-container').forEach(container => {
        const stars = container.querySelectorAll('.rating-star');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        
        // Set initial stars if value exists
        if (hiddenInput.value) {
            const value = parseInt(hiddenInput.value);
            stars.forEach((star, i) => {
                if (i < value) {
                    star.classList.add('active');
                }
            });
        }
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                hiddenInput.value = value;
            });
        });
    });
    
    // Preview image before upload
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.match('image.*')) {
                const reader = new FileReader();
                
                reader.onload = function(readerEvent) {
                    // Remove existing preview if any
                    const existingPreview = input.nextElementSibling;
                    if (existingPreview && existingPreview.classList.contains('file-preview')) {
                        existingPreview.remove();
                    }
                    
                    // Create new preview
                    const preview = document.createElement('img');
                    preview.src = readerEvent.target.result;
                    preview.className = 'file-preview';
                    input.parentNode.insertBefore(preview, input.nextSibling);
                }
                
                reader.readAsDataURL(file);
            }
        });
    });
</script>
</body>
</html>