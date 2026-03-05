// ============================================================================
// COMPREHENSIVE FORM FIELD DETECTION WITH DYNAMIC CONTENT DETECTION
// ============================================================================

console.log('🔍 STARTING COMPREHENSIVE FORM INSPECTION...\n');

function inspectPageCompletely() {
    console.log('='.repeat(80));
    console.log('COMPLETE FORM INSPECTION REPORT');
    console.log('='.repeat(80));
    
    // ========================================================================
    // 1. CHECK FOR ALPINE.JS
    // ========================================================================
    console.log('\n📦 CHECKING FOR JAVASCRIPT FRAMEWORKS:\n');
    console.log(`  Alpine.js present: ${typeof Alpine !== 'undefined' ? '✅ YES' : '❌ NO'}`);
    console.log(`  jQuery present: ${typeof jQuery !== 'undefined' ? '✅ YES' : '❌ NO'}`);
    console.log(`  Vue.js present: ${typeof Vue !== 'undefined' ? '✅ YES' : '❌ NO'}`);
    
    // ========================================================================
    // 2. INSPECT ALL FORMS
    // ========================================================================
    const forms = document.querySelectorAll('form');
    console.log(`\n📝 FORMS ON PAGE: ${forms.length}\n`);
    
    forms.forEach((form, formIdx) => {
        console.log(`${'─'.repeat(80)}`);
        console.log(`FORM ${formIdx + 1}:`);
        console.log(`${'─'.repeat(80)}`);
        console.log(`  ID: ${form.id || '(no ID)'}`);
        console.log(`  Name: ${form.name || '(no name)'}`);
        console.log(`  Action: ${form.action || '(no action)'}`);
        console.log(`  Method: ${form.method.toUpperCase()}`);
        console.log(`  HTML Length: ${form.innerHTML.length} characters`);
        
        // ====================================================================
        // Check form content
        // ====================================================================
        const inputs = form.querySelectorAll('input');
        const textareas = form.querySelectorAll('textarea');
        const selects = form.querySelectorAll('select');
        const buttons = form.querySelectorAll('button');
        
        console.log(`\n  📊 FORM CONTENT BREAKDOWN:`);
        console.log(`    - Input fields: ${inputs.length}`);
        console.log(`    - Textareas: ${textareas.length}`);
        console.log(`    - Selects: ${selects.length}`);
        console.log(`    - Buttons: ${buttons.length}`);
        
        // ====================================================================
        // List all input fields
        // ====================================================================
        if (inputs.length > 0) {
            console.log(`\n  📋 INPUT FIELDS:`);
            inputs.forEach((input, idx) => {
                console.log(`\n    Input ${idx + 1}:`);
                console.log(`      - Type: ${input.type}`);
                console.log(`      - Name: ${input.name || '(no name)'}`);
                console.log(`      - ID: ${input.id || '(no id)'}`);
                console.log(`      - Placeholder: ${input.placeholder || '(none)'}`);
                console.log(`      - Value: ${input.value || '(empty)'}`);
                console.log(`      - Required: ${input.required}`);
                console.log(`      - Disabled: ${input.disabled}`);
                console.log(`      - Readonly: ${input.readOnly}`);
                console.log(`      - Visible: ${input.offsetParent !== null ? '✅ YES' : '❌ NO (hidden)'}`);
                console.log(`      - Classes: ${input.className || '(none)'}`);
                console.log(`      - Data attributes: ${Object.keys(input.dataset).length > 0 ? JSON.stringify(input.dataset) : '(none)'}`);
            });
        } else {
            console.log(`\n  ⚠️ NO INPUT FIELDS FOUND IN FORM!`);
            console.log(`\n  🔎 CHECKING RAW HTML (first 500 chars):`);
            console.log(`    ${form.innerHTML.substring(0, 500)}...`);
        }
        
        // List buttons
        if (buttons.length > 0) {
            console.log(`\n  🔘 BUTTONS:`);
            buttons.forEach((btn, idx) => {
                console.log(`    Button ${idx + 1}: "${btn.textContent.trim()}" (type: ${btn.type})`);
            });
        }
    });
    
    // ========================================================================
    // 3. CHECK ALL INPUTS ON ENTIRE PAGE
    // ========================================================================
    console.log(`\n\n${'='.repeat(80)}`);
    console.log('ALL INPUTS ON PAGE (including outside forms)');
    console.log(`${'='.repeat(80)}\n`);
    
    const allInputs = document.querySelectorAll('input');
    console.log(`Total: ${allInputs.length}`);
    
    if (allInputs.length > 0) {
        allInputs.forEach((input, idx) => {
            console.log(`\n  Input ${idx + 1}:`);
            console.log(`    - Type: ${input.type}`);
            console.log(`    - Name: ${input.name || '(no name)'}`);
            console.log(`    - ID: ${input.id || '(no id)'}`);
            console.log(`    - Value: ${input.value || '(empty)'}`);
        });
    } else {
        console.log('  ⚠️ NO INPUTS FOUND ON ENTIRE PAGE');
    }
    
    // ========================================================================
    // 4. CHECK PASSWORD FIELDS
    // ========================================================================
    const passwordFields = document.querySelectorAll('input[type="password"]');
    console.log(`\n\n${'='.repeat(80)}`);
    console.log('PASSWORD FIELDS');
    console.log(`${'='.repeat(80)}`);
    console.log(`Count: ${passwordFields.length}`);
    
    if (passwordFields.length > 0) {
        passwordFields.forEach((field, idx) => {
            console.log(`\n  Password Field ${idx + 1}:`);
            console.log(`    - Name: ${field.name}`);
            console.log(`    - ID: ${field.id}`);
            console.log(`    - Placeholder: ${field.placeholder || '(none)'}`);
        });
    } else {
        console.log('  ⚠️ NO PASSWORD FIELDS FOUND');
    }
    
    // ========================================================================
    // 5. CHECK FOR HIDDEN ELEMENTS
    // ========================================================================
    console.log(`\n\n${'='.repeat(80)}`);
    console.log('CHECKING FOR HIDDEN/DYNAMIC ELEMENTS');
    console.log(`${'='.repeat(80)}\n`);
    
    const hiddenInputs = document.querySelectorAll('input[style*="display: none"], input[hidden], input[x-show], input[x-cloak], input[style*="visibility: hidden"]');
    console.log(`Hidden inputs: ${hiddenInputs.length}`);
    
    // Check for elements with Alpine directives
    const alpineElements = document.querySelectorAll('[x-data], [x-show], [x-if], [x-cloak]');
    console.log(`Elements with Alpine directives: ${alpineElements.length}`);
    
    if (alpineElements.length > 0) {
        console.log('\n  🔄 Alpine.js elements found:');
        alpineElements.forEach((el, idx) => {
            if (idx < 5) { // Show first 5
                console.log(`    ${idx + 1}. <${el.tagName}> - Directives: ${Array.from(el.attributes).filter(a => a.name.startsWith('x-')).map(a => a.name).join(', ')}`);
            }
        });
        if (alpineElements.length > 5) console.log(`    ... and ${alpineElements.length - 5} more`);
    }
    
    // ========================================================================
    // 6. DOM STRUCTURE
    // ========================================================================
    console.log(`\n\n${'='.repeat(80)}`);
    console.log('PAGE STRUCTURE');
    console.log(`${'='.repeat(80)}\n`);
    console.log(`Document Ready State: ${document.readyState}`);
    console.log(`Total Elements on Page: ${document.querySelectorAll('*').length}`);
    console.log(`Body HTML Length: ${document.body.innerHTML.length} characters`);
    
    console.log('\n' + '='.repeat(80));
    console.log('END OF REPORT');
    console.log('='.repeat(80));
}

// Run immediately
inspectPageCompletely();

// Run again after delays to catch dynamic content
console.log('\n⏳ Will re-check in 1 second for dynamically created content...');
setTimeout(() => {
    console.log('\n\n🔄 SECOND CHECK (1 second later):\n');
    inspectPageCompletely();
}, 1000);

console.log('⏳ Will re-check in 3 seconds for dynamically created content...');
setTimeout(() => {
    console.log('\n\n🔄 THIRD CHECK (3 seconds later):\n');
    inspectPageCompletely();
}, 3000);
