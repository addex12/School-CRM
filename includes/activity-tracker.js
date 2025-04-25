(function() {
    'use strict';

    // Enhanced persistent tracking ID with school/student context
    function getTrackingId() {
        let trackingId = localStorage.getItem('student_tracker_id');
        if (!trackingId) {
            const schoolCode = window.location.hostname.split('.')[0] || 'sch';
            trackingId = `${schoolCode}_${Math.random().toString(36).substr(2, 8)}_${Date.now().toString(36)}`;
            localStorage.setItem('student_tracker_id', trackingId);
            
            // Set cookie with SameSite=None for cross-site tracking
            document.cookie = `student_tracker_id=${trackingId}; max-age=${365*24*60*60}; path=/; secure; samesite=strict`;
        }
        return trackingId;
    }

    // Enhanced device fingerprinting for mobile
    async function generateDeviceFingerprint() {
        const fingerprint = {
            // Standard browser fingerprint
            screen: `${window.screen.width}x${window.screen.height}`,
            colorDepth: window.screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            languages: navigator.languages,
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
            deviceMemory: navigator.deviceMemory || 'unknown',
            sessionStorage: !!window.sessionStorage,
            localStorage: !!window.localStorage,
            indexedDB: !!window.indexedDB,
            
            // Mobile-specific features
            touchSupport: 'ontouchstart' in window,
            maxTouchPoints: navigator.maxTouchPoints || 0,
            deviceOrientation: 'DeviceOrientationEvent' in window,
            motion: 'DeviceMotionEvent' in window,
            batteryApi: 'getBattery' in navigator,
            connection: navigator.connection ? {
                type: navigator.connection.type,
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink
            } : null
        };

        // Add canvas fingerprint
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('EDU', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('TRACK', 4, 17);
            fingerprint.canvas = canvas.toDataURL();
        } catch (e) {}

        // Add battery status (mobile)
        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                fingerprint.battery = {
                    level: battery.level,
                    charging: battery.charging,
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
            }
        } catch (e) {}

        // Add device motion/orientation (mobile)
        try {
            if ('DeviceOrientationEvent' in window) {
                fingerprint.orientation = {
                    available: true,
                    permission: await checkPermission('gyroscope')
                };
            }
            
            if ('DeviceMotionEvent' in window) {
                fingerprint.motion = {
                    available: true,
                    permission: await checkPermission('accelerometer')
                };
            }
        } catch (e) {}

        return btoa(JSON.stringify(fingerprint));
    }

    // Check permission for device sensors
    async function checkPermission(sensorType) {
        if (!navigator.permissions) return 'unknown';
        try {
            const status = await navigator.permissions.query({ name: sensorType });
            return status.state;
        } catch {
            return 'unknown';
        }
    }

    // Get geolocation (mobile)
    async function getGeolocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lon: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    });
                },
                (error) => {
                    resolve({
                        error: error.code,
                        message: error.message
                    });
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        });
    }

    // Get device sensor data (mobile)
    async function getSensorData() {
        const sensors = {};
        
        if ('DeviceOrientationEvent' in window) {
            sensors.orientation = {
                alpha: null,
                beta: null,
                gamma: null
            };
            
            window.addEventListener('deviceorientation', (event) => {
                sensors.orientation = {
                    alpha: event.alpha,
                    beta: event.beta,
                    gamma: event.gamma
                };
            });
        }
        
        if ('DeviceMotionEvent' in window) {
            sensors.motion = {
                accel: {
                    x: null,
                    y: null,
                    z: null
                },
                gyro: {
                    alpha: null,
                    beta: null,
                    gamma: null
                }
            };
            
            window.addEventListener('devicemotion', (event) => {
                sensors.motion = {
                    accel: {
                        x: event.accelerationIncludingGravity.x,
                        y: event.accelerationIncludingGravity.y,
                        z: event.accelerationIncludingGravity.z
                    },
                    gyro: {
                        alpha: event.rotationRate.alpha,
                        beta: event.rotationRate.beta,
                        gamma: event.rotationRate.gamma
                    }
                };
            });
        }
        
        return sensors;
    }

    // Enhanced activity tracking with mobile support
    async function sendActivity(data) {
        try {
            const trackingId = getTrackingId();
            const fingerprint = await generateDeviceFingerprint();
            const geolocation = await getGeolocation();
            const sensors = await getSensorData();
            
            const payload = {
                ...data,
                page: window.location.pathname,
                timestamp: new Date().toISOString(),
                tracking_id: trackingId,
                fingerprint: fingerprint,
                userAgent: navigator.userAgent,
                screenResolution: `${window.screen.width}x${window.screen.height}`,
                viewportSize: `${window.innerWidth}x${window.innerHeight}`,
                geolocation: geolocation,
                sensor: sensors
            };

            // Use Beacon for reliability, especially on mobile
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(payload)], {type: 'application/json'});
                navigator.sendBeacon('track_activity.php', blob);
            } else {
                fetch('track_activity.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload),
                    keepalive: true
                }).catch(e => console.error('Tracking error:', e));
            }
        } catch (error) {
            console.error('Activity tracking failed:', error);
        }
    }

    // Track initial page load with device info
    sendActivity({
        action: 'page_load',
        referrer: document.referrer,
        cookiesEnabled: navigator.cookieEnabled,
        networkType: navigator.connection?.effectiveType || 'unknown'
    });

    // Enhanced click tracking with touch support
    document.addEventListener('click', function(e) {
        sendActivity({
            action: 'click',
            target: e.target.tagName,
            id: e.target.id || null,
            class: e.target.className || null,
            text: (e.target.innerText || e.target.value || '').substring(0, 200),
            coordinates: {
                x: e.clientX,
                y: e.clientY,
                pageX: e.pageX,
                pageY: e.pageY
            },
            isTouch: ('ontouchstart' in window) && e.pointerType === 'touch'
        });
    }, true);

    // Track form interactions
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

    // Track input changes with enhanced mobile support
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
                    value: (e.target.value || '').substring(0, 200),
                    inputMethod: e.inputType || 'keyboard' // 'keyboard' or 'speech' for mobile
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

    // Track page visibility changes (important for mobile)
    document.addEventListener('visibilitychange', function() {
        sendActivity({
            action: 'visibility_change',
            isVisible: !document.hidden,
            timestamp: new Date().toISOString()
        });
    });

    // Track screen orientation changes (mobile)
    window.addEventListener('orientationchange', function() {
        sendActivity({
            action: 'orientation_change',
            orientation: window.orientation,
            screenWidth: window.screen.width,
            screenHeight: window.screen.height
        });
    });

    // Track network status changes (mobile)
    if ('connection' in navigator) {
        navigator.connection.addEventListener('change', function() {
            sendActivity({
                action: 'network_change',
                type: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                saveData: navigator.connection.saveData
            });
        });
    }

    // Track page exit with additional context
    window.addEventListener('beforeunload', function() {
        sendActivity({
            action: 'page_exit',
            timeOnPage: performance.now() / 1000,
            scrollDepth: getScrollDepth()
        });
    });

    // Track app install prompt (PWA)
    window.addEventListener('beforeinstallprompt', function(e) {
        sendActivity({
            action: 'pwa_install_prompt',
            platforms: e.platforms,
            userChoice: e.userChoice
        });
    });

    // Heartbeat with additional context
    setInterval(() => {
        sendActivity({
            action: 'heartbeat',
            timeOnPage: performance.now() / 1000,
            batteryLevel: navigator.getBattery ? navigator.getBattery().then(b => b.level) : null,
            networkType: navigator.connection?.effectiveType || 'unknown'
        });
    }, 300000); // Every 5 minutes

    // Helper function to get scroll depth
    function getScrollDepth() {
        const scrollPosition = window.scrollY;
        const pageHeight = document.documentElement.scrollHeight;
        const viewportHeight = window.innerHeight;
        return Math.round((scrollPosition + viewportHeight) / pageHeight * 100);
    }

    // Initialize sensor tracking for mobile
    if ('DeviceMotionEvent' in window || 'DeviceOrientationEvent' in window) {
        sendActivity({
            action: 'device_sensor_init',
            motion: 'DeviceMotionEvent' in window,
            orientation: 'DeviceOrientationEvent' in window
        });
    }
})();