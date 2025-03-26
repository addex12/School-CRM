<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch all surveys
$surveys = $pdo->query("SELECT id, title, description, is_active, starts_at, ends_at FROM surveys ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Surveys - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="content">
            <h1>View Surveys</h1>
            
            <?php if (empty($surveys)): ?>
                <p>No surveys found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Availability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($surveys as $survey): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                <td><?php echo htmlspecialchars($survey['description']); ?></td>
                                <td><?php echo $survey['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($survey['starts_at'])); ?> - 
                                    <?php echo date('M j, Y g:i A', strtotime($survey['ends_at'])); ?>
                                </td>
                                <td>
                                    <a href="survey_preview.php?id=<?php echo $survey['id']; ?>" class="btn btn-primary">Preview</a>
                                    <a href="survey_builder.php?survey_id=<?php echo $survey['id']; ?>" class="btn btn-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
