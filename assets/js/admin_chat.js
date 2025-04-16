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
        fetch('../api/chat_history.php')
            .then(response => response.json())
            .then(messages => {
                chatMessages.innerHTML = '';
                messages.forEach(message => {
                    const p = document.createElement('p');
                    p.textContent = `${message.sender}: ${message.message}`;
                    chatMessages.appendChild(p);
                });
            })
            .catch(error => console.error('Error fetching chat messages:', error));
    }

    // Handle chat form submission
    chatForm.addEventListener('submit', event => {
        event.preventDefault();
        const message = chatInput.value.trim();
        if (message) {
            // Send message via AJAX
            fetch('../api/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
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
    });
});