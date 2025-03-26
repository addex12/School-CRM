document.addEventListener('DOMContentLoaded', function() {
    const supportForm = document.getElementById('support-form');
    const attachmentInput = document.getElementById('attachment');
    const maxFileSize = 5 * 1024 * 1024; // 5MB
    
    if (supportForm) {
        supportForm.addEventListener('submit', function(e) {
            // Validate file size if file is selected
            if (attachmentInput.files.length > 0) {
                const file = attachmentInput.files[0];
                
                if (file.size > maxFileSize) {
                    e.preventDefault();
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    return;
                }
                
                // Validate file type
                const validTypes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'text/plain',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                
                if (!validTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Invalid file type. Please upload PDF, JPG, PNG, TXT, or DOC/DOCX files only.');
                    return;
                }
            }
            
            // Validate required fields
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (!subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show loading state
            const submitBtn = supportForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });
    }
    
    // Preview selected file name
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function() {
            const fileHint = document.querySelector('.file-hint');
            if (this.files.length > 0) {
                fileHint.textContent = 'Selected: ' + this.files[0].name;
            } else {
                fileHint.textContent = 'Max file size: 5MB (PDF, JPG, PNG, TXT, DOC/DOCX)';
            }
        });
    }
    
    // Clear success message after 5 seconds
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 500);
        }, 5000);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('contact-form');
    const ticketsContainer = document.getElementById('tickets-container');

    // Fetch and display ticket history
    const loadTickets = async () => {
        try {
            const response = await fetch('/user/api/tickets.php');
            const tickets = await response.json();

            ticketsContainer.innerHTML = '';
            if (tickets.length === 0) {
                ticketsContainer.innerHTML = '<p>You have no support tickets.</p>';
                return;
            }

            tickets.forEach(ticket => {
                const ticketDiv = document.createElement('div');
                ticketDiv.classList.add('ticket');
                ticketDiv.innerHTML = `
                    <div class="ticket-header">
                        <span class="ticket-number">${ticket.ticket_number}</span>
                        <span class="priority ${ticket.priority}">${ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}</span>
                    </div>
                    <h4>${ticket.subject}</h4>
                    <p>${ticket.message}</p>
                    ${ticket.attachment ? `<div class="attachment"><a href="/uploads/${ticket.attachment}" target="_blank"><i class="fas fa-paperclip"></i> Attachment</a></div>` : ''}
                    <small>Created: ${new Date(ticket.created_at).toLocaleString()}</small>
                `;
                ticketsContainer.appendChild(ticketDiv);
            });
        } catch (error) {
            console.error('Error loading tickets:', error);
            ticketsContainer.innerHTML = '<p>Error loading tickets. Please try again later.</p>';
        }
    };

    // Handle form submission
    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(contactForm);
        try {
            const response = await fetch('/user/api/contact.php', {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                contactForm.reset();
                loadTickets();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error submitting ticket:', error);
            alert('An error occurred while submitting your ticket. Please try again.');
        }
    });

    // Load tickets on page load
    loadTickets();
});