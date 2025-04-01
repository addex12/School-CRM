document.addEventListener('DOMContentLoaded', function () {
    console.log('survey_builder.js loaded'); // Check if the script is loaded

    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');
    const generateAIQuestionsBtn = document.getElementById('generate-ai-questions');
    const aiOutput = document.getElementById('ai-output');
    const aiSuggestionsContainer = document.getElementById('ai-suggestions-container');
    const generateQuestionBtn = document.getElementById('generate-question-btn');
    // Check if the required elements are present in the DOM

    if (!questionsContainer || !addQuestionBtn || !generateAIQuestionsBtn) {
        console.error('Required elements not found');
        return;
    }

    let questionCount = questionsContainer.children.length;
    console.log('Initial question count:', questionCount); // Debugging log
    // Initialize existing questions        
    // Add new question
    addQuestionBtn.addEventListener('click', () => {
        console.log('Add question button clicked'); // Debugging log
        const newQuestion = createQuestionElement(questionCount);
        questionsContainer.appendChild(newQuestion);
        initQuestionEvents(newQuestion);
        questionCount++;
    });

    // Generate AI questions
    generateAIQuestionsBtn.addEventListener('click', async () => {
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;

        if (!title || !description) {
            alert('Please provide a title and description for the survey.');
            return;
        }

        try {
            aiOutput.textContent = 'Generating questions...'; // Show loading message
            const response = await fetch('/api/getAISuggestions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    codeSnippet: '',
                    context: `Generate survey questions for a survey titled "${title}" with the description "${description}".`,
                }),
            });

            const data = await response.json();
            if (data.suggestions) {
                aiOutput.textContent = 'AI Suggestions:';
                const questions = data.suggestions.split('\n').filter((q) => q.trim());
                questions.forEach((question) => {
                    const newQuestion = createQuestionElement(questionCount, question);
                    questionsContainer.appendChild(newQuestion);
                    initQuestionEvents(newQuestion);
                    questionCount++;
                });
            } else {
                aiOutput.textContent = 'No suggestions received from AI.';
            }
        } catch (error) {
            console.error('Error fetching AI suggestions:', error);
            aiOutput.textContent = 'Failed to fetch AI suggestions.';
        }

        
    });

    // Initialize existing questions
    document.querySelectorAll('.question-card').forEach((question) => {
        initQuestionEvents(question);
    });

    function createQuestionElement(index, questionText = '') {
        const div = document.createElement('div');
        div.className = 'question-card';
        div.innerHTML = `
            <div class="form-group">
                <label>Question</label>
                <input type="text" name="questions[]" value="${questionText}" required placeholder="Enter your question">
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
    document.getElementById('survey-form').addEventListener('submit', function () {
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
    // Initial setup for existing questions
    document.querySelectorAll('.question-card').forEach((question) => {
        initQuestionEvents(question);
    });
    // Add new question handler
    addQuestionBtn.addEventListener('click', () => {
        const newQuestion = createQuestionElement(questionCount);
        questionsContainer.appendChild(newQuestion);
        initQuestionEvents(newQuestion);
        questionCount++;
    });
    // Initial question count setup     
    questionCount = questionsContainer.children.length;
    // AI question generation handler
    generateQuestionBtn.addEventListener('click', async () => {
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        const aiOutput = document.getElementById('ai-output');
        if (!title || !description) {
            alert('Please provide a title and description for the survey.');
            return;
        }
        aiOutput.innerHTML = 'Generating questions...'; // Show loading message
        try {
            const response = await fetch('/generate-questions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title,
                    description,
                }),
            });
            const data = await response.json(); // Parse the response as JSON
            if (data.suggestions) {
                aiOutput.innerHTML = data.suggestions; // Display the suggestions
            } else {
                aiOutput.innerHTML = 'No suggestions available.'; // Handle case where no suggestions are received  
            }
        } catch (error) {
            console.error('Error fetching AI suggestions:', error); // Log the error
            aiOutput.innerHTML = 'Failed to fetch AI suggestions.'; // Display error message
            }
            });
        });
        
