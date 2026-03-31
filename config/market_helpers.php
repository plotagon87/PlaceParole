<?php
/**
 * config/market_helpers.php
 * 
 * Convenience helpers for market data retrieval
 * All functions guarantee data comes directly from database
 * 
 * Usage:
 *   require_once 'config/market_helpers.php';
 *   $market = getMarket($market_id);
 *   $markets = getMarkets();
 */

require_once __DIR__ . '/market_validator.php';

/**
 * Get a single market by ID
 * Guaranteed to be from database
 */
function getMarket($market_id) {
    global $pdo;
    return MarketValidator::getMarketById($pdo, $market_id);
}

/**
 * Get all markets
 * Guaranteed to be fresh from database (no cache)
 */
function getMarkets($orderBy = 'name') {
    global $pdo;
    return MarketValidator::getAllMarkets($pdo, $orderBy);
}

/**
 * Check if market exists
 * Returns boolean
 */
function marketExists($market_id) {
    global $pdo;
    return MarketValidator::validateMarketExists($pdo, $market_id);
}

/**
 * Get market with user context validation
 * Ensures user belongs to their market session
 */
function getMarketForUser($market_id) {
    global $pdo;
    
    // If user is logged in, verify market matches session
    if (isset($_SESSION['market_id'])) {
        MarketValidator::enforceMarketSession($pdo, $market_id, $_SESSION['market_id']);
    }
    
    return MarketValidator::getMarketById($pdo, $market_id);
}

/**
 * Get all markets for dropdown (safe for frontend)
 * Returns minimal data set, prevents data exposure
 */
function getMarketsForDropdown() {
    global $pdo;
    
    $markets = MarketValidator::getAllMarkets($pdo);
    
    // Return only necessary fields for UI
    $safe_markets = [];
    foreach ($markets as $market) {
        $safe_markets[] = [
            'id' => $market['id'],
            'name' => $market['name'],
            'location' => $market['location'] ?? ''
        ];
    }
    
    return $safe_markets;
}

/**
 * Verify all market data is from database
 * Runs audit on provided market data
 */
function verifyMarketDataSource($market_data) {
    return MarketValidator::verifyDatabaseSource($market_data);
}

/**
 * Get market count
 * Returns total number of markets in database
 */
function getMarketCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM markets");
    return (int) $stmt->fetchColumn();
}

/**
 * Check if market has a specific location
 * Returns boolean
 */
function marketHasLocation($market_id, $location) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM markets WHERE id = ? AND location = ? LIMIT 1");
    $stmt->execute([(int)$market_id, $location]);
    return (bool) $stmt->fetch();
}
?>
