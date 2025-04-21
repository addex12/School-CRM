messageForm && messageForm.addEventListener('submit', function (e) {
    e.preventDefault();
    console.log('Submit handler triggered');
    
    if (!messageInput.value.trim()) {
        alert('Message cannot be empty.');
        return;
    }
    
    if (!receiverInput.value) {
        alert('Please select a contact before sending a message.');
        return;
    }
    
    // Create FormData object for proper formatting
    const formData = new FormData();
    formData.append('receiver_id', receiverInput.value);
    formData.append('message', messageInput.value);
    
    fetch('../api/send_message.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(res => {
        if (!res.ok) {
            return res.text().then(text => { 
                throw new Error(`Send failed: ${res.status} ${text}`); 
            });
        }
        return res.json();
    })
    .then(data => {
        console.log('Send response:', data);
        if(data.success) {
            messageInput.value = '';
            loadMessages();
        } else {
            alert((data.error || 'Failed to send message') + 
                 (data.debug ? '\nDebug: ' + JSON.stringify(data.debug) : ''));
        }
    })
    .catch(err => {
        alert('Send error: ' + err.message);
        console.error('Send error:', err);
    });
});