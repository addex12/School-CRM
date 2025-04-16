class ChatInterface {
    constructor() {
        this.chatContainer = document.createElement('div');
        this.setupUI();
        this.initializeEventListeners();
        this.pollMessages();
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
        // Implement WebSocket/SSE connection here
        // Notification polling
        this.pollNotifications();
        document.querySelector('.send-btn').addEventListener('click', () => this.sendMessage());
    }

    async pollMessages() {
        try {
            const response = await fetch(`/api/get_messages?user_id=${selectedUserId}`);
            const data = await response.json();
            if(data.success) this.updateMessages(data.messages);
        } catch(e) {
            console.error('Message polling error:', e);
        }
        setTimeout(() => this.pollMessages(), 3000);
    }

    updateMessages(messages) {
        // Render messages in UI
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
