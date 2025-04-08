document.addEventListener('DOMContentLoaded', function () {
    const feedbackTableRows = document.querySelectorAll('.table tbody tr');
    const searchInput = document.getElementById('feedback-search');

    // Filter feedback table rows based on search input
    searchInput?.addEventListener('input', function () {
        const query = searchInput.value.toLowerCase();
        feedbackTableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Highlight ratings based on configuration
    fetch('../admin/feedback_config.json')
        .then(response => response.json())
        .then(config => {
            const ratings = config.ratings;
            document.querySelectorAll('.feedback-rating').forEach(ratingElement => {
                const ratingValue = parseInt(ratingElement.textContent, 10);
                const ratingConfig = ratings.find(r => r.value === ratingValue);
                if (ratingConfig) {
                    ratingElement.style.color = ratingConfig.color;
                    ratingElement.title = ratingConfig.label;
                }
            });
        })
        .catch(error => console.error('Error loading feedback configuration:', error));
});

// Function to open the feedback modal
function openFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    modal.style.display = 'block';
}

// Function to close the feedback modal
function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    modal.style.display = 'none';
}
// Function to submit feedback
function submitFeedback() {
    const feedbackForm = document.getElementById('feedbackForm');
    const formData = new FormData(feedbackForm);

    fetch('submit_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Feedback submitted successfully!');
            closeFeedbackModal();
            location.reload(); // Reload the page to see the new feedback
        } else {
            alert('Error submitting feedback: ' + data.message);
        }
    })
    .catch(error => console.error('Error submitting feedback:', error));
}
// Function to handle feedback actions (like delete)
function handleFeedbackAction(action, feedbackId) {
    if (action === 'delete' && !confirm('Are you sure you want to delete this feedback?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'feedback.php';

    const feedbackIdInput = document.createElement('input');
    feedbackIdInput.type = 'hidden';
    feedbackIdInput.name = 'feedback_id';
    feedbackIdInput.value = feedbackId;

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;

    form.appendChild(feedbackIdInput);
    form.appendChild(actionInput);
    document.body.appendChild(form);
    form.submit();
}
// Function to handle feedback details modal
function openFeedbackDetails(feedbackId) {
    const modal = document.getElementById('feedbackDetailsModal');
    const feedbackDetailsContainer = document.getElementById('feedbackDetailsContainer');

    fetch(`feedback_details.php?feedback_id=${feedbackId}`)
        .then(response => response.text())
        .then(data => {
            feedbackDetailsContainer.innerHTML = data;
            modal.style.display = 'block';
        })
        .catch(error => console.error('Error loading feedback details:', error));
}

