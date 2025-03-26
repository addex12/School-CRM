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

    // ... rest of the enhanced builder implementation ...
}