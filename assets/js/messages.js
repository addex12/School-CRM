document.addEventListener('DOMContentLoaded', function () {
    const userList = document.getElementById('user-list');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const messageInput = document.getElementById('message-input');
    let selectedUserId = null;

    // Select a user/admin to chat with
    // Fetch and render contacts
    function loadContacts() {
        fetch('../api/get_contacts.php')
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => { throw new Error(`Contacts fetch failed: ${res.status} ${text}`); });
                }
                return res.json();
            })
            .then(data => {
                if (!data.success) return;
                userList.innerHTML = '';
                data.contacts.forEach(contact => {
                    const li = document.createElement('li');
                    li.setAttribute('data-user-id', contact.id);
                    li.innerHTML = `${contact.username} ${contact.unread > 0 ? `<span class='unread-badge'>${contact.unread}</span>` : ''}`;
                    userList.appendChild(li);
                });
            })
            .catch(err => {
                alert('Error loading contacts: ' + err.message);
                console.error(err);
            });
    }
    loadContacts();

    userList && userList.addEventListener('click', function (e) {
        const li = e.target.closest('li[data-user-id]');
        if (!li) return;
        selectedUserId = li.getAttribute('data-user-id');
        receiverInput.value = selectedUserId;
        chatHeader.textContent = li.textContent.trim();
        messageForm.style.display = '';
        loadMessages();
        // Mark as read after loading messages
        fetch(`../api/mark_read.php?user_id=${selectedUserId}`)
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => { throw new Error(`Mark read failed: ${res.status} ${text}`); });
                }
                loadContacts();
            })
            .catch(err => {
                alert('Error marking as read: ' + err.message);
                console.error(err);
            });
    });

    // Send a message
    messageForm && messageForm.addEventListener('submit', function (e) {
        console.log('Submit handler triggered'); // Debug
        e.preventDefault();
        console.log('receiverInput.value:', receiverInput.value); // Debug
        console.log('messageInput.value:', messageInput.value); // Debug
        if (!messageInput.value.trim()) {
            alert('Message cannot be empty.');
            return;
        }
        if (!receiverInput.value) {
            alert('Please select a contact before sending a message.');
            return;
        }
        // Debug: log what is being sent
        console.log('Sending:', {
            receiver_id: receiverInput.value,
            message: messageInput.value
        });
        fetch('../api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${encodeURIComponent(receiverInput.value)}&message=${encodeURIComponent(messageInput.value)}`,
            credentials: 'include' 
        })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => { throw new Error(`Send failed: ${res.status} ${text}`); });
            }
            return res.json();
        })
        .then(data => {
            console.log('Send response:', data);
            if(data.success) {
                messageInput.value = '';
                loadMessages();
            } else {
                alert((data.error || 'Failed to send message') + (data.debug ? '\nDebug: ' + JSON.stringify(data.debug) : ''));
            }
        })
        .catch(err => {
            alert('Send error: ' + err.message);
            console.error('Send error:', err);
        });
    });

    // Disable send button if no contact selected
    function updateSendButtonState() {
        const btn = messageForm.querySelector('button[type="submit"]');
        btn.disabled = !receiverInput.value;
    }
    userList && userList.addEventListener('click', function () {
        setTimeout(updateSendButtonState, 10);
    });
    updateSendButtonState();

    // Highlight selected contact and add hover effect
    userList && userList.addEventListener('click', function (e) {
        const li = e.target.closest('li[data-user-id]');
        if (!li) return;
        // Remove selected from all
        userList.querySelectorAll('li').forEach(el => el.classList.remove('selected-contact'));
        li.classList.add('selected-contact');
    });

    // Add hover effect via JS if not present in CSS
    userList && userList.addEventListener('mouseover', function (e) {
        const li = e.target.closest('li[data-user-id]');
        if (li) li.classList.add('hover-contact');
    });
    userList && userList.addEventListener('mouseout', function (e) {
        const li = e.target.closest('li[data-user-id]');
        if (li) li.classList.remove('hover-contact');
    });

    // Load messages
    function loadMessages() {
        if (!selectedUserId) return;
        fetch(`../api/get_messages.php?user_id=${selectedUserId}`)
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => { throw new Error(`Messages fetch failed: ${res.status} ${text}`); });
                }
                return res.json();
            })
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
            })
            .catch(err => {
                chatMessages.innerHTML = `<div class="error-message">Error loading messages: ${err.message}</div>`;
                console.error(err);
            });
    }

    // Optionally, poll for new messages every 10 seconds
    setInterval(() => {
        if (selectedUserId) loadMessages();
    }, 10000);
});
