document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    const fieldsPanel = document.querySelector('.fields-panel');
    const formPreview = document.getElementById('form-preview');
    const fieldModal = document.getElementById('field-modal');
    const previewModal = document.getElementById('preview-modal');
    const fieldConfigForm = document.getElementById('field-config-form');
    const surveyForm = document.getElementById('survey-form');
    const fieldsData = document.getElementById('fields-data');
    
    let currentField = null;
    let fieldCounter = 0;
    
    // Initialize Sortable for fields panel (draggable source)
    new Sortable(fieldsPanel, {
        group: {
            name: 'survey-fields',
            pull: 'clone',
            put: false
        },
        sort: false,
        animation: 150,
        filter: '.field-item',
        draggable: '.field-item'
    });
    
    // Initialize Sortable for form preview (drop target)
    new Sortable(formPreview, {
        group: {
            name: 'survey-fields',
            put: ['survey-fields']
        },
        animation: 150,
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            // When a field is added to the preview
            const fieldType = evt.item.dataset.type;
            evt.item.remove(); // Remove the dragged element
            
            // Create a new field element
            createNewField(fieldType);
        },
        onEnd: function(evt) {
            // Update field order when items are rearranged
            updateFieldsData();
        }
    });
    
    // Field type configuration
    const fieldTemplates = {
        text: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label for="${fieldId}">${label}</label>
                    <input type="text" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-font'
        },
        textarea: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label for="${fieldId}">${label}</label>
                    <textarea id="${fieldId}" name="${fieldId}" rows="3"></textarea>
                </div>
            `,
            icon: 'fas fa-align-left'
        },
        radio: {
            html: (fieldId, label, options) => {
                let optionsHtml = options.map((opt, i) => `
                    <label class="option">
                        <input type="radio" name="${fieldId}" value="${opt.trim()}">
                        ${opt.trim()}
                    </label>
                `).join('');
                
                return `
                    <div class="form-group">
                        <label>${label}</label>
                        <div class="options">${optionsHtml}</div>
                    </div>
                `;
            },
            icon: 'far fa-dot-circle'
        },
        checkbox: {
            html: (fieldId, label, options) => {
                let optionsHtml = options.map((opt, i) => `
                    <label class="option">
                        <input type="checkbox" name="${fieldId}[]" value="${opt.trim()}">
                        ${opt.trim()}
                    </label>
                `).join('');
                
                return `
                    <div class="form-group">
                        <label>${label}</label>
                        <div class="options">${optionsHtml}</div>
                    </div>
                `;
            },
            icon: 'far fa-check-square'
        },
        select: {
            html: (fieldId, label, options) => {
                let optionsHtml = options.map(opt => `
                    <option value="${opt.trim()}">${opt.trim()}</option>
                `).join('');
                
                return `
                    <div class="form-group">
                        <label for="${fieldId}">${label}</label>
                        <select id="${fieldId}" name="${fieldId}">
                            <option value="">Select an option</option>
                            ${optionsHtml}
                        </select>
                    </div>
                `;
            },
            icon: 'fas fa-caret-down'
        },
        number: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label for="${fieldId}">${label}</label>
                    <input type="number" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-hashtag'
        },
        date: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label for="${fieldId}">${label}</label>
                    <input type="date" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'far fa-calendar-alt'
        },
        rating: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label>${label}</label>
                    <div class="rating-container">
                        <input type="hidden" name="${fieldId}" value="">
                        <span class="rating-star" data-value="1">★</span>
                        <span class="rating-star" data-value="2">★</span>
                        <span class="rating-star" data-value="3">★</span>
                        <span class="rating-star" data-value="4">★</span>
                        <span class="rating-star" data-value="5">★</span>
                        <div class="rating-labels">
                            <span>1 (Poor)</span>
                            <span>5 (Excellent)</span>
                        </div>
                    </div>
                </div>
            `,
            icon: 'fas fa-star'
        },
        file: {
            html: (fieldId, label) => `
                <div class="form-group">
                    <label for="${fieldId}">${label}</label>
                    <input type="file" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-file-upload'
        }
    };
    
    // Create a new field in the preview
    function createNewField(type) {
        fieldCounter++;
        const fieldId = `field_${fieldCounter}`;
        
        // Show configuration modal
        currentField = {
            type: type,
            id: fieldId,
            label: `${type.charAt(0).toUpperCase() + type.slice(1)} Field`,
            name: fieldId,
            required: true,
            options: ['Option 1', 'Option 2'],
            validation: {}
        };
        
        showFieldModal();
    }
    
    // Show field configuration modal
    function showFieldModal() {
        document.getElementById('field-type').value = currentField.type;
        document.getElementById('field-label').value = currentField.label;
        document.getElementById('field-name').value = currentField.name;
        document.getElementById('field-required').checked = currentField.required;
        
        // Show/hide options based on field type
        const optionsContainer = document.getElementById('options-container');
        if (['radio', 'checkbox', 'select'].includes(currentField.type)) {
            optionsContainer.style.display = 'block';
            document.getElementById('field-options').value = currentField.options.join('\n');
        } else {
            optionsContainer.style.display = 'none';
        }
        
        // Set validation rules
        if (currentField.validation) {
            document.getElementById('validation-min').value = currentField.validation.min || '';
            document.getElementById('validation-max').value = currentField.validation.max || '';
            document.getElementById('validation-regex').value = currentField.validation.regex || '';
        }
        
        fieldModal.style.display = 'block';
    }
    
    // Save field configuration
    fieldConfigForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Update current field with form values
        currentField.label = document.getElementById('field-label').value;
        currentField.name = document.getElementById('field-name').value;
        currentField.required = document.getElementById('field-required').checked;
        
        // Get options if applicable
        if (['radio', 'checkbox', 'select'].includes(currentField.type)) {
            const optionsText = document.getElementById('field-options').value;
            currentField.options = optionsText.split('\n').filter(opt => opt.trim() !== '');
        }
        
        // Get validation rules
        currentField.validation = {
            min: document.getElementById('validation-min').value || null,
            max: document.getElementById('validation-max').value || null,
            regex: document.getElementById('validation-regex').value || null
        };
        
        // Create the field element
        createFieldElement();
        
        // Close modal
        fieldModal.style.display = 'none';
    });
    
    // Create the actual field element in the preview
    function createFieldElement() {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'form-field';
        fieldDiv.dataset.fieldId = currentField.id;
        fieldDiv.dataset.fieldType = currentField.type;
        fieldDiv.dataset.fieldName = currentField.name;
        
        // Add field HTML
        const template = fieldTemplates[currentField.type];
        fieldDiv.innerHTML = `
            <div class="field-header">
                <div class="field-title">
                    <i class="${template.icon}"></i>
                    ${currentField.label}
                    ${currentField.required ? '<span class="required">*</span>' : ''}
                </div>
                <div class="field-actions">
                    <button type="button" class="edit-field" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="delete-field" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            ${template.html(currentField.name, currentField.label, currentField.options || [])}
        `;
        
        // Add to preview
        formPreview.querySelector('.empty-message')?.remove();
        formPreview.appendChild(fieldDiv);
        
        // Add event listeners for edit/delete
        fieldDiv.querySelector('.edit-field').addEventListener('click', function() {
            editField(fieldDiv);
        });
        
        fieldDiv.querySelector('.delete-field').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this field?')) {
                fieldDiv.remove();
                updateFieldsData();
                
                // Show empty message if no fields left
                if (formPreview.querySelectorAll('.form-field').length === 0) {
                    const emptyMsg = document.createElement('p');
                    emptyMsg.className = 'empty-message';
                    emptyMsg.textContent = 'Drag fields from the right panel to build your form';
                    formPreview.appendChild(emptyMsg);
                }
            }
        });
        
        // Initialize any field-specific JS
        if (currentField.type === 'rating') {
            initRatingField(fieldDiv);
        }
        
        updateFieldsData();
    }
    
    // Edit existing field
    function editField(fieldDiv) {
        currentField = {
            type: fieldDiv.dataset.fieldType,
            id: fieldDiv.dataset.fieldId,
            label: fieldDiv.querySelector('.field-title').textContent.trim().replace(/\s*\*$/, ''),
            name: fieldDiv.dataset.fieldName,
            required: fieldDiv.querySelector('.required') !== null,
            options: [],
            validation: {}
        };
        
        // For fields with options, get current options
        if (['radio', 'checkbox', 'select'].includes(currentField.type)) {
            const options = [];
            fieldDiv.querySelectorAll('.options input, .options select option').forEach(el => {
                if (el.value && !options.includes(el.value)) {
                    options.push(el.value);
                }
            });
            currentField.options = options;
        }
        
        showFieldModal();
    }
    
    // Initialize rating field interaction
    function initRatingField(fieldDiv) {
        const stars = fieldDiv.querySelectorAll('.rating-star');
        const hiddenInput = fieldDiv.querySelector('input[type="hidden"]');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                hiddenInput.value = value;
            });
        });
    }
    
    // Update the hidden fields data input
    function updateFieldsData() {
        const fields = [];
        const fieldElements = formPreview.querySelectorAll('.form-field');
        
        fieldElements.forEach((fieldEl, index) => {
            const field = {
                type: fieldEl.dataset.fieldType,
                label: fieldEl.querySelector('.field-title').textContent.trim().replace(/\s*\*$/, ''),
                name: fieldEl.dataset.fieldName,
                required: fieldEl.querySelector('.required') !== null,
                order: index
            };
            
            // Add options for relevant field types
            if (['radio', 'checkbox', 'select'].includes(field.type)) {
                const options = [];
                fieldEl.querySelectorAll('.options input, .options select option').forEach(el => {
                    if (el.value && !options.includes(el.value)) {
                        options.push(el.value);
                    }
                });
                field.options = options.join('\n');
            }
            
            fields.push(field);
        });
        
        fieldsData.value = JSON.stringify(fields);
    }
    
    // Preview button
    document.getElementById('preview-btn').addEventListener('click', function() {
        const previewContent = document.getElementById('survey-preview-content');
        previewContent.innerHTML = `
            <h3>${document.getElementById('title').value || 'Survey Title'}</h3>
            <p>${document.getElementById('description').value || 'Survey description'}</p>
            <hr>
            ${formPreview.innerHTML.replace(/field-actions/g, 'hidden-actions')}
        `;
        
        // Initialize rating fields in preview
        previewContent.querySelectorAll('.rating-container').forEach(container => {
            const stars = container.querySelectorAll('.rating-star');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.dataset.value);
                    stars.forEach((s, i) => {
                        if (i < value) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    container.querySelector('input[type="hidden"]').value = value;
                });
            });
        });
        
        previewModal.style.display = 'block';
    });
    
    // Close modal buttons
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            fieldModal.style.display = 'none';
            previewModal.style.display = 'none';
        });
    });
    
    // Cancel field button
    document.getElementById('cancel-field').addEventListener('click', function() {
        fieldModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === fieldModal) {
            fieldModal.style.display = 'none';
        }
        if (e.target === previewModal) {
            previewModal.style.display = 'none';
        }
    });
});