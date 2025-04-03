document.addEventListener('DOMContentLoaded', function() {
    const fieldsContainer = document.getElementById('fields-container');
    const addFieldBtn = document.getElementById('add-field');
    const fieldTemplate = document.getElementById('field-template');
    const form = document.getElementById('survey-form');
    
    // Initialize date pickers
    flatpickr('#starts_at', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        minDate: 'today'
    });
    
    flatpickr('#ends_at', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        minDate: 'today'
    });
    
    // Add new field
    addFieldBtn.addEventListener('click', function() {
        const index = fieldsContainer.children.length;
        const newField = createField(index);
        fieldsContainer.appendChild(newField);
        updateFieldIndexes();
    });
    
    // Create a new field from template
    function createField(index) {
        const newField = fieldTemplate.cloneNode(true);
        newField.style.display = '';
        newField.dataset.index = index;
        
        // Update all names and IDs with the new index
        const elements = newField.querySelectorAll('[name], [id]');
        elements.forEach(el => {
            if (el.name) el.name = el.name.replace('__INDEX__', index);
            if (el.id) el.id = el.id.replace('__INDEX__', index);
        });
        
        // Add event listeners
        addFieldEventListeners(newField);
        
        return newField;
    }
    
    // Add event listeners to a field
    function addFieldEventListeners(field) {
        // Delete button
        const deleteBtn = field.querySelector('.btn-delete-field');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this question?')) {
                    field.remove();
                    updateFieldIndexes();
                }
            });
        }
        
        // Move up button
        const moveUpBtn = field.querySelector('.btn-move-up');
        if (moveUpBtn) {
            moveUpBtn.addEventListener('click', function() {
                const prev = field.previousElementSibling;
                if (prev) {
                    fieldsContainer.insertBefore(field, prev);
                    updateFieldIndexes();
                }
            });
        }
        
        // Move down button
        const moveDownBtn = field.querySelector('.btn-move-down');
        if (moveDownBtn) {
            moveDownBtn.addEventListener('click', function() {
                const next = field.nextElementSibling;
                if (next) {
                    fieldsContainer.insertBefore(next, field);
                    updateFieldIndexes();
                }
            });
        }
        
        // Field type change
        const typeSelect = field.querySelector('.field-type-select');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                const optionsGroup = field.querySelector('.options-group');
                const icon = field.querySelector('.field-type i');
                const typeLabel = field.querySelector('.field-type');
                
                // Update icon
                const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                const type = selectedOption.value;
                const iconClass = getIconForType(type);
                icon.className = `fas ${iconClass}`;
                
                // Show/hide options
                if (['radio', 'checkbox', 'select', 'rating'].includes(type)) {
                    optionsGroup.style.display = 'block';
                } else {
                    optionsGroup.style.display = 'none';
                }
            });
        }
    }
    
    // Get icon class for field type
    function getIconForType(type) {
        const icons = {
            'text': 'fa-font',
            'textarea': 'fa-align-left',
            'radio': 'fa-dot-circle',
            'checkbox': 'fa-check-square',
            'select': 'fa-caret-square-down',
            'number': 'fa-hashtag',
            'date': 'fa-calendar-alt',
            'rating': 'fa-star',
            'file': 'fa-file-upload'
        };
        return icons[type] || 'fa-font';
    }
    
    // Update field indexes after reordering
    function updateFieldIndexes() {
        const fields = fieldsContainer.querySelectorAll('.field-card');
        fields.forEach((field, index) => {
            field.dataset.index = index;
            
            // Update order input
            const orderInput = field.querySelector('input[name$="[order]"]');
            if (orderInput) {
                orderInput.value = index;
                orderInput.name = `fields[${index}][order]`;
            }
            
            // Update other inputs
            const inputs = field.querySelectorAll('[name^="fields["]');
            inputs.forEach(input => {
                const name = input.name.replace(/fields\[\d+\]/, `fields[${index}]`);
                input.name = name;
            });
        });
    }
    
    // Initialize drag and drop
    let draggedItem = null;
    
    fieldsContainer.addEventListener('dragstart', function(e) {
        if (e.target.classList.contains('field-card')) {
            draggedItem = e.target;
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.innerHTML);
        }
    });
    
    fieldsContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        
        const targetItem = e.target.closest('.field-card');
        if (targetItem && targetItem !== draggedItem) {
            const rect = targetItem.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            
            if (e.clientY < midpoint) {
                fieldsContainer.insertBefore(draggedItem, targetItem);
            } else {
                fieldsContainer.insertBefore(draggedItem, targetItem.nextSibling);
            }
        }
    });
    
    fieldsContainer.addEventListener('dragend', function(e) {
        if (e.target.classList.contains('field-card')) {
            e.target.classList.remove('dragging');
            updateFieldIndexes();
        }
    });
    
    fieldsContainer.addEventListener('drop', function(e) {
        e.preventDefault();
    });
    
    // Make fields draggable
    const fields = fieldsContainer.querySelectorAll('.field-card');
    fields.forEach(field => {
        field.draggable = true;
        addFieldEventListeners(field);
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        // Check for at least one role selected
        const roleCheckboxes = form.querySelectorAll('input[name="roles[]"]:checked');
        if (roleCheckboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one role that can access this survey');
            return;
        }
        
        // Check for at least one field
        const fields = fieldsContainer.querySelectorAll('.field-card');
        if (fields.length === 0) {
            e.preventDefault();
            alert('Please add at least one question to the survey');
            return;
        }
        
        // Validate field options for radio/checkbox/select
        let isValid = true;
        fields.forEach(field => {
            const typeSelect = field.querySelector('.field-type-select');
            const optionsTextarea = field.querySelector('textarea[name$="[options]"]');
            
            if (typeSelect && optionsTextarea && optionsTextarea.style.display !== 'none') {
                const options = optionsTextarea.value.trim().split('\n').filter(opt => opt.trim() !== '');
                if (options.length === 0) {
                    isValid = false;
                    optionsTextarea.focus();
                    alert('Please provide at least one option for this question');
                    return false;
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Initialize field type change handlers for existing fields
    const typeSelects = document.querySelectorAll('.field-type-select');
    typeSelects.forEach(select => {
        select.dispatchEvent(new Event('change'));
    });
});