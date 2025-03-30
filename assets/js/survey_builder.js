// survey_builder.js
document.addEventListener('DOMContentLoaded', function() {
    const builder = {
        init() {
            this.cacheElements();
            this.bindEvents();
            this.initRichTextEditors();
            this.initDragAndDrop();
            this.loadSurveyData();
            this.initAutoSave();
            this.initCollaboration();
        },

        cacheElements() {
            this.container = document.getElementById('survey-fields');
            this.form = document.getElementById('survey-form');
            this.addQuestionBtn = document.getElementById('add-question');
            this.fieldTemplate = document.getElementById('field-template');
            this.logicBuilder = document.getElementById('logic-builder');
            this.themeSelector = document.getElementById('theme-selector');
            this.undoStack = [];
            this.redoStack = [];
        },

        bindEvents() {
            this.addQuestionBtn.addEventListener('click', () => this.addField());
            this.form.addEventListener('submit', e => this.handleSubmit(e));
            document.addEventListener('keydown', e => this.handleUndoRedo(e));
            this.themeSelector.addEventListener('change', e => this.changeTheme(e));
            this.container.addEventListener('change', e => this.handleFieldChange(e));
            this.container.addEventListener('click', e => this.handleFieldActions(e));
        },

        addField(type = 'text') {
            const fieldId = Date.now();
            const fieldHTML = `
                <div class="survey-field draggable" data-id="${fieldId}">
                    <div class="field-header">
                        <span class="drag-handle">☰</span>
                        <div class="field-actions">
                            <button class="btn logic-btn" data-action="logic">
                                <i class="fas fa-project-diagram"></i>
                            </button>
                            <button class="btn delete-btn" data-action="delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="rich-editor" id="editor-${fieldId}"></div>
                        <input type="hidden" name="questions[${fieldId}]">
                    </div>

                    <div class="form-group">
                        <select class="field-type" name="types[${fieldId}]">
                            ${this.getTypeOptions()}
                        </select>
                    </div>

                    <div class="field-options"></div>

                    <div class="validation-rules">
                        <div class="rule" data-rule="required">
                            <label>
                                <input type="checkbox" name="required[${fieldId}]"> Required
                            </label>
                        </div>
                        <div class="rule" data-rule="regex">
                            <input type="text" placeholder="RegEx pattern" 
                                   name="regex[${fieldId}]">
                        </div>
                    </div>
                </div>`;

            this.container.insertAdjacentHTML('beforeend', fieldHTML);
            this.initFieldEditor(fieldId);
            this.saveState();
        },

        initRichTextEditors() {
            document.querySelectorAll('.rich-editor').forEach(editor => {
                const quill = new Quill(editor, {
                    modules: { toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image']
                    ]},
                    theme: 'snow'
                });
                
                quill.on('text-change', () => {
                    const input = editor.nextElementSibling;
                    input.value = quill.root.innerHTML;
                });
            });
        },

        initDragAndDrop() {
            new Sortable(this.container, {
                handle: '.drag-handle',
                animation: 150,
                onUpdate: () => this.saveState()
            });
        },

        handleFieldChange(e) {
            if (e.target.matches('.field-type')) {
                this.updateOptions(e.target.closest('.survey-field'));
            }
        },

        updateOptions(field) {
            const type = field.querySelector('.field-type').value;
            const optionsContainer = field.querySelector('.field-options');
            optionsContainer.innerHTML = type === 'radio' || type === 'checkbox' ? 
                `<input type="text" class="options-input" 
                      placeholder="Option 1, Option 2, Option 3">` : '';
        },

        handleSubmit(e) {
            e.preventDefault();
            if (this.validateForm()) {
                this.showLoading(true);
                const formData = new FormData(this.form);
                formData.append('logic', JSON.stringify(this.getLogicRules()));
                
                fetch(this.form.action, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) window.location.href = 'surveys.php';
                });
            }
        },

        validateForm() {
            let isValid = true;
            document.querySelectorAll('.survey-field').forEach(field => {
                const required = field.querySelector('[name^="required"]:checked');
                const input = field.querySelector('input, textarea, select');
                
                if (required && !input.value.trim()) {
                    isValid = false;
                    field.classList.add('invalid');
                }
            });
            return isValid;
        },

        initAutoSave() {
            setInterval(() => {
                if (this.isDirty) {
                    this.saveDraft();
                    this.isDirty = false;
                }
            }, 30000);
        },

        saveState() {
            this.undoStack.push(this.container.innerHTML);
            this.redoStack = [];
            this.isDirty = true;
        },

        handleUndoRedo(e) {
            if (e.ctrlKey && e.key === 'z') {
                if (this.undoStack.length > 0) {
                    this.redoStack.push(this.container.innerHTML);
                    this.container.innerHTML = this.undoStack.pop();
                }
            }
            if (e.ctrlKey && e.key === 'y') {
                if (this.redoStack.length > 0) {
                    this.undoStack.push(this.container.innerHTML);
                    this.container.innerHTML = this.redoStack.pop();
                }
            }
        },

        changeTheme(e) {
            document.documentElement.style.setProperty(
                '--primary-color', e.target.value
            );
        }
    };

    builder.init();
});

document.addEventListener('DOMContentLoaded', function () {
    const addQuestionButton = document.getElementById('add-question');
    const questionsContainer = document.getElementById('questions-container');

    addQuestionButton.addEventListener('click', function () {
        const questionIndex = questionsContainer.children.length;

        // Create a new question card
        const questionCard = document.createElement('div');
        questionCard.classList.add('question-card');

        questionCard.innerHTML = `
            <div class="form-group">
                <label>Question</label>
                <input type="text" name="questions[]" placeholder="Enter your question" required />
            </div>
            <div class="form-group">
                <label>Field Type</label>
                <select name="field_types[]" required>
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
            <div class="form-group">
                <label>Options (comma-separated, for applicable types)</label>
                <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
            </div>
            <div class="form-group">
                <label>Placeholder</label>
                <input type="text" name="placeholders[]" placeholder="Enter a placeholder">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="required[${questionIndex}]"> Required
                </label>
            </div>
            <button type="button" class="remove-question btn btn-danger">
                <i class="fas fa-trash"></i> Remove Question
            </button>
        `;

        // Add remove functionality
        questionCard.querySelector('.remove-question').addEventListener('click', function () {
            questionCard.remove();
        });

        // Append the new question card to the container
        questionsContainer.appendChild(questionCard);
    });
});
