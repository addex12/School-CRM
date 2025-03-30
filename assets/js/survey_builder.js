document.addEventListener('DOMContentLoaded', function() {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');
    let questionCount = questionsContainer.children.length;

    // Add new question
    addQuestionBtn.addEventListener('click', () => {
        const newQuestion = createQuestionElement(questionCount);
        questionsContainer.appendChild(newQuestion);
        initQuestionEvents(newQuestion);
        questionCount++;
    });

    // Initialize existing questions
    document.querySelectorAll('.question-card').forEach(question => {
        initQuestionEvents(question);
    });

    function createQuestionElement(index) {
        const div = document.createElement('div');
        div.className = 'question-card';
        div.innerHTML = `
            <div class="form-group">
                <label>Question</label>
                <input type="text" name="questions[]" required placeholder="Enter your question">
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label>Field Type</label>
                <select name="field_types[]" class="field-type-select" required>
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
            <div class="form-group options-group">
                <label>Options (comma-separated)</label>
                <input type="text" name="options[]" placeholder="Option 1, Option 2, Option 3">
            </div>
            <div class="form-group">
                <label>Placeholder</label>
                <input type="text" name="placeholders[]" placeholder="Enter placeholder text">
            </div>
            <div class="form-group">
                <label class="required-check">
                    <input type="checkbox" name="required[]">
                    Required
                </label>
            </div>
            <button type="button" class="remove-question btn btn-danger">
                <i class="fas fa-trash"></i> Remove Question
            </button>
        `;
        return div;
    }

    function initQuestionEvents(question) {
        // Field type change handler
        const typeSelect = question.querySelector('.field-type-select');
        const optionsGroup = question.querySelector('.options-group');

        typeSelect.addEventListener('change', () => {
            const showOptions = ['radio', 'checkbox', 'select'].includes(typeSelect.value);
            optionsGroup.style.display = showOptions ? 'block' : 'none';
            optionsGroup.classList.toggle('visible', showOptions);
        });

        // Trigger initial state
        typeSelect.dispatchEvent(new Event('change'));

        // Remove question handler
        question.querySelector('.remove-question').addEventListener('click', () => {
            question.remove();
            reindexQuestions();
        });
    }

    // Reindex all questions before submission
    document.getElementById('survey-form').addEventListener('submit', function(e) {
        reindexQuestions();
    });

    function reindexQuestions() {
        questionsContainer.querySelectorAll('.question-card').forEach((question, index) => {
            // Update all names with current index
            question.querySelector('[name="questions[]"]').name = `questions[${index}]`;
            question.querySelector('[name="field_types[]"]').name = `field_types[${index}]`;
            question.querySelector('[name="options[]"]').name = `options[${index}]`;
            question.querySelector('[name="placeholders[]"]').name = `placeholders[${index}]`;
            
            // Handle required checkbox
            const requiredCheckbox = question.querySelector('[name="required[]"]');
            requiredCheckbox.name = `required[${index}]`;
        });
    }
});