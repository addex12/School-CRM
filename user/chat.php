<?php
require_once __DIR__.'/../includes/auth.php';
require_login();

$title = 'Chat';
include __DIR__.'/includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Messages</h2>
    <div id="chat-container"></div>
</div>

<script src="/assets/js/chat.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const chat = new ChatInterface();
    });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
