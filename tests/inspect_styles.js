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

// ===== NEW DEBUG CODE =====
console.log('\n' + '='.repeat(60));
console.log('STYLESHEET & VARIABLE VERIFICATION');
console.log('='.repeat(60));

// Quick check — directly read from stylesheet
const styleEl = document.querySelector('link[href*="style.css"]');
console.log('Stylesheet loaded?', styleEl ? 'YES ✅' : 'NO ❌');

// Check if variables are actually there
const root = document.querySelector(':root');
if (root) {
    const allStyles = getComputedStyle(root);
    console.log('Sample vars:');
    console.log('--color-primary:', allStyles.getPropertyValue('--color-primary').trim());
    console.log('--color-bg:', allStyles.getPropertyValue('--color-bg').trim());
    console.log('--color-text-body:', allStyles.getPropertyValue('--color-text-body').trim());
}
console.log('='.repeat(60));

// ===== TEXT VISIBILITY CHECK =====
console.log('\n' + '='.repeat(60));
console.log('TEXT VISIBILITY BLOCKERS CHECK');
console.log('='.repeat(60));

function checkTextVisibility(selector) {
    const el = document.querySelector(selector);
    if (!el) {
        console.log(`❌ '${selector}' not found`);
        return;
    }
    
    const comp = window.getComputedStyle(el);
    const bgColor = comp.backgroundColor;
    const textColor = comp.color;
    const opacity = comp.opacity;
    const visibility = comp.visibility;
    const display = comp.display;
    const textIndent = comp.textIndent;
    const lineHeight = comp.lineHeight;
    const overflow = comp.overflow;
    const zIndex = comp.zIndex;
    
    console.log(`\n📝 '${selector}':`);
    console.log(`  display: ${display} ${display === 'none' ? '⚠️ HIDDEN!' : ''}`);
    console.log(`  visibility: ${visibility} ${visibility === 'hidden' ? '⚠️ HIDDEN!' : ''}`);
    console.log(`  opacity: ${opacity} ${opacity === '0' ? '⚠️ FULLY TRANSPARENT!' : ''}`);
    console.log(`  text color: ${textColor}`);
    console.log(`  background: ${bgColor}`);
    console.log(`  text-indent: ${textIndent} ${textIndent !== '0px' ? '⚠️ OFFSET!' : ''}`);
    console.log(`  line-height: ${lineHeight}`);
    console.log(`  overflow: ${overflow}`);
    console.log(`  z-index: ${zIndex}`);
}

// Check common text elements
checkTextVisibility('body');
checkTextVisibility('h1');
checkTextVisibility('p');
checkTextVisibility('.btn-primary');
checkTextVisibility('input');
checkTextVisibility('label');

// Check all text nodes for opacity 0
console.log('\n🔍 Scanning for opacity:0 elements...');
const opaque = document.querySelectorAll('[style*="opacity: 0"], [style*="opacity:0"]');
if (opaque.length > 0) {
    console.log(`⚠️ Found ${opaque.length} elements with opacity:0`);
    opaque.forEach((el, i) => {
        console.log(`  ${i+1}. ${el.tagName} - ${el.className || el.id || el.textContent.substring(0, 30)}`);
    });
} else {
    console.log('✅ No opacity:0 found');
}

// ===== ADVANCED CONTRAST CHECK =====
console.log('\n' + '='.repeat(60));
console.log('CONTRAST ANALYSIS & HIDDEN ELEMENTS');
console.log('='.repeat(60));

// Check for visibility:hidden or display:none on parent chain
console.log('\n🔍 Checking for hidden parent elements...');
document.querySelectorAll('*').forEach(el => {
    const comp = window.getComputedStyle(el);
    const hasText = el.textContent.trim().length > 50; // Only elements with substantial text
    
    if (hasText) {
        const visibility = comp.visibility;
        const display = comp.display;
        const opacity = comp.opacity;
        const pointerEvents = comp.pointerEvents;
        
        const issues = [];
        if (visibility === 'hidden') issues.push('visibility:hidden');
        if (display === 'none') issues.push('display:none');
        if (opacity === '0') issues.push('opacity:0');
        if (pointerEvents === 'none') issues.push('pointer-events:none');
        
        if (issues.length > 0) {
            console.log(`⚠️ Element "${el.textContent.substring(0, 40)}..."`);
            console.log(`   Issues: ${issues.join(', ')}`);
            console.log(`   Tag: ${el.tagName}, Class: ${el.className}`);
        }
    }
});

console.log('\n📊 Elements using very light text on dark backgrounds:');
document.querySelectorAll('*').forEach(el => {
    const comp = window.getComputedStyle(el);
    const text = el.textContent.trim();
    
    // Only check visible elements with real text content
    if (text.length > 20 && text.length < 200) {
        const textColor = comp.color;
        const bgColor = comp.backgroundColor;
        const display = comp.display;
        const visibility = comp.visibility;
        const opacity = comp.opacity;
        
        // Skip hidden elements
        if (display === 'none' || visibility === 'hidden' || opacity === '0') return;
        
        // Light grays: rgb(229, 231, 235), rgb(243, 244, 246)
        if (textColor.includes('rgb(229') || textColor.includes('rgb(243') || textColor.includes('rgb(217')) {
            console.log(`  ✓ "${text.substring(0, 40)}"`);
            console.log(`    Color: ${textColor}, BG: ${bgColor}`);
            console.log(`    Tag: ${el.tagName}, Class: ${el.className}`);
            
            // Check if dark mode is enabled
            const isDarkMode = document.documentElement.hasAttribute('data-theme') && document.documentElement.getAttribute('data-theme') === 'dark';
            console.log(`    Dark mode enabled: ${isDarkMode ? 'YES' : 'NO'}`);
            
            // Check parent classes
            let parent = el.parentElement;
            let depth = 0;
            while (parent && depth < 3) {
                if (parent.classList.contains('bg-gray-50') || parent.classList.contains('bg-yellow-50') || parent.classList.contains('bg-red-50') || parent.classList.contains('bg-blue-50')) {
                    console.log(`    Parent has Tailwind bg class: ${parent.className}`);
                    break;
                }
                parent = parent.parentElement;
                depth++;
            }
        }
    }
});

console.log('='.repeat(60));