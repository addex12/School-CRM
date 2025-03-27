document.addEventListener('DOMContentLoaded', function () {
    // Add new survey field
    document.getElementById('add-field').addEventListener('click', function () {
        const fieldCount = document.querySelectorAll('.survey-field').length + 1;
        const fieldHTML = `
            <div class="survey-field">
                <label>Question ${fieldCount}</label>
                <input type="text" name="questions[]" placeholder="Enter your question" required>
                <button type="button" class="remove-field">Remove</button>
            </div>`;
        document.getElementById('survey-fields').insertAdjacentHTML('beforeend', fieldHTML);
    });

    // Remove survey field
    document.getElementById('survey-fields').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('.survey-field').remove();
        }
    });

    // Show options input for applicable field types
    document.getElementById('survey-fields').addEventListener('change', function (e) {
        if (e.target.classList.contains('field-type-selector')) {
            const optionsDiv = e.target.closest('.survey-field').querySelector('.field-options');
            if (['radio', 'checkbox', 'dropdown'].includes(e.target.value)) {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }
    });

    // Initialize date/time inputs with current time
    const now = new Date();
    const timezoneOffset = now.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
    document.getElementById('starts_at').value = localISOTime;
    document.getElementById('ends_at').value = localISOTime;
});