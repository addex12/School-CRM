// Add this JavaScript at the end of the file
<script>
// Initialize WebSocket connection
const adminId = <?= $_SESSION['user_id'] ?>;
const ws = new WebSocket('ws://your-domain:8080?user_id=' + adminId);

// Handle incoming messages
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    
    if (data.type === 'message') {
        if (data.thread_id === activeThreadId) {
            appendMessage(data);
        }
        updateUnreadCounts();
    }
    
    if (data.type === 'status_update') {
        updateOnlineUsers();
    }
};

// Send message function
function sendAdminMessage(message, threadId) {
    const msgData = {
        type: 'message',
        thread_id: threadId,
        user_id: adminId,
        message: message,
        is_admin: true,
        recipient_id: data.user_id
    };
    ws.send(JSON.stringify(msgData));
}

// Update online users list
function updateOnlineUsers() {
    fetch('chat_actions.php?action=get_online_users')
        .then(response => response.json())
        .then(data => {
            // Update online users list UI
        });
}

// Update unread counts
function updateUnreadCounts() {
    fetch('chat_actions.php?action=get_unread_counts')
        .then(response => response.json())
        .then(data => {
            // Update UI badges
        });
}
</script>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/chat.js"></script>
    <script>
        // Initialize chat with current thread
        <?php if ($activeThread): ?>
            const activeThreadId = <?= $activeThread['id'] ?>;
            const currentUserId = <?= $_SESSION['user_id'] ?>;
            
            // Load messages for active thread
            loadChatMessages(activeThreadId);
            
            // Initialize WebSocket for real-time updates
            initChatSocket(activeThreadId, currentUserId);
        <?php endif; ?>
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>