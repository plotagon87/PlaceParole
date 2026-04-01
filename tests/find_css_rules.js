// Find CSS rules affecting a selector property
function findRules(selector, property) {
    const matches = [];
    for (const sheet of document.styleSheets) {
        let rules;
        try { rules = sheet.cssRules; } catch(e) { continue; }
        if (!rules) continue;
        for (const rule of rules) {
            if (rule.selectorText && rule.selectorText.includes(selector)) {
                const val = rule.style.getPropertyValue(property);
                if (val) matches.push({sheet: sheet.href || 'inline', rule: rule.cssText});
            }
        }
    }
    return matches;
}
console.log('body background rules:', findRules('body','background-color'));
console.log('body color rules:', findRules('body','color'));
