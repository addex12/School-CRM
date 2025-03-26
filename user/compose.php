<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$data['recipient']]);
    $receiver = $stmtUser->fetch();

    if ($receiver) {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, content, is_email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $receiver['id'],
            $data['subject'],
            $data['content'],
            $data['is_email'] ? 1 : 0
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Recipient not found']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compose Message</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="compose-container">
        <h1>Compose New Message</h1>
        
        <form id="composeForm">
            <div class="form-group">
                <label>Recipient Username:</label>
                <input type="text" name="recipient" required>
            </div>
            
            <div class="form-group">
                <label>Subject:</label>
                <input type="text" name="subject" required>
            </div>
            
            <div class="form-group">
                <label>Message:</label>
                <textarea name="content" rows="8" required></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_email" checked>
                    Send as Email
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>

    <script>
        document.getElementById('composeForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Message sent successfully!');
                    window.location.href = 'inbox.php';
                } else {
                    alert(result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>