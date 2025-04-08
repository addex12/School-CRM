// General JavaScript for the survey system

document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips
    initTooltips();
    
    // Handle survey question management in admin
    if (document.getElementById('questions-container')) {
        initQuestionManagement();
    }
    
    // Handle survey submission
    if (document.getElementById('survey-form')) {
        initSurveyForm();
    }
});

function initTooltips() {
    // Initialize any tooltips using Tippy.js or similar
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseover', function() {
            // Simple tooltip implementation
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'custom-tooltip';
            tooltipElement.textContent = tooltipText;
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 5}px`;
            tooltipElement.style.left = `${rect.left + rect.width/2 - tooltipElement.offsetWidth/2}px`;
            
            this.addEventListener('mouseout', function() {
                document.body.removeChild(tooltipElement);
            });
        });
    });
}

function initQuestionManagement() {
    const container = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');
    
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', function() {
            const questionCount = container.querySelectorAll('.question-item').length;
            const newQuestion = createQuestionElement(questionCount + 1);
            container.appendChild(newQuestion);
        });
    }
    
    // Handle question type changes
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type')) {
            const questionItem = e.target.closest('.question-item');
            const optionsContainer = questionItem.querySelector('.question-options');
            
            if (e.target.value === 'multiple_choice') {
                optionsContainer.style.display = 'block';
                // Ensure at least two options
                if (questionItem.querySelectorAll('.option-input').length < 2) {
                    addOptionField(questionItem);
                    addOptionField(questionItem);
                }
            } else {
                optionsContainer.style.display = 'none';
            }
        }
        
        // Add option button
        if (e.target.classList.contains('add-option')) {
            const questionItem = e.target.closest('.question-item');
            addOptionField(questionItem);
        }
    });
    
    // Handle question deletion
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-question')) {
            const questionItem = e.target.closest('.question-item');
            if (confirm('Are you sure you want to delete this question?')) {
                container.removeChild(questionItem);
                renumberQuestions();
            }
        }
        
        // Delete option button
        if (e.target.classList.contains('delete-option')) {
            const optionContainer = e.target.closest('.option-container');
            const optionsContainer = optionContainer.parentElement;
            if (optionsContainer.querySelectorAll('.option-container').length > 1) {
                optionsContainer.removeChild(optionContainer);
            } else {
                alert('A question must have at least one option');
            }
        }
    });
}

function createQuestionElement(number) {
    const div = document.createElement('div');
    div.className = 'question-item';
    div.innerHTML = `
        <div class="question-header">
            <h3>Question #${number}</h3>
            <button type="button" class="delete-question">Delete</button>
        </div>
        <div class="form-group">
            <label>Question Text:</label>
            <textarea name="questions[${number}][text]" required></textarea>
        </div>
        <div class="form-group">
            <label>Question Type:</label>
            <select name="questions[${number}][type]" class="question-type" required>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="text">Text Answer</option>
                <option value="rating">Rating (1-5)</option>
            </select>
        </div>
        <div class="form-group question-options" style="display: none;">
            <label>Options (for multiple choice):</label>
            <div class="options-container"></div>
            <button type="button" class="add-option">Add Option</button>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="questions[${number}][required]" checked>
                Required Question
            </label>
        </div>
    `;
    return div;
}

function addOptionField(questionItem) {
    const optionsContainer = questionItem.querySelector('.options-container');
    const optionCount = optionsContainer.querySelectorAll('.option-container').length;
    
    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-container';
    optionDiv.innerHTML = `
        <input type="text" class="option-input" name="questions[${questionItem.dataset.number || 1}][options][${optionCount}]" placeholder="Option ${optionCount + 1}" required>
        <button type="button" class="delete-option">×</button>
    `;
    optionsContainer.appendChild(optionDiv);
}

function renumberQuestions() {
    const container = document.getElementById('questions-container');
    const questions = container.querySelectorAll('.question-item');
    
    questions.forEach((question, index) => {
        question.querySelector('h3').textContent = `Question #${index + 1}`;
        // Update all the name attributes to maintain proper array structure
        const inputs = question.querySelectorAll('[name^="questions["]');
        inputs.forEach(input => {
            const name = input.name.replace(/questions\[\d+\]/g, `questions[${index + 1}]`);
            input.name = name;
        });
    });
}

function initSurveyForm() {
    const form = document.getElementById('survey-form');
    
    form.addEventListener('submit', function(e) {
        // Validate required questions
        const requiredQuestions = form.querySelectorAll('[data-required="true"]');
        let isValid = true;
        
        requiredQuestions.forEach(question => {
            const input = question.querySelector('input, textarea, select');
            if (!input.value.trim()) {
                isValid = false;
                question.classList.add('error');
            } else {
                question.classList.remove('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please answer all required questions');
        }
    });
    
    // Rating question interaction
    form.addEventListener('click', function(e) {
        if (e.target.classList.contains('rating-star')) {
            const container = e.target.closest('.rating-container');
            const stars = container.querySelectorAll('.rating-star');
            const input = container.querySelector('input');
            const value = parseInt(e.target.dataset.value);
            
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
            
            input.value = value;
        }
    });
}