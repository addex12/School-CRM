document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const feedbackModal = document.getElementById('feedback-modal');
    const statusModal = document.getElementById('status-modal');
    const modalCloseButtons = document.querySelectorAll('.close-modal, .close-button');
    
    // View feedback details
    document.querySelectorAll('.view-button').forEach(button => {
        button.addEventListener('click', function() {
            const feedbackId = this.getAttribute('data-id');
            fetchFeedbackDetails(feedbackId);
        });
    });
    
    // Change status
    document.querySelectorAll('.status-button').forEach(button => {
        button.addEventListener('click', function() {
            const feedbackId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            document.getElementById('status-feedback-id').value = feedbackId;
            
            // Set current status as checked
            document.querySelector(`input[name="new-status"][value="${currentStatus}"]`).checked = true;
            
            openModal(statusModal);
        });
    });
    
    // Save admin notes
    document.getElementById('save-notes').addEventListener('click', function() {
        const feedbackId = document.getElementById('detail-id').textContent;
        const notes = document.getElementById('detail-notes').value;
        
        // AJAX request to save notes
        fetch('update_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_notes&id=${feedbackId}&notes=${encodeURIComponent(notes)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Notes saved successfully!', 'success');
            } else {
                showAlert('Error saving notes: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('Network error: ' + error, 'error');
        });
    });
    
    // Update status
    document.getElementById('update-status').addEventListener('click', function() {
        const feedbackId = document.getElementById('status-feedback-id').value;
        const newStatus = document.querySelector('input[name="new-status"]:checked').value;
        
        // AJAX request to update status
        fetch('update_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_status&id=${feedbackId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the UI
                const row = document.querySelector(`tr[data-feedback-id="${feedbackId}"]`);
                if (row) {
                    const statusBadge = row.querySelector('.badge');
                    const statusButton = row.querySelector('.status-button');
                    
                    // Update badge
                    statusBadge.className = `badge badge-${newStatus.replace('_', '-')}`;
                    statusBadge.textContent = feedbackConfig.status_options[newStatus] || newStatus;
                    
                    // Update button data attribute
                    statusButton.setAttribute('data-status', newStatus);
                }
                
                showAlert('Status updated successfully!', 'success');
                closeModal(statusModal);
            } else {
                showAlert('Error updating status: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('Network error: ' + error, 'error');
        });
    });
    
    // Export buttons
    document.getElementById('export-csv').addEventListener('click', exportFeedback.bind(null, 'csv'));
    document.getElementById('export-pdf').addEventListener('click', exportFeedback.bind(null, 'pdf'));
    
    // Modal close handlers
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    });
    
    // Functions
    function fetchFeedbackDetails(feedbackId) {
        // AJAX request to get feedback details
        fetch(`get_feedback.php?id=${feedbackId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate modal
                    document.getElementById('detail-id').textContent = data.feedback.id;
                    document.getElementById('detail-user').textContent = data.feedback.username || 'Anonymous';
                    document.getElementById('detail-date').textContent = new Date(data.feedback.created_at).toLocaleString();
                    document.getElementById('detail-rating').innerHTML = getRatingStars(data.feedback.rating);
                    document.getElementById('detail-status').innerHTML = `<span class="badge badge-${data.feedback.status.replace('_', '-')}">${feedbackConfig.status_options[data.feedback.status] || data.feedback.status}</span>`;
                    document.getElementById('detail-subject').textContent = data.feedback.subject;
                    document.getElementById('detail-message').textContent = data.feedback.message;
                    document.getElementById('detail-notes').value = data.feedback.admin_notes || feedbackConfig.default_notes;
                    
                    openModal(feedbackModal);
                } else {
                    showAlert('Error loading feedback: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Network error: ' + error, 'error');
            });
    }
    
    function getRatingStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star ${i <= rating ? 'filled' : ''}"></i>`;
        }
        return stars;
    }
    
    function openModal(modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    }
    
    function exportFeedback(format) {
        const config = feedbackConfig.export_options[format];
        if (!config) return;
        
        // Build export URL with current filters
        const params = new URLSearchParams();
        params.append('format', format);
        
        if (currentFilters.status !== 'all') params.append('status', currentFilters.status);
        if (currentFilters.rating > 0) params.append('rating', currentFilters.rating);
        
        // Open in new tab to trigger download
        window.open(`export_feedback.php?${params.toString()}`, '_blank');
    }
});