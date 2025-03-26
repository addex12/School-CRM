<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'];
    $email = $data['email'];
    $message = $data['message'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['error' => 'All fields are required.']);
        exit();
    }

    // Save the message to the database (or send an email)
    require_once '../includes/config.php';
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $email, $message])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to send your message. Please try again later.']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/user_header.php'; ?>

    <div class="contact-container">
        <h1>Contact Us</h1>
        <form id="contactForm">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
        <p id="responseMessage" style="display: none;"></p>
    </div>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                const responseMessage = document.getElementById('responseMessage');
                if (result.success) {
                    responseMessage.style.color = 'green';
                    responseMessage.textContent = 'Your message has been sent successfully!';
                } else {
                    responseMessage.style.color = 'red';
                    responseMessage.textContent = result.error || 'An error occurred. Please try again.';
                }
                responseMessage.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                const responseMessage = document.getElementById('responseMessage');
                responseMessage.style.color = 'red';
                responseMessage.textContent = 'An error occurred. Please try again.';
                responseMessage.style.display = 'block';
            });
        });
    </script>
</body>
</html>