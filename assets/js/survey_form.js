document.addEventListener('DOMContentLoaded', function() {
    // Add new field
    const addFieldBtn = document.getElementById('add-field');
    const fieldsContainer = document.getElementById('survey-fields');
    const fieldTemplate = document.getElementById('field-template');
    
    if (addFieldBtn && fieldsContainer && fieldTemplate) {
        addFieldBtn.addEventListener('click', function() {
            const newField = fieldTemplate.content.cloneNode(true);
            const fieldCards = fieldsContainer.querySelectorAll('.field-card');
            const newOrder = fieldCards.length + 1;
            
            // Set default order value
            newField.querySelector('input[name*="[order]"]').value = newOrder;
            
            fieldsContainer.appendChild(newField);
            
            // Scroll to the new field
            fieldsContainer.lastElementChild.scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    // Remove field
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-field')) {
            const fieldCard = e.target.closest('.field-card');
            if (fieldCard && confirm('Are you sure you want to remove this question?')) {
                fieldCard.remove();
                
                // Update field orders
                const fieldCards = fieldsContainer.querySelectorAll('.field-card');
                fieldCards.forEach((card, index) => {
                    const orderInput = card.querySelector('input[name*="[order]"]');
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                    const orderSpan = card.querySelector('.field-order');
                    if (orderSpan) {
                        orderSpan.textContent = index + 1;
                    }
                });
            }
        }
    });
    
    // Show/hide options based on field type
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('field-type')) {
            const fieldCard = e.target.closest('.field-card');
            if (fieldCard) {
                const optionsGroup = fieldCard.querySelector('.options-group');
                if (optionsGroup) {
                    const showOptions = ['radio', 'checkbox', 'select'].includes(e.target.value);
                    optionsGroup.style.display = showOptions ? 'block' : 'none';
                    
                    if (showOptions) {
                        const textarea = optionsGroup.querySelector('textarea');
                        if (textarea && !textarea.value) {
                            textarea.value = "Option 1\nOption 2\nOption 3";
                        }
                    }
                }
            }
        }
    });
    
    // Initialize options groups for existing fields
    document.querySelectorAll('.field-type').forEach(select => {
        const fieldCard = select.closest('.field-card');
        if (fieldCard) {
            const optionsGroup = fieldCard.querySelector('.options-group');
            if (optionsGroup) {
                const showOptions = ['radio', 'checkbox', 'select'].includes(select.value);
                optionsGroup.style.display = showOptions ? 'block' : 'none';
            }
        }
    });
});