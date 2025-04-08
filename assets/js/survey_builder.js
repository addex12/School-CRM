document.addEventListener('DOMContentLoaded', function () {
    console.log('survey_builder.js loaded');

    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');

    if (!questionsContainer || !addQuestionBtn) {
        console.error('Required elements not found');
        return;
    }

    function createQuestionElement(index) {
        const div = document.createElement('div');
        div.className = 'question-row';
        div.dataset.index = index;
        div.innerHTML = `
            <div class="form-group">
                <input type="text" name="questions[${index}]" placeholder="Enter your question" required>
            </div>
            <div class="form-group">
                <select name="field_types[${index}]" required>
                    <option value="text">Text</option>
                    <option value="radio">Multiple Choice (Single)</option>
                    <option value="checkbox">Multiple Choice (Multiple)</option>
                    <option value="select">Dropdown</option>
                </select>
            </div>
            <div class="form-group options-group">
                <label>Options:</label>
                <textarea name="options[${index}]" rows="3" placeholder="Enter each option on a new line"></textarea>
                <p class="help-text">Enter each option on a new line</p>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="required[${index}]">
                    Required
                </label>
            </div>
            <button type="button" class="remove-question btn-danger">Remove Question</button>
        `;
        return div;
    }

    function addQuestion() {
        const index = questionsContainer.children.length;
        const newQuestion = createQuestionElement(index);
        questionsContainer.appendChild(newQuestion);
        
        // Add remove button click handler
        const removeBtn = newQuestion.querySelector('.remove-question');
        removeBtn.addEventListener('click', function() {
            questionsContainer.removeChild(newQuestion);
        });
    }

    // Add question button click handler
    addQuestionBtn.addEventListener('click', addQuestion);

    // Initialize existing questions
    document.querySelectorAll('.question-row').forEach((question, index) => {
        const removeBtn = question.querySelector('.remove-question');
        removeBtn.addEventListener('click', function() {
            questionsContainer.removeChild(question);
        });
    });

    // Add initial question if none exist
    if (questionsContainer.children.length === 0) {
        addQuestion();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const addQuestionButton = document.getElementById('add-question');
    const questionTemplate = document.getElementById('question-template').content;
    const questionContainer = document.getElementById('question-container');

    // Add question functionality
    addQuestionButton.addEventListener('click', function () {
        const questionClone = questionTemplate.cloneNode(true);
        questionContainer.appendChild(questionClone);
    });

    // Handle delete question functionality
    questionContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-delete-question')) {
            e.target.closest('.question-item').remove();
        }
    });
});
