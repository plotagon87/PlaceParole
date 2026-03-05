// assets/js/app.js
/*
 * PlaceParole — Common JavaScript Utilities
 */

/**
 * formatDate(dateString)
 * Format a date string for display (e.g., "2024-03-04 10:30:00" → "04/03/2024 10:30")
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }) + ' ' + date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * copyToClipboard(text)
 * Copy text to clipboard and show notification
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied: ' + text);
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

/**
 * showNotification(message, type)
 * Show a temporary notification
 * type: 'success' | 'error' | 'info'
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg text-white fade-in z-50`;
    
    const typeClasses = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600'
    };
    
    notification.className += ' ' + (typeClasses[type] || typeClasses.info);
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease-in-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * validateEmail(email)
 * Simple email validation
 */
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * formatPhoneNumber(phone)
 * Format phone number for Cameroon: +237 6XX XXX XXX
 */
function formatPhoneNumber(phone) {
    // Remove all non-digits
    const digits = phone.replace(/\D/g, '');
    // Cameroon format: +237 followed by rest
    if (digits.length >= 9) {
        return '+237 ' + digits.slice(-9, -5) + ' ' + digits.slice(-5);
    }
    return phone;
}

/**
 * enableFormDirtyWarning()
 * Show confirmation when user tries to leave a form with unsaved changes
 */
function enableFormDirtyWarning(formId = null) {
    const forms = formId ? [document.getElementById(formId)] : document.querySelectorAll('form');
    
    forms.forEach(form => {
        if (!form) return;
        
        let isDirty = false;
        const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                isDirty = true;
            });
        });
        
        // Clear the dirty flag when form is submitted
        form.addEventListener('submit', () => {
            isDirty = false;
        });
        
        // Warn before leaving if form is dirty
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    });
}

// Dark‑mode helpers (matching style.css section 15)
function applyTheme(theme) {
    const html = document.documentElement;
    // remove any previous listener if present
    if (html._prefsMediaListener) {
        window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', html._prefsMediaListener);
        delete html._prefsMediaListener;
    }

    if (theme === 'dark') {
        html.setAttribute('data-theme', 'dark');
    } else if (theme === 'light') {
        html.removeAttribute('data-theme');
    } else if (theme === 'system') {
        html.removeAttribute('data-theme');
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const applyMq = e => {
            if (e.matches) html.setAttribute('data-theme', 'dark');
            else html.removeAttribute('data-theme');
        };
        // apply initial state
        if (mq.matches) html.setAttribute('data-theme', 'dark');
        // store listener for later removal
        html._prefsMediaListener = applyMq;
        mq.addEventListener('change', applyMq);
    }
}

function getStoredTheme() {
    return localStorage.getItem('theme') || 'system';
}

function setStoredTheme(theme) {
    localStorage.setItem('theme', theme);
}

function updateThemeUI(theme) {
    const iconMap = { light: '☀️', dark: '🌙', system: '🖥️' };
    document.querySelectorAll('#theme-toggle, #theme-toggle-mobile').forEach(btn => {
        if (btn) btn.textContent = iconMap[theme] || iconMap.system;
    });
}

function setTheme(theme) {
    setStoredTheme(theme);
    applyTheme(theme);
    updateThemeUI(theme);
}

function cycleTheme() {
    const themes = ['light', 'dark', 'system'];
    let current = getStoredTheme();
    let idx = themes.indexOf(current);
    idx = (idx + 1) % themes.length;
    const next = themes[idx];
    console.log('theme cycle:', current, '→', next);
    setTheme(next);
}

// Auto-format phone inputs and other DOMContentLoaded tasks

document.addEventListener('DOMContentLoaded', () => {
    // Initialize theme (light / dark / system) and update icons
    setTheme(getStoredTheme());
    // expose for inline handlers (if still used anywhere)
    window.cycleTheme = cycleTheme;
    window.setTheme = setTheme;

    // Enable form dirty warning for all forms
    enableFormDirtyWarning();
    
    // Handle phone input formatting (Cameroon format)
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', (e) => {
            // Only format if the input looks like a phone number
            if (e.target.value.length >= 9) {
                e.target.value = formatPhoneNumber(e.target.value);
            }
        });
    });
});

// Language Toggle
function setLanguage(lang) {
    const url = new URL(window.location);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

// Debug helper – prints current theme state to console
function debugTheme() {
    const stored = getStoredTheme();
    const effective = document.documentElement.getAttribute('data-theme') || 'light';
    const prefers = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    console.log('theme → stored:', stored, 'effective attr:', effective, 'os preference:', prefers);
}

// expose debugTheme for console
window.debugTheme = debugTheme;

console.log('PlaceParole App loaded ✅');
