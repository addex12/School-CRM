<?php
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: surveys.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Get target roles
$target_roles = json_decode($survey['target_roles'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Preview - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Survey Preview: <?php echo htmlspecialchars($survey['title']); ?></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="surveys.php">Surveys</a>
                <a href="survey_builder.php">Survey Builder</a>
                <a href="categories.php">Categories</a>
                <a href="users.php">Users</a>
                <a href="results.php">Results</a>
                <a href="../../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="content">
            <div class="survey-info">
                <h2>Survey Information</h2>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($survey['title']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($survey['description']); ?></p>
                <p><strong>Target Audience:</strong> 
                    <?php 
                    $roleNames = [
                        'student' => 'Students',
                        'teacher' => 'Teachers',
                        'parent' => 'Parents'
                    ];
                    $targetNames = array_map(function($role) use ($roleNames) {
                        return $roleNames[$role] ?? ucfirst($role);
                    }, $target_roles);
                    echo implode(', ', $targetNames);
                    ?>
                </p>
                <p><strong>Status:</strong> 
                    <?php if ($survey['is_active'] && $survey['starts_at'] <= date('Y-m-d H:i:s') && $survey['ends_at'] >= date('Y-m-d H:i:s')): ?>
                        <span class="status-active">Active</span>
                    <?php elseif (!$survey['is_active']): ?>
                        <span class="status-inactive">Inactive</span>
                    <?php else: ?>
                        <span class="status-pending">Pending/Scheduled</span>
                    <?php endif; ?>
                </p>
                <p><strong>Availability:</strong> 
                    <?php echo date('M j, Y g:i A', strtotime($survey['starts_at'])); ?> to 
                    <?php echo date('M j, Y g:i A', strtotime($survey['ends_at'])); ?>
                </p>
                <p><strong>Anonymous:</strong> <?php echo $survey['is_anonymous'] ? 'Yes' : 'No'; ?></p>
            </div>
            
            <div class="survey-preview">
                <h2>Survey Preview</h2>
                <div class="preview-container">
                    <?php foreach ($fields as $field): ?>
                        <div class="preview-field">
                            <h3><?php echo htmlspecialchars($field['field_label']); ?>
                                <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                            </h3>
                            
                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" disabled placeholder="Text input">
                            
                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea rows="3" disabled placeholder="Textarea input"></textarea>
                            
                            <?php elseif (in_array($field['field_type'], ['radio', 'checkbox', 'dropdown'])): ?>
                                <div class="options">
                                    <?php 
                                    $options = json_decode($field['field_options'], true);
                                    foreach ($options as $option): ?>
                                        <label class="option">
                                            <input type="<?php echo $field['field_type'] === 'radio' ? 'radio' : 'checkbox'; ?>" disabled>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($field['field_type'] === 'rating'): ?>
                                <div class="rating-container">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="rating-star">★</span>
                                    <?php endfor; ?>
                                </div>
                            
                            <?php elseif ($field['field_type'] === 'file'): ?>
                                <input type="file" disabled>
                            <?php endif; ?>
                            
                            <div class="field-meta">
                                <p><strong>Field Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $field['field_type'])); ?></p>
                                <p><strong>Field Name:</strong> <?php echo $field['field_name']; ?></p>
                                <p><strong>Required:</strong> <?php echo $field['is_required'] ? 'Yes' : 'No'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="survey_builder.php?survey_id=<?php echo $survey['id']; ?>" class="btn btn-edit">Edit Survey</a>
                <a href="results.php?survey_id=<?php echo $survey['id']; ?>" class="btn btn-primary">View Results</a>
            </div>
        </div>
    </div>
</body>
</html>
