# Test & Diagnostic Files

This folder contains all test, diagnostic, and debugging scripts for the PlaceParole project.

## File Categories

### 📋 Diagnostic JavaScript Files (Debugging)
These were used during development to debug form rendering and page structure issues:
- `diagnostic.js` - Basic form rendering diagnostic
- `diagnostic_form_visibility.js` - Form visibility checks
- `diagnostic_form_enhanced.js` - Enhanced form diagnostics
- `diagnostic_form_html_inspector.js` - HTML structure inspector
- `diagnostic_ultimate.js` - Comprehensive diagnostic suite
- `diagnostic_div_content.js` - Div content checker

**Status:** ❌ Not actively used in production (can be deleted if development is complete)

### 🔍 Form Field Detection Files (Debugging)
Scripts to detect and verify form fields during development:
- `check_form_debug.js` - Form field debugging
- `check_form_fields.js` - Form field detection
- `check_form_fields_advanced.js` - Advanced field detection

**Status:** ❌ Not actively used in production (can be deleted if development is complete)

### 🎨 CSS Inspection Files (Debugging)
Tools for CSS rule inspection and style debugging:
- `inspect_styles.js` - CSS style inspector
- `find_css_rules.js` - CSS rule finder

**Status:** ❌ Not actively used in production (can be deleted if development is complete)

### 🧪 Test & Verification PHP Files
- `test_form_render.php` - Tests form rendering functionality
- `test_market_validation.php` - Tests market data validation
- `TESTING_GUIDE.php` - Testing guide and helpers
- `verify_implementation.php` - Verifies implementation completeness

**Status:** ℹ️ For manual testing (can be deleted after testing is complete)

### 📊 Test Data SQL Files
Database test data for development and testing:
- `test_data.sql` - General test data
- `test_data_complaints_flow.sql` - Complaint flow test data
- `test_data_complaints_setup.sql` - Complaint system setup data
- `test_data_fresh_import.sql` - Fresh database import data
- `test_data_new_features.sql` - New features test data

**Status:** ℹ️ Use for seeding test database (keep until production)

## Files Still in Root (Active)

The following test/setup files remain in the root because they are still referenced in documentation:
- `verify_schema.php` - Schema verification (referenced in IMPLEMENTATION_FORMS_COMPLETE.md)
- `setup_verify.php` - Setup verification (referenced in BUILD_COMPLETE.md)
- `run_migrations.php` - Database migration runner (referenced in documentation)
- `run_migration_002.php` - Specific migration runner (referenced in IMPLEMENTATION_FORMS_COMPLETE.md)

## Cleanup Guide

When your project is ready for production, you can safely delete:
1. **All diagnostic `.js` files** - These are development debugging tools
2. **Form check `.js` files** - Used only during form development
3. **CSS inspection `.js` files** - Used only during styling
4. **PHP test files** - After you've verified functionality works correctly

Keep the test data SQL files until you have a proper production database setup.

## Usage

To use any of these files:

**PHP files:**
```bash
php tests/test_form_render.php
php tests/test_market_validation.php
```

**SQL data:**
```bash
mysql -u user -p database < tests/test_data.sql
```

**JavaScript files (run in browser console):**
Copy and paste contents into the browser DevTools console
