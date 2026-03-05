// Script to check for form fields on the website
console.log('='.repeat(60));
console.log('FORM FIELDS DETECTION REPORT');
console.log('='.repeat(60));

// Get all input fields
const allInputs = document.querySelectorAll('input');
console.log(`\nTotal input fields found: ${allInputs.length}\n`);

// Check for password fields specifically
const passwordFields = document.querySelectorAll('input[type="password"]');
console.log('PASSWORD FIELDS:');
console.log(`- Count: ${passwordFields.length}`);
if (passwordFields.length > 0) {
    passwordFields.forEach((field, index) => {
        console.log(`\n  Password Field ${index + 1}:`);
        console.log(`    - Name: ${field.name || 'NOT SET'}`);
        console.log(`    - ID: ${field.id || 'NOT SET'}`);
        console.log(`    - Type: ${field.type}`);
        console.log(`    - Placeholder: ${field.placeholder || 'NOT SET'}`);
        console.log(`    - Required: ${field.required}`);
        console.log(`    - Class: ${field.className || 'NOT SET'}`);
        console.log(`    - Value exists: ${field.value ? 'YES' : 'NO'}`);
    });
} else {
    console.log('  - No password fields found!');
}

// Check for other form fields
console.log('\n' + '-'.repeat(60));
console.log('ALL FORM FIELDS:');
console.log('-'.repeat(60));
allInputs.forEach((input, index) => {
    console.log(`\nField ${index + 1}:`);
    console.log(`  - Type: ${input.type}`);
    console.log(`  - Name: ${input.name || 'NOT SET'}`);
    console.log(`  - ID: ${input.id || 'NOT SET'}`);
    console.log(`  - Placeholder: ${input.placeholder || 'NOT SET'}`);
    console.log(`  - Required: ${input.required}`);
    console.log(`  - Class: ${input.className || 'NOT SET'}`);
});

// Check for form elements
console.log('\n' + '-'.repeat(60));
console.log('FORM ELEMENTS:');
console.log('-'.repeat(60));
const forms = document.querySelectorAll('form');
console.log(`\nTotal forms found: ${forms.length}`);
forms.forEach((form, index) => {
    console.log(`\nForm ${index + 1}:`);
    console.log(`  - ID: ${form.id || 'NOT SET'}`);
    console.log(`  - Name: ${form.name || 'NOT SET'}`);
    console.log(`  - Action: ${form.action || 'NOT SET'}`);
    console.log(`  - Method: ${form.method || 'NOT SET'}`);
    console.log(`  - Class: ${form.className || 'NOT SET'}`);
});

console.log('\n' + '='.repeat(60));
console.log('END OF REPORT');
console.log('='.repeat(60));
