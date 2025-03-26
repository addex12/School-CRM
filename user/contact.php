<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Support</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/contact.js" defer></script>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <h2>Contact Support</h2>
            
            <form id="contact-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment:</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </form>

            <div id="ticket-history">
                <h3>Your Support Tickets</h3>
                <div id="tickets-container">
                    <!-- Tickets will be dynamically loaded here -->
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>