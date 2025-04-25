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
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced activity tracking - always active
    function sendActivity(action, details = {}) {
        const payload = Object.assign({
            action: action,
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            viewportSize: `${window.innerWidth}x${window.innerHeight}`
        }, details);
        
        // Send to both endpoints for redundancy
        fetch('track_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(e => console.error('Tracking error:', e));
        
        fetch('log_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(e => console.error('Log error:', e));
    }

    // Track initial page load
    sendActivity('page_load', {
        referrer: document.referrer,
        cookiesEnabled: navigator.cookieEnabled,
        localStorage: !!window.localStorage,
        sessionStorage: !!window.sessionStorage
    });

    // Track all clicks
    document.addEventListener('click', function(e) {
        const target = e.target;
        sendActivity('click', {
            tag: target.tagName,
            id: target.id || null,
            class: target.className || null,
            text: (target.innerText || target.value || '').substring(0, 500),
            value: target.value || null,
            href: target.href || null,
            x: e.clientX,
            y: e.clientY
        });
    }, true); // Use capture phase to get all clicks

    // Track form interactions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const formData = {};
        Array.from(form.elements).forEach(el => {
            if (el.name) {
                formData[el.name] = el.value || '';
            }
        });
        
        sendActivity('form_submit', {
            formId: form.id || null,
            formClass: form.className || null,
            formAction: form.action || null,
            formMethod: form.method || 'GET',
            formData: JSON.stringify(formData)
        });
    });

    // Track input changes (with throttling)
    const inputTracker = (function() {
        const trackedInputs = new WeakMap();
        return function(e) {
            const target = e.target;
            if ((target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') && 
                !trackedInputs.has(target)) {
                trackedInputs.set(target, true);
                
                const prevValue = target.value || '';
                target.addEventListener('change', function() {
                    sendActivity('input_change', {
                        tag: target.tagName,
                        id: target.id || null,
                        class: target.className || null,
                        name: target.name || null,
                        type: target.type || null,
                        previousValue: prevValue,
                        newValue: target.value || ''
                    });
                });
            }
        };
    })();
    
    document.addEventListener('focus', inputTracker, true);

    // Track copy, paste, cut
    ['copy', 'paste', 'cut'].forEach(event => {
        document.addEventListener(event, function(e) {
            const text = (event === 'paste') ? 
                (e.clipboardData || window.clipboardData).getData('text') :
                window.getSelection().toString();
                
            sendActivity(event, {
                text: text.substring(0, 1000),
                targetId: e.target.id || null,
                targetClass: e.target.className || null
            });
        });
    });

    // Track tab/window visibility changes
    document.addEventListener('visibilitychange', function() {
        sendActivity('visibility_change', {
            isVisible: !document.hidden,
            timeHidden: document.hidden ? new Date().toISOString() : null
        });
    });

    // Track beforeunload (page exit)
    window.addEventListener('beforeunload', function() {
        navigator.sendBeacon('track_activity.php', JSON.stringify({
            action: 'page_exit',
            page: window.location.pathname,
            timestamp: new Date().toISOString()
        }));
    });

    // Detect password manager autofill
    setInterval(function() {
        document.querySelectorAll('input[type="password"]').forEach(pwd => {
            if (pwd.value && !pwd.hasAttribute('data-tracked-autofill')) {
                pwd.setAttribute('data-tracked-autofill', 'true');
                sendActivity('password_autofill', {
                    fieldId: pwd.id || null,
                    fieldName: pwd.name || null
                });
            }
        });
    }, 1000);

    // Track key events (with filtering for sensitive inputs)
    document.addEventListener('keydown', function(e) {
        const target = e.target;
        const isSensitive = target.type === 'password' || 
                          target.type === 'email' || 
                          target.type === 'tel' || 
                          target.type === 'number';
        
        if (!isSensitive) {
            sendActivity('keydown', {
                key: e.key,
                code: e.code,
                targetTag: target.tagName,
                targetId: target.id || null
            });
        }
    });
});
