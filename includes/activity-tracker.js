
// Add to activity-tracker.js
document.addEventListener('submit', (e) => {
    const inputs = Array.from(e.target.elements).reduce((acc, el) => {
        if (el.name) acc[el.name] = el.value.substring(0, 150); // Truncate sensitive data
        return acc;
    }, {});

    sendActivity({
        action: 'form_submission',
        form_id: e.target.id || 'unknown',
        fields: Object.keys(inputs),
        field_count: Object.keys(inputs).length
    });
}, true);