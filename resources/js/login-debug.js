/**
 * Login Debug Console Logger
 * Logs login-related events to browser console for debugging
 */

(function() {
    'use strict';

    const DEBUG_PREFIX = '[LOGIN DEBUG]';
    
    // Log initial page load
    console.log(`${DEBUG_PREFIX} Page loaded`, {
        url: window.location.href,
        pathname: window.location.pathname,
        cookies: document.cookie,
        sessionStorage: Object.keys(sessionStorage),
        localStorage: Object.keys(localStorage),
    });

    // Monitor form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM' && (form.action.includes('/login') || form.querySelector('input[type="email"]'))) {
            const email = form.querySelector('input[type="email"]')?.value;
            const hasPassword = !!form.querySelector('input[type="password"]')?.value;
            
            console.log(`${DEBUG_PREFIX} Form submitted`, {
                email: email ? email.substring(0, 3) + '***' : 'none',
                hasPassword: hasPassword,
                formAction: form.action,
                method: form.method,
            });
        }
    });

    // Monitor Livewire events
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('request', ({ component, payload, respond }) => {
            if (component?.name?.includes('Login') || payload?.fingerprint?.name?.includes('Login')) {
                console.log(`${DEBUG_PREFIX} Livewire request`, {
                    component: component?.name,
                    method: payload?.serverMemo?.data?.method,
                    url: payload?.fingerprint?.path,
                });
            }
        });

        Livewire.hook('commit', ({ component, commit, respond }) => {
            if (component?.name?.includes('Login')) {
                console.log(`${DEBUG_PREFIX} Livewire commit`, {
                    component: component?.name,
                    effects: commit?.effects,
                });
            }
        });

        Livewire.hook('message', ({ component, message, respond }) => {
            if (component?.name?.includes('Login')) {
                console.log(`${DEBUG_PREFIX} Livewire message`, {
                    component: component?.name,
                    message: message,
                });
            }
        });
    }

    // Monitor redirects
    let lastUrl = window.location.href;
    setInterval(() => {
        if (window.location.href !== lastUrl) {
            console.log(`${DEBUG_PREFIX} Page redirected`, {
                from: lastUrl,
                to: window.location.href,
            });
            lastUrl = window.location.href;
        }
    }, 100);

    // Monitor authentication state changes
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph', ({ component, el }) => {
            if (el && (el.textContent?.includes('Login') || el.querySelector('form[wire\\:submit="authenticate"]'))) {
                console.log(`${DEBUG_PREFIX} Login form rendered/updated`);
            }
        });
    }

    // Log cookie changes
    let lastCookies = document.cookie;
    setInterval(() => {
        if (document.cookie !== lastCookies) {
            console.log(`${DEBUG_PREFIX} Cookies changed`, {
                old: lastCookies,
                new: document.cookie,
            });
            lastCookies = document.cookie;
        }
    }, 500);

    // Monitor network requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        if (typeof url === 'string' && (url.includes('/login') || url.includes('/livewire'))) {
            console.log(`${DEBUG_PREFIX} Fetch request`, {
                url: url,
                method: args[1]?.method || 'GET',
            });
        }
        return originalFetch.apply(this, args).then(response => {
            if (typeof url === 'string' && (url.includes('/login') || url.includes('/livewire'))) {
                console.log(`${DEBUG_PREFIX} Fetch response`, {
                    url: url,
                    status: response.status,
                    statusText: response.statusText,
                });
            }
            return response;
        });
    };

    console.log(`${DEBUG_PREFIX} Debug logger initialized`);
})();
