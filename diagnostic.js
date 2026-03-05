// Comprehensive diagnostic to check for form rendering issues

console.log('🔍 DIAGNOSTIC FORM RENDERING CHECK\n');
console.log('='.repeat(80));

// 1. Check what's actually in the form element
console.log('\n1️⃣ FORM ELEMENT DETAILS:\n');
const form = document.getElementById('registerSellerForm');
if (form) {
    console.log(`Form ID: ${form.id}`);
    console.log(`Form HTML length: ${form.innerHTML.length} characters`);
    console.log(`Form HTML (complete): `);
    console.log(form.innerHTML);
    console.log('\n' + '-'.repeat(80));
}

// 2. Check for any script errors
console.log('\n2️⃣ CHECKING PAGE CONSOLE MESSAGES:\n');
console.log(`Document ready state: ${document.readyState}`);
console.log(`Window errors: ${window.onerror ? 'Detected' : 'None'}`);

// 3. Check if there are any hidden/collapsed sections
console.log('\n3️⃣ CHECKING FOR HIDDEN ELEMENTS:\n');
const allDivs = document.querySelectorAll('div');
console.log(`Total DIVs on page: ${allDivs.length}`);

// 4. Check body content
console.log('\n4️⃣ BODY CONTENT:\n');
console.log(`Body innerHTML length: ${document.body.innerHTML.length}`);
console.log(`Body children count: ${document.body.children.length}`);

// Log body structure
console.log('\nBody children:');
for (let i = 0; i < document.body.children.length; i++) {
    const child = document.body.children[i];
    console.log(`  ${i + 1}. <${child.tagName}> - ID: "${child.id}" - Class: "${child.className}" - Children: ${child.children.length}`);
}

// 5. Check for main auth container
console.log('\n5️⃣ AUTH CONTAINER STRUCTURE:\n');
const authWrapper = document.querySelector('.auth-wrapper');
if (authWrapper) {
    console.log(`✅ Auth wrapper found`);
    console.log(`   HTML length: ${authWrapper.innerHTML.length}`);
    console.log(`   Display: ${window.getComputedStyle(authWrapper).display}`);
    console.log(`   Visibility: ${window.getComputedStyle(authWrapper).visibility}`);
    
    const authCard = authWrapper.querySelector('.auth-card');
    if (authCard) {
        console.log(`✅ Auth card found`);
        console.log(`   Children: ${authCard.children.length}`);
        console.log(`   HTML length: ${authCard.innerHTML.length}`);
        
        // List children
        console.log(`\n   Children of auth-card:`);
        for (let i = 0; i < authCard.children.length; i++) {
            const child = authCard.children[i];
            console.log(`     ${i + 1}. <${child.tagName}> - ${child.textContent.substring(0, 50)}`);
        }
    } else {
        console.log(`❌ Auth card NOT found`);
    }
} else {
    console.log(`❌ Auth wrapper NOT found`);
}

// 6. Try to find what's between the form tags
console.log('\n6️⃣ FORM INNER STRUCTURE:\n');
if (form) {
    const formChildren = form.children;
    console.log(`Form has ${formChildren.length} direct children`);
    
    for (let i = 0; i < Math.min(5, formChildren.length); i++) {
        const child = formChildren[i];
        console.log(`  Child ${i + 1}: <${child.tagName}> - Class: "${child.className}"`);
    }
}

// 7. Search for all inputs in the entire document
console.log('\n7️⃣ EXHAUSTIVE INPUT SEARCH:\n');
const allInputs = document.querySelectorAll('*[type="password"], *[type="text"], *[type="email"], input, select, textarea');
console.log(`Total form fields found (exhaustive search): ${allInputs.length}`);

for (let i = 0; i < allInputs.length; i++) {
    const elem = allInputs[i];
    console.log(`  ${i + 1}. <${elem.tagName}> Type: ${elem.type} Name: "${elem.name}" ID: "${elem.id}"`);
}

// 8. Check page title and meta to understand which page we're on
console.log('\n8️⃣ PAGE IDENTIFICATION:\n');
console.log(`Page Title: ${document.title}`);
console.log(`Current URL: ${window.location.href}`);
console.log(`Language from URL: ${new URLSearchParams(window.location.search).get('lang')}`);

console.log('\n' + '='.repeat(80));
console.log('END OF DIAGNOSTIC');
console.log('='.repeat(80));
