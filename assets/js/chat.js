// Global variables
let chatSocket = null;
let activeThreadId = null;
let currentUserId = null;

// Initialize chat functionality
function initChat() {
    // Tab switching
    document.querySelectorAll('.chat-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });

    // Online user click handler
    document.querySelectorAll('.online-user').forEach(user => {
        user.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-start-chat')) {
                return; // Let the button handle its own click
            }
            
            const userId = this.getAttribute('data-user-id');
            startNewChat(userId);
        });
    });

    // Start chat button handler
    document.querySelectorAll('.btn-start-chat').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const userId = this.closest('.online-user').getAttribute('data-user-id');
            startNewChat(userId);
        });
    });

    // Thread click handler
    document.querySelectorAll('.chat-thread').forEach(thread => {
        thread.addEventListener('click', function() {
            const threadId = this.getAttribute('data-thread-id');
            loadThread(threadId);
        });
    });

    // Chat form submission
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    // Notes modal
    const notesModal = document.getElementById('notes-modal');
    if (notesModal) {
        document.getElementById('btn-add-notes')?.addEventListener('click', () => {
            notesModal.classList.add('active');
        });

        notesModal.querySelector('.modal-close').addEventListener('click', () => {
            notesModal.classList.remove('active');
        });

        document.getElementById('notes-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            saveNotes();
        });
    }

    // Quick responses modal
    const responsesModal = document.getElementById('quick-responses-modal');
    if (responsesModal) {
        document.querySelector('.btn-input-action[title="Insert Quick Response"]')?.addEventListener('click', () => {
            loadQuickResponses();
            responsesModal.classList.add('active');
        });

        responsesModal.querySelector('.modal-close').addEventListener('click', () => {
            responsesModal.classList.remove('active');
        });
    }

    // Close chat button
    document.getElementById('btn-close-chat')?.addEventListener('click', closeChat);

    // Auto-resize textarea
    const messageInput = document.querySelector('.message-input textarea');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
}

// Switch between tabs
function switchTab(tabName) {
    // Update active tab button
    document.querySelectorAll('.chat-tab').forEach(tab => {
        tab.classList.toggle('active', tab.getAttribute('data-tab') === tabName);
    });

    // Update active chat list
    document.querySelectorAll('.chat-list').forEach(list => {
        list.classList.toggle('active', list.id === `${tabName}-list`);
    });

    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

// Load a chat thread
function loadThread(threadId) {
    const url = new URL(window.location);
    url.searchParams.set('thread_id', threadId);
    window.location.href = url.toString();
}

// Start a new chat with user
function startNewChat(userId) {
    fetch('chat_actions.php?action=start_chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadThread(data.thread_id);
        } else {
            alert(data.message || 'Failed to start chat');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while starting the chat');
    });
}

// Load chat messages for a thread
function loadChatMessages(threadId) {
    const messagesContainer = document.getElementById('chat-messages');
    if (!messagesContainer) return;

    messagesContainer.innerHTML = '<div class="loading-messages"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>';

    fetch(`chat_actions.php?action=get_messages&thread_id=${threadId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayMessages(data.messages);
        } else {
            messagesContainer.innerHTML = '<div class="no-messages">Failed to load messages</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messagesContainer.innerHTML = '<div class="no-messages">Error loading messages</div>';
    });
}

// Display messages in the chat
function displayMessages(messages) {
    const messagesContainer = document.getElementById('chat-messages');
    if (!messagesContainer) return;

    messagesContainer.innerHTML = '';

    if (messages.length === 0) {
        messagesContainer.innerHTML = '<div class="no-messages">No messages yet</div>';
        return;
    }

    messages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${message.is_admin ? 'admin' : 'user'}`;
        
        messageDiv.innerHTML = `
            <div>${message.message}</div>
            <small class="message-time">${formatMessageTime(message.created_at)}</small>
        `;
        
        messagesContainer.appendChild(messageDiv);
    });

    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Format message time
function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Send a new message
function sendMessage() {
    const form = document.getElementById('chat-form');
    if (!form) return;

    const formData = new FormData(form);
    const messageInput = form.querySelector('textarea');

    fetch('chat_actions.php?action=send_message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to UI immediately
            const messagesContainer = document.getElementById('chat-messages');
            if (messagesContainer) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message message-admin';
                messageDiv.innerHTML = `
                    <div>${formData.get('message')}</div>
                    <small class="message-time">Just now</small>
                `;
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            // Clear input
            messageInput.value = '';
            messageInput.style.height = 'auto';
        } else {
            alert(data.message || 'Failed to send message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the message');
    });
}

