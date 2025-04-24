document.addEventListener('DOMContentLoaded', function() {
    const userList = document.getElementById('user-list');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const messageInput = document.getElementById('message-input');

    let selectedUserId = window.messagesConfig.selectedUserId;
    let currentUser = window.messagesConfig.currentUser;

    function loadMessages(userId) {
        if (!userId) return;
        chatMessages.innerHTML = '<p>Loading messages...</p>';
        fetch(`../api/get_messages.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                chatMessages.innerHTML = '';
                if (data.success) {
                    if (data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `chat-message ${msg.is_own ? 'own' : 'other'}`;
                            messageDiv.innerHTML = `
                                <strong>${msg.sender}</strong>
                                <p class="msg-text" data-msg-id="${msg.id}">${msg.message}</p>
                                <span class="msg-time">${msg.sent_at}</span>
                            `;
                            chatMessages.appendChild(messageDiv);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        markAsRead(userId);
                    } else {
                        chatMessages.innerHTML = '<p>No messages yet. Start the conversation!</p>';
                    }
                } else {
                    chatMessages.innerHTML = `<p>Error loading messages: ${data.error}</p>`;
                }
            })
            .catch(error => {
                chatMessages.innerHTML = '<p>Error loading messages</p>';
            });
    }

    function markAsRead(senderId) {
        if (!senderId) return;
        fetch(`../api/mark_read.php?user_id=${senderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector(`li[data-user-id="${senderId}"] .unread-badge`);
                    if (badge) badge.remove();
                }
            });
    }

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (!message || !selectedUserId) return;
        const formData = new FormData();
        formData.append('receiver_id', selectedUserId);
        formData.append('message', message);
        fetch('../api/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadMessages(selectedUserId);
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Failed to send message');
        });
    });

    userList.addEventListener('click', function(e) {
        const li = e.target.closest('li[data-user-id]');
        if (!li) return;
        selectedUserId = li.getAttribute('data-user-id');
        receiverInput.value = selectedUserId;
        document.querySelectorAll('.contact-item').forEach(item => {
            item.classList.remove('selected');
        });
        li.classList.add('selected');
        chatHeader.innerHTML = `<h3>Chat with ${li.textContent.trim()}</h3>`;
        messageForm.style.display = 'block';
        loadMessages(selectedUserId);
    });

    if (selectedUserId) {
        const li = document.querySelector(`li[data-user-id="${selectedUserId}"]`);
        if (li) {
            li.classList.add('selected');
            receiverInput.value = selectedUserId;
            chatHeader.innerHTML = `<h3>Chat with ${li.textContent.trim()}</h3>`;
            messageForm.style.display = 'block';
            loadMessages(selectedUserId);
        }
    }

    // No polling or setInterval here!

    // The following endpoints are used for message CRUD via AJAX:
    //   - ../api/edit_message.php
    //   - ../api/delete_message.php

    chatMessages.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.preventDefault();
            const msgId = editBtn.getAttribute('data-msg-id');
            const oldText = decodeURIComponent(editBtn.getAttribute('data-msg-text'));
            const newText = prompt('Edit your message:', oldText);
            if (newText !== null && newText.trim() !== '' && newText !== oldText) {
                fetch('../api/edit_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: msgId, message: newText })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadMessages(selectedUserId);
                    } else {
                        alert('Failed to edit message: ' + (data.error || 'Unknown error'));
                    }
                });
            }
            return;
        }
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            const msgId = deleteBtn.getAttribute('data-msg-id');
            if (confirm('Are you sure you want to delete this message?')) {
                fetch('../api/delete_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: msgId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadMessages(selectedUserId);
                    } else {
                        alert('Failed to delete message: ' + (data.error || 'Unknown error'));
                    }
                });
            }
            return;
        }
    });
});