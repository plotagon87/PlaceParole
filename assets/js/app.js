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
 * Format phone number (basic)
 */
function formatPhoneNumber(phone) {
    return phone.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 $2 $3 $4');
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
