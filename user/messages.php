<script>
document.addEventListener('DOMContentLoaded', function() {
    const MESSAGES_API = '../api/user/get_messages.php';
    const SEND_API = '../api/user/send_message.php';
    const MARK_READ_API = '../api/user/mark_read.php';
    
    const contactItems = document.querySelectorAll('.contact-item');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const messageInput = document.getElementById('message-input');
    const currentUserId = <?= $current_user_id ?>;

    let selectedUserId = null;
    let isLoading = false;

    // Load messages for selected user
    async function loadMessages(userId) {
        if (!userId || isLoading) return;
        
        isLoading = true;
        chatMessages.innerHTML = '<div class="no-messages">Loading messages...</div>';
        
        try {
            const response = await fetch(`${MESSAGES_API}?user_id=${userId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load messages');
            }
            
            chatMessages.innerHTML = '';
            
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
                    messageDiv.innerHTML = `
                        <div class="message-content">${msg.message}</div>
                        <div class="message-meta">
                            <span class="message-sender">${msg.sender}</span>
                            <span class="message-time">${msg.sent_at}</span>
                        </div>
                    `;
                    chatMessages.appendChild(messageDiv);
                });
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Mark messages as read
                await markAsRead(userId);
            } else {
                chatMessages.innerHTML = '<div class="no-messages">No messages yet. Start the conversation!</div>';
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            chatMessages.innerHTML = `<div class="no-messages">Error: ${error.message}</div>`;
        } finally {
            isLoading = false;
        }
    }

    // Mark messages as read
    async function markAsRead(senderId) {
        try {
            await fetch(`${MARK_READ_API}?user_id=${senderId}`);
            // Update unread count in UI
            const badge = document.querySelector(`.contact-item[data-user-id="${senderId}"] .unread-badge`);
            if (badge) badge.remove();
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    // Handle contact selection
    contactItems.forEach(item => {
        item.addEventListener('click', function() {
            selectedUserId = this.getAttribute('data-user-id');
            receiverInput.value = selectedUserId;
            
            // Update UI
            contactItems.forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            
            // Update header
            const contactName = this.querySelector('.contact-name').textContent;
            chatHeader.innerHTML = `<h3>${contactName}</h3>`;
            
            // Show message form and load messages
            messageForm.style.display = 'flex';
            loadMessages(selectedUserId);
        });
    });

    // Handle form submission
    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message || !selectedUserId) return;
        
        try {
            const response = await fetch(SEND_API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${encodeURIComponent(selectedUserId)}&message=${encodeURIComponent(message)}`
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to send message');
            }
            
            messageInput.value = '';
            await loadMessages(selectedUserId);
        } catch (error) {
            console.error('Error sending message:', error);
            alert(`Failed to send message: ${error.message}`);
        }
    });

    // Initialize with first contact if available
    if (contactItems.length > 0) {
        contactItems[0].click();
    }

    // Poll for new messages every 3 seconds
    setInterval(() => {
        if (selectedUserId) {
            loadMessages(selectedUserId);
        }
    }, 3000);
});
</script>