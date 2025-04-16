document.addEventListener('DOMContentLoaded', () => {
    const onlineUsersList = document.getElementById('onlineUsers');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');

    // Fetch online users
    function fetchOnlineUsers() {
        fetch('../user/online_users.php')
            .then(response => response.json())
            .then(users => {
                onlineUsersList.innerHTML = '';
                users.forEach(user => {
                    const li = document.createElement('li');
                    li.textContent = user.username;
                    onlineUsersList.appendChild(li);
                });
            })
            .catch(error => console.error('Error fetching online users:', error));
    }

    // Fetch chat messages (placeholder for WebSocket integration)
    function fetchChatMessages() {
        // Placeholder: Replace with WebSocket or AJAX logic
        chatMessages.innerHTML = '<p>Chat messages will appear here...</p>';
    }

    // Handle chat form submission
    chatForm.addEventListener('submit', event => {
        event.preventDefault();
        const message = chatInput.value.trim();
        if (message) {
            // Placeholder: Send message via WebSocket or AJAX
            const p = document.createElement('p');
            p.textContent = `Admin: ${message}`;
            chatMessages.appendChild(p);
            chatInput.value = '';
        }
    });

    // Update admin_chat.js to handle sending messages and fetching chat history
    function sendMessage(receiverId, message) {
        fetch('../api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receiverId, message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const p = document.createElement('p');
                p.textContent = `Admin: ${message}`;
                chatMessages.appendChild(p);
                chatInput.value = '';
            } else {
                console.error('Error sending message:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Fetch chat history
    function fetchChatHistory(receiverId) {
        fetch(`../api/chat_history.php?receiverId=${receiverId}`)
            .then(response => response.json())
            .then(messages => {
                chatMessages.innerHTML = '';
                messages.forEach(msg => {
                    const p = document.createElement('p');
                    p.textContent = `${msg.sender}: ${msg.message}`;
                    chatMessages.appendChild(p);
                });
            })
            .catch(error => console.error('Error fetching chat history:', error));
    }

    // Initial fetches
    fetchOnlineUsers();
    fetchChatMessages();

    // Refresh online users every 10 seconds
    setInterval(fetchOnlineUsers, 10000);
});