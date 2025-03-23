<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Real-time Chat</title>
    <script>
        let ws = new WebSocket('ws://localhost:8080');
        ws.onmessage = function(event) {
            let messages = document.getElementById('messages');
            let message = document.createElement('div');
            message.textContent = event.data;
            messages.appendChild(message);
        };

        function sendMessage() {
            let input = document.getElementById('message');
            ws.send(input.value);
            input.value = '';
        }
    </script>
</head>
<body>
    <h1>Real-time Chat</h1>
    <div id="messages"></div>
    <input type="text" id="message">
    <button onclick="sendMessage()">Send</button>
</body>
</html>
