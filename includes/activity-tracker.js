(function() {
    // Helper to get element info
    function getElementInfo(el) {
        return {
            tag: el.tagName,
            id: el.id || '',
            class: el.className || '',
            text: (el.innerText || el.value || '').substring(0, 200), // limit text length
            href: el.href || ''
        };
    }

    // Send activity to server
    function sendActivity(data) {
        data.page = window.location.pathname;
        fetch('/School-CRM/track_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
    }

    // Clicks
    document.addEventListener('click', function(e) {
        let el = e.target;
        sendActivity({
            action: 'click',
            ...getElementInfo(el)
        });
    }, true);

    // Inputs (typing, changes)
    document.addEventListener('input', function(e) {
        let el = e.target;
        sendActivity({
            action: 'input',
            ...getElementInfo(el)
        });
    }, true);

    // Copy
    document.addEventListener('copy', function(e) {
        let el = e.target;
        sendActivity({
            action: 'copy',
            ...getElementInfo(el)
        });
    }, true);

    // Paste
    document.addEventListener('paste', function(e) {
        let el = e.target;
        sendActivity({
            action: 'paste',
            ...getElementInfo(el)
        });
    }, true);

    // Cut
    document.addEventListener('cut', function(e) {
        let el = e.target;
        sendActivity({
            action: 'cut',
            ...getElementInfo(el)
        });
    }, true);

    // Focus
    document.addEventListener('focus', function(e) {
        let el = e.target;
        sendActivity({
            action: 'focus',
            ...getElementInfo(el)
        });
    }, true);

    // Blur
    document.addEventListener('blur', function(e) {
        let el = e.target;
        sendActivity({
            action: 'blur',
            ...getElementInfo(el)
        });
    }, true);
})();
