document.addEventListener('DOMContentLoaded', function() {
    // Initialize the builder with these enhancements
    const builder = new SurveyBuilder({
        fieldTypes: <?php echo json_encode($fieldTypes); ?>,
        validationRules: {
            text: ['required', 'minLength', 'maxLength', 'regex'],
            number: ['required', 'min', 'max'],
            date: ['required', 'minDate', 'maxDate'],
            file: ['required', 'fileTypes', 'maxSize']
        }
    });

    // Add logic builder functionality
    document.getElementById('add-logic').addEventListener('click', function() {
        builder.showLogicModal();
    });
});

class SurveyBuilder {
    constructor(config) {
        this.config = config;
        this.initDragDrop();
        this.initModals();
        this.initPreview();
    }

    initDragDrop() {
        // Enhanced drag-drop initialization with touch support
        new Sortable(this.formPreview, {
            group: 'survey-builder',
            animation: 150,
            handle: '.field-handle',
            ghostClass: 'sortable-ghost',
            onAdd: (evt) => this.handleFieldAdd(evt),
            onUpdate: () => this.updateFieldsData()
        });
    }

    handleFieldAdd(evt) {
        const fieldType = evt.item.dataset.type;
        this.currentField = this.createFieldConfig(fieldType);
        this.showFieldModal();
    }

    createFieldConfig(type) {
        return {
            id: `field_${Date.now()}`,
            type: type,
            label: `${type.charAt(0).toUpperCase() + type.slice(1)} Field`,
            name: `field_${Math.random().toString(36).substr(2, 9)}`,
            placeholder: '',
            required: true,
            options: type === 'rating' ? 5 : null,
            validation: {},
            logic: []
        };
    }

    showLogicModal() {
        // Implement logic builder UI
        const fields = this.getCurrentFields();
        // ... logic builder implementation ...
    }

    updateFieldsData() {
        const fields = Array.from(this.formPreview.querySelectorAll('.form-field')).map(fieldEl => {
            return {
                type: fieldEl.dataset.type,
                label: fieldEl.querySelector('.field-label').textContent,
                name: fieldEl.dataset.name,
                placeholder: fieldEl.querySelector('input')?.placeholder || '',
                required: fieldEl.classList.contains('required'),
                options: this.getFieldOptions(fieldEl),
                validation: this.getFieldValidation(fieldEl)
            };
        });
        
        this.fieldsData.value = JSON.stringify(fields);
    }

   //Lets enhance the builder implementation ...
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