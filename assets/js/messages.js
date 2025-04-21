document.addEventListener('DOMContentLoaded', function() {
    const userList = document.getElementById('user-list');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const receiverIdInput = document.getElementById('receiver_id');
    
    // Handle user selection
    userList.addEventListener('click', function(e) {
        if (e.target.tagName === 'LI') {
            const userId = e.target.dataset.userId;
            const username = e.target.textContent.trim();
            
            // Update UI
            receiverIdInput.value = userId;
            chatHeader.innerHTML = `<h3>Messaging: ${username}</h3>`;
            messageForm.style.display = 'block';
            
            // Load messages (you'll need to implement this)
            loadMessages(userId);
        }
    });
    
    // Handle form submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'send_message');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            messageInput.value = '';
            // Optionally reload messages
        })
        .catch(error => {
            alert('Error: ' + error.message);
            console.error(error);
        });
    });
    
    function loadMessages(userId) {
        // Implement message loading functionality
        chatMessages.innerHTML = '<p>Loading messages...</p>';
        // You'll need another endpoint to fetch messages
    }
});