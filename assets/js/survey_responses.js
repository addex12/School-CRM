document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    flatpickr('#date_from', {
        dateFormat: 'Y-m-d',
        allowInput: true
    });
    
    flatpickr('#date_to', {
        dateFormat: 'Y-m-d',
        allowInput: true
    });
    
    // Delete response button handler
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const deleteModal = document.getElementById('delete-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    let responseIdToDelete = null;
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            responseIdToDelete = this.dataset.responseId;
            deleteModal.style.display = 'block';
        });
    });
    
    // Modal close handlers
    const closeModalButtons = document.querySelectorAll('.close-modal');
    closeModalButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
            responseIdToDelete = null;
        });
    });
    
    // Click outside modal to close
    window.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            deleteModal.style.display = 'none';
            responseIdToDelete = null;
        }
    });
    
    // Confirm delete handler
    confirmDeleteBtn.addEventListener('click', function() {
        if (responseIdToDelete) {
            deleteResponse(responseIdToDelete);
        }
    });
    
    // Export all responses
    const exportAllBtn = document.getElementById('export-all');
    if (exportAllBtn) {
        exportAllBtn.addEventListener('click', function() {
            const surveyId = new URLSearchParams(window.location.search).get('id');
            window.location.href = `response_export.php?survey_id=${surveyId}&all=1`;
        });
    }
    
    // Delete selected responses
    const deleteSelectedBtn = document.getElementById('delete-selected');
    if (deleteSelectedBtn) {
        // In a real implementation, you would add checkboxes to each row
        // and handle the selection/deletion of multiple responses
        deleteSelectedBtn.addEventListener('click', function() {
            // This would be implemented with actual selected items
            alert('This functionality would delete selected responses');
        });
    }
});

function deleteResponse(responseId) {
    if (!confirm('Are you sure you want to delete this response? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('response_id', responseId);
    
    fetch('response_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const row = document.querySelector(`[data-response-id="${responseId}"]`).closest('tr');
            if (row) {
                row.remove();
                // Show success message
                showAlert('Response deleted successfully', 'success');
            }
        } else {
            showAlert(data.message || 'Failed to delete response', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting the response', 'error');
    })
    .finally(() => {
        document.getElementById('delete-modal').style.display = 'none';
    });
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}