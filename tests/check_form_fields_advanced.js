// Enhanced script to detect form fields (including dynamic ones)
console.log('='.repeat(70));
console.log('ADVANCED FORM FIELDS DETECTION REPORT');
console.log('='.repeat(70));

// Wait for DOM to be ready
function inspectForms() {
    console.log('\n📋 CHECKING FOR FORMS AND FIELDS...\n');

    // Check all forms
    const forms = document.querySelectorAll('form');
    console.log(`Total forms found: ${forms.length}`);

    forms.forEach((form, formIndex) => {
        console.log(`\n${'='.repeat(70)}`);
        console.log(`FORM ${formIndex + 1}:`);
        console.log(`${'='.repeat(70)}`);
        console.log(`  ID: ${form.id || 'NOT SET'}`);
        console.log(`  Name: ${form.name || 'NOT SET'}`);
        console.log(`  Action: ${form.action || 'NOT SET'}`);
        console.log(`  Method: ${form.method || 'NOT SET'}`);
        console.log(`  Class: ${form.className || 'NOT SET'}`);

        // Check for input fields within this form
        const inputs = form.querySelectorAll('input');
        console.log(`\n  📝 Input Fields in this form: ${inputs.length}`);
        
        if (inputs.length === 0) {
            console.log('  ⚠️ No input fields found! Checking form HTML...');
            console.log('\n📄 RAW FORM HTML:');
            console.log(form.innerHTML);
        } else {
            inputs.forEach((input, inputIndex) => {
                console.log(`\n    Input ${inputIndex + 1}:`);
                console.log(`      - Type: ${input.type}`);
                console.log(`      - Name: ${input.name || 'NOT SET'}`);
                console.log(`      - ID: ${input.id || 'NOT SET'}`);
                console.log(`      - Placeholder: ${input.placeholder || 'NOT SET'}`);
                console.log(`      - Value: ${input.value || '(empty)'}`);
                console.log(`      - Required: ${input.required}`);
                console.log(`      - Class: ${input.className || 'NOT SET'}`);
            });
        }

        // Check for buttons in form
        const buttons = form.querySelectorAll('button');
        console.log(`\n  🔘 Buttons in form: ${buttons.length}`);
        buttons.forEach((btn, btnIndex) => {
            console.log(`    Button ${btnIndex + 1}: "${btn.textContent.trim()}" (type: ${btn.type})`);
        });

        // Check for select elements
        const selects = form.querySelectorAll('select');
        if (selects.length > 0) {
            console.log(`\n  ⬇️ Select fields: ${selects.length}`);
            selects.forEach((select, selectIndex) => {
                console.log(`    Select ${selectIndex + 1}: Name="${select.name}", Options: ${select.options.length}`);
            });
        }

        // Check for textarea elements
        const textareas = form.querySelectorAll('textarea');
        if (textareas.length > 0) {
            console.log(`\n  📄 Textarea fields: ${textareas.length}`);
            textareas.forEach((ta, taIndex) => {
                console.log(`    Textarea ${taIndex + 1}: Name="${ta.name}"`);
            });
        }
    });

    // Check all input fields on the page (not just in forms)
    console.log(`\n${'='.repeat(70)}`);
    console.log('ALL INPUT FIELDS ON PAGE (including outside forms):');
    console.log(`${'='.repeat(70)}`);
    const allInputs = document.querySelectorAll('input');
    console.log(`Total: ${allInputs.length}`);
    
    if (allInputs.length > 0) {
        allInputs.forEach((input, index) => {
            console.log(`\n  Input ${index + 1}:`);
            console.log(`    - Type: ${input.type}`);
            console.log(`    - Name: ${input.name || 'NOT SET'}`);
            console.log(`    - ID: ${input.id || 'NOT SET'}`);
            console.log(`    - Placeholder: ${input.placeholder || 'NOT SET'}`);
        });
    }

    // Check for Alpine.js data
    console.log(`\n${'='.repeat(70)}`);
    console.log('CHECKING FOR ALPINE.JS OR DYNAMIC ELEMENTS:');
    console.log(`${'='.repeat(70)}`);
    if (typeof Alpine !== 'undefined') {
        console.log('✅ Alpine.js detected on page');
    } else {
        console.log('⚠️ Alpine.js not detected');
    }

    console.log('\n' + '='.repeat(70));
    console.log('END OF REPORT');
    console.log('='.repeat(70));
}

// Run immediately
inspectForms();

// Also check after a delay in case content is still loading
console.log('\n⏳ Will check again in 2 seconds for dynamically loaded content...\n');
setTimeout(() => {
    console.log('\n\n🔄 SECOND CHECK (after 2 second delay):\n');
    inspectForms();
}, 2000);
