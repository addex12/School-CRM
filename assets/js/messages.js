document.addEventListener('DOMContentLoaded', function () {
    const userList = document.getElementById('user-list');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const messageInput = document.getElementById('message-input');
    let selectedUserId = null;

    // Select a user/admin to chat with
    userList && userList.addEventListener('click', function (e) {
        const li = e.target.closest('li[data-user-id]');
        if (!li) return;
        selectedUserId = li.getAttribute('data-user-id');
        receiverInput.value = selectedUserId;
        chatHeader.textContent = li.textContent.trim();
        messageForm.style.display = '';
        loadMessages();
    });

    // Send a message
    messageForm && messageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!messageInput.value.trim()) return;
        fetch('../api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${encodeURIComponent(receiverInput.value)}&message=${encodeURIComponent(messageInput.value)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                messageInput.value = '';
                loadMessages();
            } else {
                alert(data.error || 'Failed to send message');
            }
        });
    });

    // Load messages
    function loadMessages() {
        if (!selectedUserId) return;
        fetch(`../api/get_messages.php?user_id=${selectedUserId}`)
            .then(res => res.json())
            .then(data => {
                chatMessages.innerHTML = '';
                if(data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'chat-message' + (msg.is_own ? ' own' : '');
                        div.innerHTML = `<span class="msg-user">${msg.sender}</span>: <span class="msg-text">${msg.message}</span><br><span class="msg-time">${msg.sent_at}</span>`;
                        chatMessages.appendChild(div);
                    });
                } else {
                    chatMessages.innerHTML = '<div class="no-messages">No messages yet.</div>';
                }
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
    }

    // Optionally, poll for new messages every 10 seconds
    setInterval(() => {
        if (selectedUserId) loadMessages();
    }, 10000);
});
