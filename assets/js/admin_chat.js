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

    // Initial fetches
    fetchOnlineUsers();
    fetchChatMessages();

    // Refresh online users every 10 seconds
    setInterval(fetchOnlineUsers, 10000);
});