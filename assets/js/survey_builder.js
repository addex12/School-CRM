document.addEventListener('DOMContentLoaded', function () {
    // Add new survey field
    document.getElementById('add-field').addEventListener('click', function () {
        const fieldCount = document.querySelectorAll('.survey-field').length + 1;
        const fieldHTML = `
            <div class="survey-field">
                <label for="question-${fieldCount}">Question ${fieldCount}</label>
                <input type="text" name="questions[]" id="question-${fieldCount}" placeholder="Enter your question" required>
                <select name="field_types[]" class="field-type-selector" required>
                    <option value="text">Text Input</option>
                    <option value="textarea">Text Area</option>
                    <option value="radio">Radio Buttons</option>
                    <option value="checkbox">Checkboxes</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="rating">Rating</option>
                    <option value="file">File Upload</option>
                </select>
                <input type="text" name="placeholders[]" placeholder="Placeholder (optional)">
                <div class="field-options" style="display: none;">
                    <label>Options (comma-separated):</label>
                    <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
                </div>
                <label><input type="checkbox" name="required[]"> Required</label>
                <button type="button" class="remove-field btn btn-danger">Remove</button>
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