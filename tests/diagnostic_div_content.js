/**
 * Inspect exact div contents for form fields
 */

console.log('=== FORM DIV CONTENT INSPECTOR ===\n');

// Get all divs with labels
const allDivs = document.querySelectorAll('form > div');
console.log(`Found ${allDivs.length} top-level divs in form\n`);

allDivs.forEach((div, idx) => {
  console.log(`\n--- DIV ${idx} ---`);
  console.log('HTML:');
  console.log(div.outerHTML);
  console.log('\nContains:');
  const labels = div.querySelectorAll('label');
  const inputs = div.querySelectorAll('input, textarea');
  console.log(`  ${labels.length} labels: ${Array.from(labels).map(l => l.textContent.substring(0, 30)).join(', ')}`);
  console.log(`  ${inputs.length} inputs: ${Array.from(inputs).map(i => `<${i.tagName.toLowerCase()} name="${i.name}">`).join(', ')}`);
});

// Specifically look for hidden forms or display:none
console.log('\n--- CHECKING CSS DISPLAY ---');
const form = document.querySelector('form');
if (form) {
  form.querySelectorAll('div, input, textarea, label, button').forEach((el, idx) => {
    const display = window.getComputedStyle(el).display;
    const visibility = window.getComputedStyle(el).visibility;
    if (display === 'none' || visibility === 'hidden') {
      console.log(`❌ HIDDEN: <${el.tagName.toLowerCase()} class="${el.className}">`);
    }
  });
}

console.log('\n=== END INSPECTOR ===');
