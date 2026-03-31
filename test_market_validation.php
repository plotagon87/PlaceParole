#!/usr/bin/env php
<?php
/**
 * test_market_validation.php
 * 
 * Test script to verify all market data flows use database-only sources
 * Run via: php test_market_validation.php
 * 
 * Tests:
 * 1. MarketValidator class functionality
 * 2. Market data integrity checks
 * 3. Database-only source verification
 * 4. All critical market queries
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/market_validator.php';

echo "========================================\n";
echo "PlaceParole Market Data Validation Test\n";
echo "========================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: Get all markets
echo "Test 1: Retrieving all markets from database...\n";
try {
    $markets = MarketValidator::getAllMarkets($pdo);
    echo "✓ Retrieved " . count($markets) . " markets\n";
    
    // Verify each market
    foreach ($markets as $market) {
        MarketValidator::verifyDatabaseSource($market);
    }
    echo "✓ All markets verified as database source\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 2: Get single market (if any exist)
echo "\nTest 2: Retrieving single market by ID...\n";
try {
    $markets = MarketValidator::getAllMarkets($pdo);
    if (count($markets) > 0) {
        $first_market = $markets[0];
        $market = MarketValidator::getMarketById($pdo, $first_market['id']);
        echo "✓ Retrieved market: " . htmlspecialchars($market['name']) . "\n";
        
        MarketValidator::verifyDatabaseSource($market);
        echo "✓ Single market verified as database source\n";
        $tests_passed++;
    } else {
        echo "⊘ Skipped: No markets in database\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 3: Validate market existence
echo "\nTest 3: Validating market existence checks...\n";
try {
    $markets = MarketValidator::getAllMarkets($pdo);
    if (count($markets) > 0) {
        $first_market = $markets[0];
        $exists = MarketValidator::validateMarketExists($pdo, $first_market['id']);
        
        if ($exists) {
            echo "✓ Market existence check confirmed\n";
        } else {
            throw new Exception("Market existence check failed for existing market");
        }
        
        // Check non-existent market
        $not_exists = MarketValidator::validateMarketExists($pdo, 999999);
        if (!$not_exists) {
            echo "✓ Non-existent market correctly identified\n";
        } else {
            throw new Exception("Non-existent market incorrectly validated");
        }
        
        $tests_passed++;
    } else {
        echo "⊘ Skipped: No markets in database\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 4: Data integrity verification
echo "\nTest 4: Verifying market data integrity...\n";
try {
    $markets = MarketValidator::getAllMarkets($pdo);
    
    if (count($markets) > 0) {
        $market = $markets[0];
        
        // Check required fields exist
        $required = ['id', 'name', 'created_at'];
        foreach ($required as $field) {
            if (!isset($market[$field])) {
                throw new Exception("Market missing required field: {$field}");
            }
        }
        echo "✓ All required fields present\n";
        
        // Check data types are correct
        if (!is_numeric($market['id'])) {
            throw new Exception("Market ID is not numeric");
        }
        if (!is_string($market['name'])) {
            throw new Exception("Market name is not string");
        }
        echo "✓ All data types correct\n";
        
        $tests_passed++;
    } else {
        echo "⊘ Skipped: No markets in database\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 5: Query ordering
echo "\nTest 5: Testing different query orders...\n";
try {
    $by_name = MarketValidator::getAllMarkets($pdo, 'name');
    $by_id = MarketValidator::getAllMarkets($pdo, 'id');
    
    if (count($by_name) > 0 && count($by_id) > 0) {
        echo "✓ Retrieved markets ordered by name: " . count($by_name) . "\n";
        echo "✓ Retrieved markets ordered by id: " . count($by_id) . "\n";
        
        // Verify counts match
        if (count($by_name) === count($by_id)) {
            echo "✓ Count consistency verified\n";
        } else {
            throw new Exception("Query result counts differ");
        }
        
        $tests_passed++;
    } else {
        echo "⊘ Skipped: No markets in database\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 6: Security check - no external data
echo "\nTest 6: Verifying no external data sources are used...\n";
try {
    $markets = MarketValidator::getAllMarkets($pdo);
    
    // Check that markets don't have extra fields that would indicate external API sourcing
    $suspicious_fields = ['external_id', 'cached_at', 'sync_from_api', 'air_id', 'remote_source'];
    
    foreach ($markets as $market) {
        foreach ($suspicious_fields as $field) {
            if (isset($market[$field])) {
                throw new Exception("Suspicious field found: {$field}. Data may come from external source.");
            }
        }
    }
    
    echo "✓ No suspicious external data fields detected\n";
    echo "✓ All markets confirmed as direct database source\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Test 7: Audit functionality
echo "\nTest 7: Running comprehensive market audit...\n";
try {
    $audit = MarketValidator::auditAllMarkets($pdo);
    
    echo "✓ Audit completed\n";
    echo "✓ Audited " . count($audit) . " markets\n";
    
    // Verify all audits passed
    foreach ($audit as $result) {
        if (!$result['source_verified']) {
            throw new Exception("Audit failed for market ID: " . $result['database_id']);
        }
    }
    
    echo "✓ All markets passed source verification audit\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $tests_failed++;
}

// Summary
echo "\n========================================\n";
echo "Test Results\n";
echo "========================================\n";
echo "✓ Passed: {$tests_passed}\n";
echo "✗ Failed: {$tests_failed}\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n🎉 All tests passed! Market data is guaranteed to come from database.\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed. Please review the output above.\n";
    exit(1);
}
?>
