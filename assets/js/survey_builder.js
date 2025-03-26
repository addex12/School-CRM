document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let currentField = null;
    let fieldCounter = 0;
    const availableLanguages = <?php echo json_encode($availableLanguages); ?>;
    const selectedLanguages = ['en']; // Default to English
    
    // Initialize Sortable for form preview
    const preview = document.getElementById('form-preview');
    new Sortable(preview, {
        group: {
            name: 'survey-fields',
            pull: false,
            put: ['field-types']
        },
        animation: 150,
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            const fieldType = evt.item.dataset.type;
            createField(fieldType);
            evt.item.remove(); // Remove the dragged item
        }
    });
    
    // Initialize Sortable for field types panel
    const fieldTypes = document.querySelector('.fields-panel');
    new Sortable(fieldTypes, {
        group: {
            name: 'field-types',
            pull: 'clone',
            put: false
        },
        sort: false,
        animation: 150
    });
    
    // Language selection handling
    document.querySelectorAll('input[name="languages[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked && !selectedLanguages.includes(this.value)) {
                selectedLanguages.push(this.value);
            } else if (!this.checked) {
                const index = selectedLanguages.indexOf(this.value);
                if (index !== -1) {
                    selectedLanguages.splice(index, 1);
                }
            }
        });
    });
    
    // Field creation function
    function createField(type) {
        fieldCounter++;
        const fieldId = 'field-' + fieldCounter;
        
        const field = document.createElement('div');
        field.className = 'form-field';
        field.dataset.id = fieldId;
        field.dataset.type = type;
        
        const fieldHtml = `
            <div class="field-actions">
                <button type="button" class="edit-field" title="Edit"><i class="fas fa-edit"></i></button>
                <button type="button" class="delete-field" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
            <label>${type.charAt(0).toUpperCase() + type.slice(1)} Field</label>
        `;
        
        field.innerHTML = fieldHtml;
        preview.querySelector('.empty-message')?.remove();
        preview.appendChild(field);
        
        // Set up edit and delete buttons
        field.querySelector('.edit-field').addEventListener('click', () => openFieldConfig(field));
        field.querySelector('.delete-field').addEventListener('click', () => field.remove());
        
        // Open config modal immediately
        openFieldConfig(field);
    }
    
    // Field configuration modal
    function openFieldConfig(fieldElement) {
        currentField = fieldElement;
        const fieldType = fieldElement.dataset.type;
        const modal = document.getElementById('field-modal');
        
        // Set field type
        document.getElementById('field-type').value = fieldType;
        document.getElementById('field-id').value = fieldElement.dataset.id;
        
        // Update modal title
        modal.querySelector('h3').innerHTML = `<i class="fas fa-cog"></i> Configure ${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} Field`;
        
        // Show/hide options section based on field type
        const optionsContainer = document.getElementById('options-container');
        optionsContainer.style.display = ['radio', 'checkbox', 'select', 'rating'].includes(fieldType) ? 'block' : 'none';
        
        // Clear and rebuild language tabs
        const languageTabs = document.getElementById('language-tabs');
        const languageContents = document.getElementById('language-contents');
        languageTabs.innerHTML = '';
        languageContents.innerHTML = '';
        
        // Create tabs for each selected language
        selectedLanguages.forEach((lang, index) => {
            // Create tab
            const tab = document.createElement('div');
            tab.className = `language-tab ${index === 0 ? 'active' : ''}`;
            tab.dataset.lang = lang;
            tab.textContent = availableLanguages[lang];
            tab.addEventListener('click', switchLanguageTab);
            languageTabs.appendChild(tab);
            
            // Create content
            const contentTemplate = document.getElementById('language-content-template').content.cloneNode(true);
            const content = contentTemplate.querySelector('.language-content');
            content.dataset.lang = lang;
            content.classList.toggle('active', index === 0);
            
            // Update labels
            content.querySelector('.translation-label').textContent = `Label (${availableLanguages[lang]})`;
            
            // Add to container
            languageContents.appendChild(content);
        });
        
        // Show modal
        modal.style.display = 'block';
    }
    
    // Switch between language tabs
    function switchLanguageTab(e) {
        const lang = e.currentTarget.dataset.lang;
        
        // Update active tab
        document.querySelectorAll('.language-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.lang === lang);
        });
        
        // Update active content
        document.querySelectorAll('.language-content').forEach(content => {
            content.classList.toggle('active', content.dataset.lang === lang);
        });
    }
    
    // Add option button handler
    document.getElementById('add-option').addEventListener('click', function() {
        const optionTemplate = document.getElementById('option-template').content.cloneNode(true);
        const optionContainer = document.getElementById('option-items');
        optionContainer.appendChild(optionTemplate);
        
        // Add delete handler
        optionContainer.lastElementChild.querySelector('.btn-delete-option').addEventListener('click', function() {
            this.closest('.option-item').remove();
        });
    });
    
    // Save field configuration
    document.getElementById('field-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fieldId = document.getElementById('field-id').value;
        const fieldType = document.getElementById('field-type').value;
        const fieldName = document.getElementById('field-name').value;
        const isRequired = document.getElementById('field-required').checked;
        
        // Collect field options
        let options = [];
        if (['radio', 'checkbox', 'select', 'rating'].includes(fieldType)) {
            document.querySelectorAll('#option-items .option-value').forEach(input => {
                if (input.value.trim()) {
                    options.push(input.value.trim());
                }
            });
        }
        
        // Collect validation rules
        const validation = {
            min: document.getElementById('validation-min').value || null,
            max: document.getElementById('validation-max').value || null,
            regex: document.getElementById('validation-regex').value || null
        };
        
        // Collect translations
        const translations = {};
        document.querySelectorAll('.language-content').forEach(content => {
            const lang = content.dataset.lang;
            translations[lang] = {
                label: content.querySelector('.translation-input').value
            };
            
            // For fields with options, collect option translations
            if (options.length > 0) {
                translations[lang].options = {};
                content.querySelectorAll('.option-translation-item').forEach((item, index) => {
                    if (options[index]) {
                        translations[lang].options[options[index]] = item.querySelector('.option-translation').value;
                    }
                });
            }
        });
        
        // Update the field element
        const fieldElement = document.querySelector(`.form-field[data-id="${fieldId}"]`);
        fieldElement.innerHTML = `
            <div class="field-actions">
                <button type="button" class="edit-field" title="Edit"><i class="fas fa-edit"></i></button>
                <button type="button" class="delete-field" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
            <label>${translations['en'].label || fieldName}</label>
            ${generateFieldPreview(fieldType, options)}
        `;
        
        // Set up edit and delete buttons
        fieldElement.querySelector('.edit-field').addEventListener('click', () => openFieldConfig(fieldElement));
        fieldElement.querySelector('.delete-field').addEventListener('click', () => fieldElement.remove());
        
        // Store field data
        fieldElement.dataset.config = JSON.stringify({
            type: fieldType,
            name: fieldName,
            required: isRequired,
            options: options,
            validation: validation,
            translations: translations
        });
        
        // Close modal
        document.getElementById('field-modal').style.display = 'none';
    });
    
    // Generate field preview HTML
    function generateFieldPreview(type, options) {
        switch (type) {
            case 'text':
                return '<input type="text" disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
            case 'textarea':
                return '<textarea disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;"></textarea>';
            case 'radio':
                return options.map(opt => `
                    <div style="margin: 5px 0;">
                        <input type="radio" disabled> ${opt}
                    </div>
                `).join('');
            case 'checkbox':
                return options.map(opt => `
                    <div style="margin: 5px 0;">
                        <input type="checkbox" disabled> ${opt}
                    </div>
                `).join('');
            case 'select':
                return `
                    <select disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select an option</option>
                        ${options.map(opt => `<option>${opt}</option>`).join('')}
                    </select>
                `;
            case 'number':
                return '<input type="number" disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
            case 'date':
                return '<input type="date" disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
            case 'rating':
                return `
                    <div style="margin: 10px 0;">
                        ${Array(5).fill().map((_, i) => '<i class="fas fa-star" style="color: #f1c40f; margin-right: 5px;"></i>').join('')}
                    </div>
                `;
            case 'file':
                return '<input type="file" disabled style="width: 100%; padding: 8px;">';
            default:
                return '';
        }
    }
    
    // Preview button handler
    document.getElementById('preview-btn').addEventListener('click', function() {
        const previewContent = document.getElementById('survey-preview-content');
        previewContent.innerHTML = '<h3>Survey Preview</h3>';
        
        // Add all fields to preview
        document.querySelectorAll('#form-preview .form-field').forEach(field => {
            const config = JSON.parse(field.dataset.config);
            const fieldHtml = `
                <div class="preview-field" style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                        ${config.translations['en'].label} ${config.required ? '<span style="color: red;">*</span>' : ''}
                    </label>
                    ${generateFieldPreview(config.type, config.options)}
                </div>
            `;
            previewContent.innerHTML += fieldHtml;
        });
        
        // Show modal
        document.getElementById('preview-modal').style.display = 'block';
    });
    
    // Form submission handler
    document.getElementById('survey-form').addEventListener('submit', function(e) {
        // Collect all field data
        const fields = [];
        document.querySelectorAll('#form-preview .form-field').forEach(field => {
            fields.push(JSON.parse(field.dataset.config));
        });
        
        // Set the fields data
        document.getElementById('fields-data').value = JSON.stringify(fields);
    });
    
    // Modal close handlers
    document.querySelectorAll('.close-modal, #cancel-field').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});