document.addEventListener('DOMContentLoaded', function() {
    // Status change confirmation
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            if (this.value === 'resolved') {
                if (!confirm('Are you sure you want to mark this ticket as resolved?')) {
                    this.value = '<?= $ticket['status'] ?>'; // Revert to original value
                }
            }
        });
    }

    // Priority color indicator
    const prioritySelect = document.getElementById('priority_id');
    if (prioritySelect) {
        // Set initial color
        updatePriorityColor(prioritySelect);
        
        // Update on change
        prioritySelect.addEventListener('change', function() {
            updatePriorityColor(this);
        });
    }

    function updatePriorityColor(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        selectElement.style.color = selectedOption.style.color;
        selectElement.style.borderLeft = `3px solid ${selectedOption.style.color}`;
    }

    // Form submission handling
    const ticketForm = document.querySelector('.ticket-form');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(e) {
            // Additional validation can be added here
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (!subject || !message) {
                e.preventDefault();
                alert('Subject and message are required.');
                return false;
            }
            
            return true;
        });
    }

    // Character counter for reply message
    const replyMessage = document.getElementById('reply_message');
    if (replyMessage) {
        const charCounter = document.createElement('div');
        charCounter.className = 'char-counter';
        charCounter.textContent = '0/1000';
        replyMessage.parentNode.appendChild(charCounter);

        replyMessage.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength}/1000`;
            
            if (currentLength > 1000) {
                charCounter.style.color = 'red';
            } else {
                charCounter.style.color = '#777';
            }
        });
    }
});