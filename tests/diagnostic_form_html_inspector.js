/**
 * Ultra-Detailed Form HTML Inspector
 * Shows the EXACT HTML of the form element by element
 */

console.log('=== FORM HTML INSPECTOR ===\n');

const form = document.querySelector('form');
if (!form) {
  console.log('❌ NO FORM FOUND ON PAGE');
} else {
  console.log('✅ FORM FOUND');
  console.log('\n--- COMPLETE FORM HTML ---');
  console.log(form.outerHTML);
  
  console.log('\n--- FORM BREAKDOWN ---');
  const children = form.children;
  console.log(`Form has ${children.length} direct children:\n`);
  
  for (let i = 0; i < children.length; i++) {
    const child = children[i];
    console.log(`[${i}] <${child.tagName.toLowerCase()} class="${child.className}">`);
    console.log(`     HTML: ${child.outerHTML.substring(0, 150)}...`);
    
    // Check if this container has inputs
    const inputs = child.querySelectorAll('input, textarea, select, label');
    if (inputs.length > 0) {
      console.log(`     Contains ${inputs.length} input elements:`);
      inputs.forEach((inp, j) => {
        console.log(`       [${j}] <${inp.tagName.toLowerCase()} type="${inp.type}" name="${inp.name}" id="${inp.id}">`);
      });
    }
  }
}

console.log('\n--- PAGE HTML AFTER FORM ---');
const main = document.querySelector('main');
if (main) {
  console.log('Main content:');
  console.log(main.innerHTML.substring(0, 2000));
}

console.log('\n=== END INSPECTOR ===');
