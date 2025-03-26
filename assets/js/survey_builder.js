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
    
    // Initialize Sortable for fields panel
    new Sortable(formPreview, {
        group: {
            name: 'survey-builder',
            pull: 'clone',
            put: true
        },
        animation: 150,
        sort: false,
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            // When a field is added to the preview, configure it
            const fieldType = evt.item.dataset.type;
            evt.item.remove(); // Remove the dragged element
            
            // Create a new field element
            createNewField(fieldType, evt.newIndex);
        }
    });
    
    // Field type configuration
    const fieldTemplates = {
        text: {
            html: (fieldId) => `
                <div class="form-group">
                    <label for="${fieldId}">${fieldId}</label>
                    <input type="text" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-font'
        },
        textarea: {
            html: (fieldId) => `
                <div class="form-group">
                    <label for="${fieldId}">${fieldId}</label>
                    <textarea id="${fieldId}" name="${fieldId}" rows="3"></textarea>
                </div>
            `,
            icon: 'fas fa-align-left'
        },
        radio: {
            html: (fieldId, options) => {
                let optionsHtml = '';
                if (options && options.length) {
                    optionsHtml = options.map((opt, i) => `
                        <label class="option">
                            <input type="radio" name="${fieldId}" value="${opt.trim()}">
                            ${opt.trim()}
                        </label>
                    `).join('');
                } else {
                    optionsHtml = `
                        <label class="option">
                            <input type="radio" name="${fieldId}" value="Option 1">
                            Option 1
                        </label>
                        <label class="option">
                            <input type="radio" name="${fieldId}" value="Option 2">
                            Option 2
                        </label>
                    `;
                }
                return `
                    <div class="form-group">
                        <label>${fieldId}</label>
                        <div class="options">${optionsHtml}</div>
                    </div>
                `;
            },
            icon: 'far fa-dot-circle'
        },
        checkbox: {
            html: (fieldId, options) => {
                let optionsHtml = '';
                if (options && options.length) {
                    optionsHtml = options.map((opt, i) => `
                        <label class="option">
                            <input type="checkbox" name="${fieldId}[]" value="${opt.trim()}">
                            ${opt.trim()}
                        </label>
                    `).join('');
                } else {
                    optionsHtml = `
                        <label class="option">
                            <input type="checkbox" name="${fieldId}[]" value="Option 1">
                            Option 1
                        </label>
                        <label class="option">
                            <input type="checkbox" name="${fieldId}[]" value="Option 2">
                            Option 2
                        </label>
                    `;
                }
                return `
                    <div class="form-group">
                        <label>${fieldId}</label>
                        <div class="options">${optionsHtml}</div>
                    </div>
                `;
            },
            icon: 'far fa-check-square'
        },
        select: {
            html: (fieldId, options) => {
                let optionsHtml = '';
                if (options && options.length) {
                    optionsHtml = options.map(opt => `
                        <option value="${opt.trim()}">${opt.trim()}</option>
                    `).join('');
                } else {
                    optionsHtml = `
                        <option value="Option 1">Option 1</option>
                        <option value="Option 2">Option 2</option>
                    `;
                }
                return `
                    <div class="form-group">
                        <label for="${fieldId}">${fieldId}</label>
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
            html: (fieldId) => `
                <div class="form-group">
                    <label for="${fieldId}">${fieldId}</label>
                    <input type="number" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-hashtag'
        },
        date: {
            html: (fieldId) => `
                <div class="form-group">
                    <label for="${fieldId}">${fieldId}</label>
                    <input type="date" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'far fa-calendar-alt'
        },
        rating: {
            html: (fieldId) => `
                <div class="form-group">
                    <label>${fieldId}</label>
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
            html: (fieldId) => `
                <div class="form-group">
                    <label for="${fieldId}">${fieldId}</label>
                    <input type="file" id="${fieldId}" name="${fieldId}">
                </div>
            `,
            icon: 'fas fa-file-upload'
        }
    };
    
    // Create a new field in the preview
    function createNewField(type, position) {
        fieldCounter++;
        const fieldId = `field_${fieldCounter}`;
        
        // Show configuration modal
        currentField = {
            type: type,
            id: fieldId,
            label: `${type.charAt(0).toUpperCase() + type.slice(1)} Field`,
            name: fieldId,
            required: true,
            order: position,
            options: null,
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
        document.getElementById('field-order').value = currentField.order;
        
        // Show/hide options based on field type
        const optionsContainer = document.getElementById('options-container');
        if (['radio', 'checkbox', 'select'].includes(currentField.type)) {
            optionsContainer.style.display = 'block';
            document.getElementById('field-options').value = currentField.options ? 
                currentField.options.join('\n') : 'Option 1\nOption 2';
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
        currentField.order = document.getElementById('field-order').value;
        
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
        
        // Add field HTML
        const template = fieldTemplates[currentField.type];
        fieldDiv.innerHTML = template.html(currentField.name, currentField.options);
        
        // Add field header with actions
        const fieldHeader = document.createElement('div');
        fieldHeader.className = 'field-header';
        fieldHeader.innerHTML = `
            <div class="field-title">
                <i class="${template.icon}"></i>
                ${currentField.label} (${currentField.name})
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
        `;
        
        fieldDiv.insertBefore(fieldHeader, fieldDiv.firstChild);
        
        // Add to preview at correct position
        const existingFields = formPreview.querySelectorAll('.form-field');
        if (existingFields.length > currentField.order) {
            formPreview.insertBefore(fieldDiv, existingFields[currentField.order]);
        } else {
            formPreview.appendChild(fieldDiv);
        }
        
        // Add event listeners for edit/delete
        fieldDiv.querySelector('.edit-field').addEventListener('click', function() {
            editField(fieldDiv);
        });
        
        fieldDiv.querySelector('.delete-field').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this field?')) {
                fieldDiv.remove();
                updateFieldsData();
            }
        });
        
        // Make the field draggable for reordering
        new Sortable(fieldDiv, {
            group: 'survey-builder',
            handle: '.field-title',
            animation: 150,
            onEnd: function() {
                updateFieldsData();
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
            label: fieldDiv.querySelector('.field-title').textContent.trim().split(' (')[0],
            name: fieldDiv.dataset.fieldName || fieldDiv.querySelector('input, select, textarea').name,
            required: fieldDiv.querySelector('.required') !== null,
            order: Array.from(formPreview.querySelectorAll('.form-field')).indexOf(fieldDiv),
            options: null,
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
                label: fieldEl.querySelector('.field-title').textContent.trim().split(' (')[0],
                name: fieldEl.querySelector('input, select, textarea').name,
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