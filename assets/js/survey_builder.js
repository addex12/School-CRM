document.addEventListener('DOMContentLoaded', function() {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');

    // Add new question
    addQuestionBtn.addEventListener('click', () => {
        const questionIndex = questionsContainer.children.length;
        const newQuestion = createQuestionElement(questionIndex);
        questionsContainer.appendChild(newQuestion);
        initQuestionEvents(newQuestion);
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
                    <input type="checkbox" name="required[${index}]">
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
        });
    }

    // Form validation
    document.getElementById('survey-form').addEventListener('submit', function(e) {
        let valid = true;
        document.querySelectorAll('[name="questions[]"]').forEach(input => {
            if (!input.value.trim()) {
                valid = false;
                input.closest('.form-group').classList.add('error');
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required question fields!');
        }
    });
});