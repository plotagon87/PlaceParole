/**
 * Form Visibility Diagnostic Script
 * Run this in browser console to diagnose form visibility issues
 */

console.log('=== FORM VISIBILITY DIAGNOSTIC ===\n');

// 1. Check for form elements
console.log('1. SEARCHING FOR FORM ELEMENTS:');
const allForms = document.querySelectorAll('form');
console.log(`Total forms found: ${allForms.length}`);
allForms.forEach((form, idx) => {
  console.log(`  Form ${idx}:`, {
    id: form.id,
    name: form.name,
    action: form.action,
    method: form.method,
    display: window.getComputedStyle(form).display,
    visibility: window.getComputedStyle(form).visibility,
    hidden: form.hidden,
    parentVisible: form.parentElement ? window.getComputedStyle(form.parentElement).display : 'no parent'
  });
});

// 2. Check for specific form containers
console.log('\n2. CHECKING SPECIFIC CONTAINERS:');
const containers = [
  'form-container',
  'suggestions-form',
  'announcements-form',
  'community-form',
  'feedback-form',
  'announcement-form',
  'suggestion-form'
];

containers.forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    console.log(`✓ Found #${id}:`, {
      display: window.getComputedStyle(el).display,
      visibility: window.getComputedStyle(el).visibility,
      opacity: window.getComputedStyle(el).opacity,
      hidden: el.hidden,
      innerHTML: `${el.innerHTML.substring(0, 100)}...`
    });
  } else {
    console.log(`✗ #${id} not found`);
  }
});

// 3. Check for form inputs
console.log('\n3. CHECKING FORM INPUTS:');
const inputs = document.querySelectorAll('input[type="text"], input[type="email"], textarea, select');
console.log(`Total inputs found: ${inputs.length}`);
inputs.forEach((input, idx) => {
  console.log(`  Input ${idx}:`, {
    type: input.type,
    name: input.name,
    placeholder: input.placeholder,
    disabled: input.disabled,
    display: window.getComputedStyle(input).display
  });
});

// 4. Check for buttons
console.log('\n4. CHECKING SUBMIT BUTTONS:');
const buttons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
console.log(`Total submit buttons found: ${buttons.length}`);
buttons.forEach((btn, idx) => {
  console.log(`  Button ${idx}:`, {
    text: btn.textContent || btn.value,
    type: btn.type,
    disabled: btn.disabled,
    display: window.getComputedStyle(btn).display
  });
});

// 5. Check for CSS display issues
console.log('\n5. CHECKING CSS DISPLAY PROPERTIES:');
const divs = document.querySelectorAll('[class*="form"], [id*="form"]');
divs.forEach((div, idx) => {
  if (idx < 10) { // Limit output
    const styles = window.getComputedStyle(div);
    if (styles.display === 'none' || styles.visibility === 'hidden' || styles.opacity === '0') {
      console.log(`⚠ Hidden element: ${div.id || div.className}`, {
        display: styles.display,
        visibility: styles.visibility,
        opacity: styles.opacity
      });
    }
  }
});

// 6. Check for JavaScript errors
console.log('\n6. CHECKING FOR JAVASCRIPT ERRORS:');
window.addEventListener('error', (event) => {
  console.error('JS Error detected:', event.error);
});

// 7. Check page structure
console.log('\n7. PAGE STRUCTURE:');
console.log({
  url: window.location.href,
  title: document.title,
  bodyClass: document.body.className,
  bodyId: document.body.id,
  mainContents: document.querySelectorAll('main, .main, .container, .content').length
});

// 8. Check for modals or overlays
console.log('\n8. CHECKING FOR MODALS/OVERLAYS:');
const modals = document.querySelectorAll('[role="dialog"], .modal, .overlay, [class*="modal"], [class*="overlay"]');
console.log(`Found ${modals.length} potential modals/overlays`);
modals.forEach((modal, idx) => {
  console.log(`  Modal ${idx}:`, {
    class: modal.className,
    id: modal.id,
    display: window.getComputedStyle(modal).display,
    zIndex: window.getComputedStyle(modal).zIndex
  });
});

// 9. Direct path check
console.log('\n9. CHECKING IF ON RIGHT PAGE:');
const pathContent = {
  '/suggestions/submit': document.querySelector('form[action*="submit"]'),
  '/announcements/create': document.querySelector('form[action*="create"]'),
  '/community/report': document.querySelector('form[action*="report"]')
};
Object.entries(pathContent).forEach(([path, form]) => {
  console.log(`  ${path}: ${form ? '✓ Form found' : '✗ Not on this page'}`);
});

// 10. Show HTML of any found forms
console.log('\n10. FORM HTML PREVIEW:');
allForms.forEach((form, idx) => {
  console.log(`\nForm ${idx} HTML (first 500 chars):`);
  console.log(form.outerHTML.substring(0, 500));
});

console.log('\n=== END DIAGNOSTIC ===');
