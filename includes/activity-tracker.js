(function() {
    // Generate or retrieve persistent tracking ID
    function getTrackingId() {
        let trackingId = localStorage.getItem('persistent_tracking_id');
        if (!trackingId) {
            trackingId = 'track_' + Math.random().toString(36).substr(2, 16) + 
                        Date.now().toString(36);
            localStorage.setItem('persistent_tracking_id', trackingId);
            
            // Also set cookie for server-side access
            document.cookie = `persistent_tracking_id=${trackingId}; max-age=${365*24*60*60}; path=/; secure; samesite=strict`;
        }
        return trackingId;
    }

    // Generate browser fingerprint
    function generateFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('Fingerprint', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('Fingerprint', 4, 17);
        
        const fingerprint = {
            canvas: canvas.toDataURL(),
            screen: `${window.screen.width}x${window.screen.height}`,
            colorDepth: window.screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            languages: navigator.languages,
            platform: navigator.platform,
            touchSupport: 'ontouchstart' in window,
            hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
            deviceMemory: navigator.deviceMemory || 'unknown',
            sessionStorage: !!window.sessionStorage,
            localStorage: !!window.localStorage,
            indexedDB: !!window.indexedDB
        };
        
        return btoa(JSON.stringify(fingerprint));
    }

    // Send activity to server
    function sendActivity(data) {
        const trackingId = getTrackingId();
        const fingerprint = generateFingerprint();
        
        const payload = {
            ...data,
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
            tracking_id: trackingId,
            fingerprint: fingerprint,
            userAgent: navigator.userAgent,
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            viewportSize: `${window.innerWidth}x${window.innerHeight}`
        };

        // Use Beacon API when possible for reliability
        if (navigator.sendBeacon) {
            navigator.sendBeacon('track_activity.php', JSON.stringify(payload));
        } else {
            fetch('track_activity.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
                keepalive: true
            });
        }
    }

    // Track all page views
    sendActivity({
        action: 'page_view',
        referrer: document.referrer,
        cookiesEnabled: navigator.cookieEnabled
    });

    // Track clicks with coordinates
    document.addEventListener('click', function(e) {
        sendActivity({
            action: 'click',
            target: e.target.tagName,
            id: e.target.id || null,
            class: e.target.className || null,
            text: (e.target.innerText || e.target.value || '').substring(0, 200),
            x: e.clientX,
            y: e.clientY,
            pageX: e.pageX,
            pageY: e.pageY
        });
    }, true);

    // Track form submissions (excluding passwords)
    document.addEventListener('submit', function(e) {
        const formData = {};
        Array.from(e.target.elements).forEach(el => {
            if (el.name && el.type !== 'password') {
                formData[el.name] = el.value || '';
            }
        });
        
        sendActivity({
            action: 'form_submit',
            formId: e.target.id || null,
            formAction: e.target.action || null,
            formMethod: e.target.method || 'GET',
            formData: formData
        });
    });

    // Track input changes with debouncing
    const inputChangeTracker = (function() {
        const timers = {};
        return function(e) {
            if (e.target.type === 'password') return;
            
            clearTimeout(timers[e.target.name]);
            timers[e.target.name] = setTimeout(() => {
                sendActivity({
                    action: 'input_change',
                    element: e.target.tagName,
                    name: e.target.name || null,
                    type: e.target.type || 'text',
                    value: (e.target.value || '').substring(0, 200)
                });
            }, 500);
        };
    })();
    
    document.addEventListener('input', inputChangeTracker);

    // Track copy/paste actions
    ['copy', 'paste'].forEach(event => {
        document.addEventListener(event, function(e) {
            const text = event === 'paste' 
                ? (e.clipboardData || window.clipboardData).getData('text')
                : window.getSelection().toString();
                
            sendActivity({
                action: event,
                text: text.substring(0, 200),
                target: e.target.tagName,
                targetId: e.target.id || null
            });
        });
    });

    // Track page exit events
    window.addEventListener('beforeunload', function() {
        sendActivity({
            action: 'page_exit',
            timeOnPage: performance.now() / 1000
        });
    });

    // Track navigation
    window.addEventListener('popstate', function() {
        sendActivity({
            action: 'navigation',
            from: document.referrer,
            to: window.location.href
        });
    });

    // Heartbeat for session tracking
    setInterval(() => {
        sendActivity({
            action: 'heartbeat',
            timeOnPage: performance.now() / 1000
        });
    }, 300000); // Every 5 minutes
})();