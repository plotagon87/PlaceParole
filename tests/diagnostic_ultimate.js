/**
 * FINAL DIAGNOSTIC: Show exact form structure and renderability
 */

console.log('=== ULTIMATE FORM DIAGNOSTIC ===\n');

// 1. Check if form exists
const form = document.querySelector('form');
console.log('1. FORM EXISTENCE:', form ? '✓ Found' : '✗ NOT FOUND');

if (form) {
  // 2. Count all elements in form
  const all Elements = form.querySelectorAll('*');
  console.log(`2. TOTAL ELEMENTS IN FORM: ${allElements.length}`);
  
  // 3. Check div count
  const divs = form.querySelectorAll('div');
  console.log(`3. DIV CONTAINERS: ${divs.length}`);
  
  // 4. Check for labels  
  const labels = form.querySelectorAll('label');
  console.log(`4. LABELS: ${labels.length}`);
  labels.forEach((label, i) => {
    console.log(`   [${i}] "${label.textContent.substring(0, 50)}"`);
  });
  
  // 5. Check for all inputs/textareas/buttons
  const inputs = form.querySelectorAll('input, textarea, select, button');
  console.log(`5. FORM CONTROLS: ${inputs.length}`);
  inputs.forEach((el, i) => {
    console.log(`   [${i}] <${el.tagName.toLowerCase()} type="${el.type}" name="${el.name}" value="${el.value.substring(0, 30)}"`);
  });
  
  // 6. Check specific input types
  console.log(`6. INPUT TYPE BREAKDOWN:`);
  console.log(`   Text inputs: ${form.querySelectorAll('input[type="text"]').length}`);
  console.log(`   Hidden inputs: ${form.querySelectorAll('input[type="hidden"]').length}`);
  console.log(`   Checkboxes: ${form.querySelectorAll('input[type="checkbox"]').length}`);
  console.log(`   Textareas: ${form.querySelectorAll('textarea').length}`);
  console.log(`   Buttons: ${form.querySelectorAll('button').length}`);
  
  // 7. Show raw form HTML
  console.log(`\n7. RAW FORM HTML (first 3000 chars):`);
  console.log(form.outerHTML.substring(0, 3000));
  
  // 8. Check computed styles of form
  const formStyles = window.getComputedStyle(form);
  console.log(`\n8. FORM STYLES:`);
  console.log({
    display: formStyles.display,
    visibility: formStyles.visibility,
    opacity: formStyles.opacity,
    width: formStyles.width,
    height: formStyles.height
  });
}

// 9. Check if Alpine.js or other framework is interfering
console.log(`\n9. FRAMEWORK CHECK:`);
console.log(`   Alpine.js: ${'Alpine' in window ? '✓ Loaded' : '✗ Not Found'}`);
console.log(`   jQuery: ${'jQuery' in window ? '✓ Loaded' : '✗ Not Found'}`);

console.log('\n=== END DIAGNOSTIC ===');
