<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$response_id = $_GET['id'] ?? 0;

// Get response info
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.email, u.role, s.title as survey_title
    FROM survey_responses r
    JOIN users u ON r.user_id = u.id
    JOIN surveys s ON r.survey_id = s.id
    WHERE r.id = ?
");
$stmt->execute([$response_id]);
$response = $stmt->fetch();

if (!$response) {
    header("Location: results.php");
    exit();
}

// Get response data
$stmt = $pdo->prepare("
    SELECT d.*, f.field_label, f.field_type
    FROM response_data d
    JOIN survey_fields f ON d.field_id = f.id
    WHERE d.response_id = ?
    ORDER BY f.display_order
");
$stmt->execute([$response_id]);
$response_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Response Details - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .response-info {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .response-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .response-item {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .response-item h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .file-preview {
            max-width: 100%;
            max-height: 200px;
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Response Details</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="surveys.php">Surveys</a>
                <a href="survey_builder.php">Survey Builder</a>
                <a href="categories.php">Categories</a>
                <a href="users.php">Users</a>
                <a href="results.php">Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="content">
            <div class="response-info">
                <h2><?php echo htmlspecialchars($response['survey_title']); ?></h2>
                <p><strong>Respondent:</strong> <?php echo htmlspecialchars($response['username']); ?> (<?php echo ucfirst($response['role']); ?>)</p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($response['email']); ?></p>
                <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($response['submitted_at'])); ?></p>
            </div>
            
            <div class="response-details">
                <?php foreach ($response_data as $data): ?>
                    <div class="response-item">
                        <h3><?php echo htmlspecialchars($data['field_label']); ?></h3>
                        
                        <?php if ($data['field_type'] === 'file'): ?>
                            <?php if ($data['field_value']): ?>
                                <?php 
                                $filepath = "../uploads/survey_{$response['survey_id']}/{$data['field_value']}";
                                if (file_exists($filepath)): 
                                    $fileinfo = pathinfo($filepath);
                                    if (in_array(strtolower($fileinfo['extension']), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <img src="<?php echo $filepath; ?>" class="file-preview" alt="Uploaded file">
                                    <?php else: ?>
                                        <a href="<?php echo $filepath; ?>" target="_blank">Download File</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>File not found</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>No file uploaded</p>
                            <?php endif; ?>
                        
                        <?php elseif (in_array($data['field_type'], ['radio', 'checkbox', 'select'])): ?>
                            <p><?php echo htmlspecialchars($data['field_value']); ?></p>
                        
                        <?php elseif ($data['field_type'] === 'rating'): ?>
                            <div class="rating-display">
                                <?php 
                                $rating = intval($data['field_value']);
                                for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-star <?php echo $i <= $rating ? 'active' : ''; ?>">★</span>
                                <?php endfor; ?>
                                <span class="rating-value">(<?php echo $rating; ?>/5)</span>
                            </div>
                        
                        <?php else: ?>
                            <p><?php echo nl2br(htmlspecialchars($data['field_value'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <a href="results.php?survey_id=<?php echo $response['survey_id']; ?>" class="btn">Back to Results</a>
            </div>
        </div>
    </div>
</body>
</html>