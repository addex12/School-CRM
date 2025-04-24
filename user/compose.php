<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .erpnext-btn {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .erpnext-btn.btn-primary {
            background: #007bfc;
            color: #fff;
            border-color: #007bfc;
        }
        .erpnext-btn.btn-primary:hover {
            background: #0056b3;
            color: #fff;
        }
        .erpnext-input, .erpnext-textarea {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #f5f7fa;
            color: #36414c;
        }
        .erpnext-input:focus, .erpnext-textarea:focus {
            outline: none;
            border-color: #007bfc;
            background: #fff;
        }
        .erpnext-label {
            font-weight: 500;
            color: #36414c;
            margin-bottom: 4px;
            display: block;
        }
        .compose-container {
            max-width:700px;
            margin:40px auto;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="compose-container">
        <h1 style="color:#007bff;">
            <i class="fas fa-pen"></i> Compose New Message
        </h1>
        
        <form id="composeForm">
            <div class="form-group">
                <label class="erpnext-label">Recipient Username:</label>
                <input type="text" name="recipient" required class="erpnext-input">
            </div>
            
            <div class="form-group">
                <label class="erpnext-label">Subject:</label>
                <input type="text" name="subject" required class="erpnext-input">
            </div>
            
            <div class="form-group">
                <label class="erpnext-label">Message:</label>
                <textarea name="content" rows="8" required class="erpnext-textarea"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_email" checked>
                    Send as Email
                </label>
            </div>
            
            <button type="submit" class="erpnext-btn btn-primary">Send Message</button>
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

    <?php include '../includes/footer.php'; ?>
</body>
</html>