// Save admin notes
function saveNotes() {
    const form = document.getElementById('notes-form');
    if (!form) return;

    const formData = new FormData(form);

    fetch('chat_actions.php?action=save_notes', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('notes-modal').classList.remove('active');
        } else {
            alert(data.message || 'Failed to save notes');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving notes');
    });
}

// Close chat thread
function closeChat() {
    if (!confirm('Are you sure you want to close this chat?')) return;

    const threadId = document.querySelector('input[name="thread_id"]')?.value;
    if (!threadId) return;

    fetch('chat_actions.php?action=close_chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `thread_id=${threadId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'chat_mgt.php?tab=inbox';
        } else {
            alert(data.message || 'Failed to close chat');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while closing the chat');
    });
}

// Load quick responses
function loadQuickResponses() {
    const container = document.querySelector('.quick-responses-list');
    if (!container) return;

    container.innerHTML = '<div class="loading">Loading quick responses...</div>';

    fetch('chat_actions.php?action=get_quick_responses')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = '';
            data.responses.forEach(response => {
                const item = document.createElement('div');
                item.className = 'quick-response-item';
                item.textContent = response.text;
                item.addEventListener('click', () => {
                    insertQuickResponse(response.text);
                    document.getElementById('quick-responses-modal').classList.remove('active');
                });
                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<div class="error">Failed to load quick responses</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div class="error">Error loading quick responses</div>';
    });
}

// Insert quick response into message input
function insertQuickResponse(text) {
    const messageInput = document.querySelector('.message-input textarea');
    if (messageInput) {
        messageInput.value = text;
        messageInput.focus();
    }
}

// Initialize WebSocket connection for real-time updates
function initChatSocket(threadId, userId) {
    // Determine WebSocket protocol (ws:// or wss://)
    const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
    const host = window.location.host;
    const path = '/chat_socket.php'; // You'll need to create this file
    
    try {
        chatSocket = new WebSocket(`${protocol}${host}${path}?thread_id=${threadId}&user_id=${userId}`);
        
        chatSocket.onopen = function(e) {
            console.log('WebSocket connection established');
        };
        
        chatSocket.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.type === 'new_message') {
                // Add new message to UI
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message message-${data.message.is_admin ? 'admin' : 'user'}`;
                    messageDiv.innerHTML = `
                        <div>${data.message.message}</div>
                        <small class="message-time">${formatMessageTime(data.message.created_at)}</small>
                    `;
                    messagesContainer.appendChild(messageDiv);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
                
                // Update unread count in sidebar if needed
                if (!data.message.is_admin) {
                    updateUnreadCounts();
                }
            } else if (data.type === 'user_typing') {
                // Show typing indicator
                // Implement as needed
            } else if (data.type === 'thread_closed') {
                // Handle thread closure
                alert('This chat has been closed');
                window.location.href = 'chat_mgt.php?tab=inbox';
            }
        };
        
        chatSocket.onclose = function(event) {
            if (event.wasClean) {
                console.log(`WebSocket connection closed cleanly, code=${event.code}, reason=${event.reason}`);
            } else {
                console.log('WebSocket connection died');
                // Attempt to reconnect after a delay
                setTimeout(() => initChatSocket(threadId, userId), 5000);
            }
        };
        
        chatSocket.onerror = function(error) {
            console.log(`WebSocket error: ${error.message}`);
        };
    } catch (error) {
        console.error('WebSocket initialization error:', error);
    }
}

// Update unread counts in sidebar
function updateUnreadCounts() {
    fetch('chat_actions.php?action=get_unread_counts')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update inbox tab badge
            const inboxBadge = document.querySelector('.chat-tab[data-tab="inbox"] .unread-badge');
            if (inboxBadge) {
                if (data.total_unread > 0) {
                    inboxBadge.textContent = data.total_unread;
                    inboxBadge.style.display = 'flex';
                } else {
                    inboxBadge.style.display = 'none';
                }
            }
            
            // Update individual thread unread counts
            data.threads.forEach(thread => {
                const threadElement = document.querySelector(`.chat-thread[data-thread-id="${thread.thread_id}"]`);
                if (threadElement) {
                    const unreadBadge = threadElement.querySelector('.unread-count');
                    if (unreadBadge) {
                        if (thread.unread_count > 0) {
                            unreadBadge.textContent = thread.unread_count;
                            threadElement.classList.add('unread');
                        } else {
                            threadElement.classList.remove('unread');
                        }
                    }
                }
            });
        }
    })
    .catch(error => {
        console.error('Error updating unread counts:', error);
    });
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', initChat);