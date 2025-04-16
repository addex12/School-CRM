class ChatInterface {
    constructor() {
        this.chatContainer = document.createElement('div');
        this.setupUI();
        this.initializeEventListeners();
    }

    setupUI() {
        this.chatContainer.innerHTML = `
            <div class="chat-window">
                <div class="chat-header">
                    <h3>Chat</h3>
                    <span class="close-chat">&times;</span>
                </div>
                <div class="chat-messages"></div>
                <div class="chat-input">
                    <textarea placeholder="Type your message..."></textarea>
                    <button class="send-btn">Send</button>
                </div>
            </div>
        `;
        document.body.appendChild(this.chatContainer);
    }

    initializeEventListeners() {
        this.setupSSEConnection();
        // Notification polling
        this.pollNotifications();
        document.querySelector('.send-btn').addEventListener('click', () => this.sendMessage());
    }

    setupSSEConnection() {
        this.eventSource = new EventSource('/api/chat_stream');
        this.eventSource.onmessage = (e) => {
            const messages = JSON.parse(e.data);
            this.updateMessages(messages.reverse());
        };
    }

    updateMessages(messages) {
        const container = document.querySelector('.chat-messages');
        container.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id === currentUserId ? 'sent' : 'received'}">
                <div class="meta">
                    <span class="user">${msg.username}</span>
                    <span class="time">${new Date(msg.created_at).toLocaleTimeString()}</span>
                </div>
                <div class="content">${msg.message}</div>
            </div>
        `).join('');
        container.scrollTop = container.scrollHeight;
    }

    async sendMessage() {
        const message = document.querySelector('textarea').value.trim();
        if(!message) return;

        try {
            const response = await fetch('/api/send_message', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ receiver_id: selectedUserId, message })
            });
            
            if(response.ok) document.querySelector('textarea').value = '';
        } catch(e) {
            console.error('Send message error:', e);
        }
    }

    async pollNotifications() {
        try {
            const res = await fetch('/api/check_notifications');
            const data = await res.json();
            if(data.success) this.updateBadge(data.unread);
        } catch(e) {
            console.error('Notification error:', e);
        }
        setTimeout(() => this.pollNotifications(), 10000);
    }

    updateBadge(count) {
        const badge = document.querySelector('.nav-link[href="#"] .badge');
        badge.textContent = count > 0 ? count : '';
    }
}

// Initialize chat when needed
window.ChatInterface = ChatInterface;
