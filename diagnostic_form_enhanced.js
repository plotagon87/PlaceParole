/**
 * Enhanced Form Visibility Diagnostic
 * Run this in browser console to see what's actually in the form
 */

console.log('=== ENHANCED FORM DIAGNOSTIC ===\n');

// Get all forms
const allForms = document.querySelectorAll('form');
console.log(`Found ${allForms.length} form(s)`);

allForms.forEach((form, idx) => {
  console.log(`\n--- FORM ${idx} DETAILS ---`);
  console.log('Action:', form.action);
  console.log('Method:', form.method);
  
  // Get all form elements
  const inputs = form.querySelectorAll('input, textarea, select, button');
  console.log(`Total form elements: ${inputs.length}`);
  
  console.log('\nAll form elements:');
  inputs.forEach((el, i) => {
    console.log(`  ${i}. <${el.tagName.toLowerCase()} type="${el.type}" name="${el.name}" id="${el.id}" class="${el.className}">`);
    console.log(`      visible: ${getComputedStyle(el).display !== 'none'}`);
    console.log(`      value: "${el.value}"`);
  });
  
  // Show full HTML
  console.log('\n--- FULL FORM HTML ---');
  console.log(form.outerHTML);
});

// Check if there are any divs/elements hiding inputs
console.log('\n--- CHECKING FOR HIDDEN INPUT CONTAINERS ---');
const hiddenElements = document.querySelectorAll('[style*="display:none"], [style*="display: none"], .hidden, [hidden]');
console.log(`Found ${hiddenElements.length} potentially hidden elements`);
hiddenElements.forEach((el, idx) => {
  if (idx < 10) {
    console.log(`  ${el.outerHTML.substring(0, 100)}`);
  }
});

// Check for PHP errors
console.log('\n--- CHECKING PAGE CONTENT ---');
const bodyText = document.body.innerText.substring(0, 500);
console.log('First 500 chars of page text:');
console.log(bodyText);

// Check for JavaScript errors in page
console.log('\n--- CHECKING WINDOW ERRORS ---');
if (window.__errors) {
  console.log('Stored errors:', window.__errors);
} else {
  console.log('No stored errors found');
}

console.log('\n=== END ENHANCED DIAGNOSTIC ===');
