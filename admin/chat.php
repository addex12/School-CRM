<?php
require_once __DIR__.'../includes/auth.php';
require_admin();

$title = 'Admin Chat';
include __DIR__.'/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__.'/includes/admin_sidebar.php' ?>
        
        <main class="col-md-9 ml-sm-auto col-lg-10 px-4">
            <h2 class="mt-3">Messaging System</h2>
            <div id="admin-chat-container"></div>
        </main>
    </div>
</div>

<script src="/assets/js/chat.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const chat = new ChatInterface();
    });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
