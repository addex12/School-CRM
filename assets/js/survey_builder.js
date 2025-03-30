document.addEventListener('DOMContentLoaded', function () {
    const surveyFieldsContainer = document.getElementById('survey-fields');

    // Add new survey field
    document.getElementById('add-question').addEventListener('click', function () {
        const fieldCount = document.querySelectorAll('.survey-field').length + 1;
        const fieldHTML = `
            <div class="survey-field">
                <div class="form-group">
                    <label for="question-${fieldCount}">Question ${fieldCount}</label>
                    <input type="text" id="question-${fieldCount}" name="questions[]" class="form-control" placeholder="Enter your question" required>
                </div>
                <div class="form-group">
                    <label for="field-type-${fieldCount}">Field Type</label>
                    <select id="field-type-${fieldCount}" name="field_types[]" class="form-control field-type-selector" required>
                        <option value="text">Text Input</option>
                        <option value="textarea">Text Area</option>
                        <option value="radio">Multiple Choice</option>
                        <option value="checkbox">Checkboxes</option>
                        <option value="select">Dropdown</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="rating">Rating</option>
                        <option value="file">File Upload</option>
                    </select>
                </div>
                <div class="form-group field-options" style="display: none;">
                    <label for="options-${fieldCount}">Options (comma-separated)</label>
                    <input type="text" id="options-${fieldCount}" name="options[]" class="form-control" placeholder="Option1, Option2, Option3">
                    <small>Separate options with commas (e.g., Option 1, Option 2, Option 3)</small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="required[]" value="${fieldCount}">
                        Required
                    </label>
                </div>
                <button type="button" class="btn btn-danger remove-field">Remove Question</button>
            </div>`;
        surveyFieldsContainer.insertAdjacentHTML('beforeend', fieldHTML);
    });

    // Remove survey field
    surveyFieldsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('.survey-field').remove();
            updateQuestionLabels();
        }
    });

    // Show options input for applicable field types
    surveyFieldsContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('field-type-selector')) {
            const optionsDiv = e.target.closest('.survey-field').querySelector('.field-options');
            if (['radio', 'checkbox', 'select'].includes(e.target.value)) {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }
    });

    // Update question labels dynamically
    function updateQuestionLabels() {
        document.querySelectorAll('.survey-field').forEach((field, index) => {
            const label = field.querySelector('label[for^="question-"]');
            if (label) label.textContent = `Question ${index + 1}`;
        });
    }

    // Initialize date/time inputs with current time
    const now = new Date();
    const timezoneOffset = now.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
    if (document.getElementById('starts_at')) document.getElementById('starts_at').value = localISOTime;
    if (document.getElementById('ends_at')) document.getElementById('ends_at').value = localISOTime;
});