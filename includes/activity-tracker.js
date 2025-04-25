// Enhanced activity-tracker.js
(function() {
    // Enhanced tracking ID with mobile support
    function getTrackingId() {
        let trackingId = localStorage.getItem('persistent_tracking_id') || 
                        sessionStorage.getItem('session_tracking_id');
        
        if (!trackingId) {
            trackingId = 'trk_' + Math.random().toString(36).substr(2, 16) + 
                        '_' + Date.now().toString(36);
            
            // Store in both storage for better persistence
            try {
                localStorage.setItem('persistent_tracking_id', trackingId);
                sessionStorage.setItem('session_tracking_id', trackingId);
            } catch (e) {
                // Handle storage quota exceeded
                trackingId = 'session_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem('session_tracking_id', trackingId);
            }
            
            // Set cookie with SameSite=None for cross-app tracking
            document.cookie = `persistent_tracking_id=${trackingId}; max-age=${365*24*60*60}; path=/; secure; samesite=strict`;
        }
        return trackingId;
    }

    // Enhanced device fingerprint with mobile capabilities
    function generateDeviceFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Enhanced canvas fingerprinting
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('Fingerprint', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('Fingerprint', 4, 17);
        
        // Mobile-specific detection
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const touchSupport = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        const fingerprint = {
            canvas: canvas.toDataURL(),
            screen: `${window.screen.width}x${window.screen.height}`,
            colorDepth: window.screen.colorDepth,
            pixelRatio: window.devicePixelRatio || 1,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            languages: navigator.languages,
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
            deviceMemory: navigator.deviceMemory || 'unknown',
            touchSupport: touchSupport,
            isMobile: isMobile,
            mobileFeatures: {
                orientation: window.screen.orientation ? window.screen.orientation.type : 'unknown',
                vibration: 'vibrate' in navigator,
                geolocation: 'geolocation' in navigator,
                battery: 'getBattery' in navigator,
                connection: navigator.connection ? {
                    type: navigator.connection.type,
                    effectiveType: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink
                } : null
            }
        };
        
        return btoa(JSON.stringify(fingerprint));
    }

    // Get battery status (mobile only)
    async function getBatteryStatus() {
        if ('getBattery' in navigator) {
            try {
                const battery = await navigator.getBattery();
                return {
                    level: battery.level,
                    charging: battery.charging,
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    // Enhanced activity tracking with mobile events
    async function sendActivity(data) {
        const trackingId = getTrackingId();
        const fingerprint = generateDeviceFingerprint();
        const battery = await getBatteryStatus();
        
        const payload = {
            ...data,
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
            tracking_id: trackingId,
            fingerprint: fingerprint,
            userAgent: navigator.userAgent,
            device: {
                screen: `${window.screen.width}x${window.screen.height}`,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                isMobile: /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent),
                battery: battery,
                connection: navigator.connection ? {
                    type: navigator.connection.type,
                    effectiveType: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink
                } : null
            },
            app_state: document.visibilityState === 'visible' ? 'foreground' : 'background'
        };

        // Enhanced beacon with mobile support
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify(payload)], {type: 'application/json'});
            navigator.sendBeacon('track_activity.php', blob);
        } else {
            fetch('track_activity.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
                keepalive: true
            });
        }
    }

    // Track mobile-specific events
    function trackMobileEvents() {
        // Screen orientation changes
        if (window.screen.orientation) {
            window.screen.orientation.addEventListener('change', () => {
                sendActivity({
                    action: 'orientation_change',
                    orientation: window.screen.orientation.type
                });
            });
        }

        // Battery status changes
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                battery.addEventListener('levelchange', () => {
                    sendActivity({
                        action: 'battery_change',
                        level: battery.level,
                        charging: battery.charging
                    });
                });
                
                battery.addEventListener('chargingchange', () => {
                    sendActivity({
                        action: 'charging_change',
                        charging: battery.charging
                    });
                });
            });
        }

        // Network connection changes
        if (navigator.connection) {
            navigator.connection.addEventListener('change', () => {
                sendActivity({
                    action: 'network_change',
                    type: navigator.connection.type,
                    effectiveType: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink
                });
            });
        }

        // App visibility changes
        document.addEventListener('visibilitychange', () => {
            sendActivity({
                action: 'app_visibility',
                state: document.visibilityState
            });
        });

        // Touch events
        document.addEventListener('touchstart', (e) => {
            sendActivity({
                action: 'touch_event',
                type: 'start',
                touches: e.touches.length,
                target: e.target.tagName,
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            });
        }, {passive: true});

        // Device motion/orientation
        if (window.DeviceOrientationEvent) {
            window.addEventListener('deviceorientation', (e) => {
                sendActivity({
                    action: 'device_orientation',
                    alpha: e.alpha,
                    beta: e.beta,
                    gamma: e.gamma
                });
            });
        }

        if (window.DeviceMotionEvent) {
            window.addEventListener('devicemotion', (e) => {
                sendActivity({
                    action: 'device_motion',
                    acceleration: e.acceleration,
                    accelerationIncludingGravity: e.accelerationIncludingGravity,
                    rotationRate: e.rotationRate
                });
            });
        }
    }

    // Initialize tracking
    (async function init() {
        // Track initial page load with device capabilities
        await sendActivity({
            action: 'app_launch',
            referrer: document.referrer,
            cookiesEnabled: navigator.cookieEnabled,
            localStorage: !!window.localStorage,
            sessionStorage: !!window.sessionStorage,
            installedApps: await detectInstalledApps()
        });

        // Set up mobile event tracking
        trackMobileEvents();

        // Existing event tracking (clicks, forms, etc.)
        document.addEventListener('click', (e) => {
            sendActivity({
                action: 'click',
                target: e.target.tagName,
                id: e.target.id || null,
                class: e.target.className || null,
                text: (e.target.innerText || e.target.value || '').substring(0, 200),
                x: e.clientX,
                y: e.clientY
            });
        }, true);

        // ... (rest of your existing event tracking)

        // Enhanced page exit tracking
        window.addEventListener('pagehide', () => {
            sendActivity({
                action: 'app_background',
                timeOnPage: performance.now() / 1000
            });
        });

        window.addEventListener('beforeunload', () => {
            navigator.sendBeacon && navigator.sendBeacon('track_activity.php', 
                JSON.stringify({
                    action: 'app_exit',
                    timeOnPage: performance.now() / 1000
                })
            );
        });
    })();

    // Detect potentially installed apps (note: limited capability)
    async function detectInstalledApps() {
        const apps = [];
        const schemes = {
            'facebook': 'fb://',
            'whatsapp': 'whatsapp://',
            'telegram': 'tg://',
            'twitter': 'twitter://'
        };

        // This technique has limitations and may not work on all browsers
        for (const [app, scheme] of Object.entries(schemes)) {
            try {
                const iframe = document.createElement('iframe');
                iframe.src = scheme;
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
                
                await new Promise(resolve => setTimeout(resolve, 100));
                
                if (document.hidden || document.visibilityState !== 'visible') {
                    apps.push(app);
                }
                
                document.body.removeChild(iframe);
            } catch (e) {
                continue;
            }
        }
        
        return apps;
    }
})();