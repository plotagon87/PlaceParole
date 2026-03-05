// Script to inspect computed styles of key elements and classes
console.log('='.repeat(60));
console.log('STYLE INSPECTION REPORT');
console.log('='.repeat(60));

// function to log computed styles for selector
function reportStyles(selector, properties) {
    const el = document.querySelector(selector);
    if (!el) {
        console.log(`\nSelector '${selector}' not found on page.`);
        return;
    }
    const comp = window.getComputedStyle(el);
    console.log(`\nStyles for '${selector}':`);
    properties.forEach(prop => {
        console.log(`  ${prop}: ${comp.getPropertyValue(prop)}`);
    });
}

// log body styles
reportStyles('body', ['color','background-color','font-family','font-size']);

// common layout containers
reportStyles('.auth-wrapper',['display','background','color']);
reportStyles('.auth-card',['background-color','color','padding']);

// button classes
['.btn-primary','.btn-secondary','.btn-outlined'].forEach(sel => {
    reportStyles(sel,['background-color','color','border','padding','display']);
});

// input field
reportStyles('.input-field',['border-color','background-color','color','font-size']);

// look for tailwind specific example
['.text-center','.bg-green-500','.border'].forEach(sel => {
    reportStyles(sel,['color','background-color','border-color']);
});

// check root variables
console.log('\n:root variables:');
['--primary','--secondary','--accent','--success','--warning','--danger'].forEach(v=>{
    console.log(`  ${v}: ${getComputedStyle(document.documentElement).getPropertyValue(v)}`);
});

console.log('\nAll styles above.');
console.log('='.repeat(60));