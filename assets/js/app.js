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

// Auto-format phone inputs
document.addEventListener('DOMContentLoaded', () => {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', (e) => {
            const formatted = formatPhoneNumber(e.target.value);
            console.log('Phone formatted:', formatted);
        });
    });
});

// Language Toggle
function setLanguage(lang) {
    const url = new URL(window.location);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

console.log('PlaceParole App loaded ✅');
