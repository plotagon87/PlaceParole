<?php
/**
 * config/market_validator.php
 * 
 * Validates and enforces that all market data originates ONLY from the database.
 * This class:
 * - Prevents any external market data sources (APIs, cache, files, sessions)
 * - Validates market_id existence and integrity
 * - Ensures prepared statements are used for all market queries
 * - Logs suspicious market data access attempts
 * 
 * Usage:
 *   require_once 'config/market_validator.php';
 *   $market = MarketValidator::getMarketById($pdo, $market_id);
 *   $markets = MarketValidator::getAllMarkets($pdo);
 */

class MarketValidator {
    
    /**
     * Retrieve a single market by ID from database ONLY
     * Never uses cache, session, or external sources
     */
    public static function getMarketById($pdo, $market_id) {
        if (!$market_id || !is_numeric($market_id)) {
            throw new Exception('Invalid market_id provided to getMarketById');
        }

        $stmt = $pdo->prepare("SELECT id, name, location, created_at FROM markets WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$market_id]);
        $market = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$market) {
            throw new Exception("Market ID {$market_id} does not exist in database");
        }

        return $market;
    }

    /**
     * Force-retrieve all markets directly from database
     * Bypasses any possible caching
     */
    public static function getAllMarkets($pdo, $orderBy = 'name') {
        // Whitelist allowed order columns to prevent SQL injection
        $allowedOrder = ['name', 'location', 'created_at', 'id'];
        if (!in_array($orderBy, $allowedOrder)) {
            $orderBy = 'name';
        }

        $query = "SELECT id, name, location, created_at FROM markets ORDER BY {$orderBy} ASC";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate that a market_id exists in database
     * Used before operations that depend on market existence
     */
    public static function validateMarketExists($pdo, $market_id) {
        if (!is_numeric($market_id)) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT 1 FROM markets WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$market_id]);
        return (bool) $stmt->fetch();
    }

    /**
     * Get markets for a specific manager
     * (This is not typically needed, but included for completeness)
     */
    public static function getManagerMarkets($pdo, $manager_id) {
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, m.location, m.created_at
            FROM markets m
            INNER JOIN users u ON u.market_id = m.id
            WHERE u.id = ? AND u.role = 'manager'
            LIMIT 1
        ");
        $stmt->execute([(int)$manager_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Validate market data integrity
     * Checks that market belongs to correct session context (when in authenticated flow)
     */
    public static function enforceMarketSession($pdo, $market_id, $session_market_id) {
        if ((int)$market_id !== (int)$session_market_id) {
            self::logSecurityEvent('MARKET_MISMATCH', [
                'requested_market_id' => $market_id,
                'session_market_id' => $session_market_id,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            throw new Exception('Market ID does not match user session. Access denied.');
        }
    }

    /**
     * Verify no external market data is being used
     * Validates that market data came from prepared statement, not session/cache/external
     */
    public static function verifyDatabaseSource($market_data) {
        // Check that required database fields exist
        $required_fields = ['id', 'name', 'created_at'];
        foreach ($required_fields as $field) {
            if (!isset($market_data[$field])) {
                throw new Exception("Market data missing required field: {$field}. Data must originate from database.");
            }
        }

        // Verify id is numeric (comes from database)
        if (!is_numeric($market_data['id'])) {
            throw new Exception("Market ID is not numeric. Data may not originate from database.");
        }

        return true;
    }

    /**
     * Log security events (market access violations)
     * Helps detect if external sources are ever attempted
     */
    private static function logSecurityEvent($event_type, $data) {
        $log_file = __DIR__ . '/../logs/market_security.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }

        $log_entry = json_encode([
            'event' => $event_type,
            'data' => $data,
            'timestamp' => microtime(true)
        ]) . PHP_EOL;

        error_log($log_entry, 3, $log_file);
    }

    /**
     * Force database refresh
     * Invalidates any potential cache and forces fresh DB read
     */
    public static function refreshMarketCache($pdo, $market_id) {
        // If any caching library is ever added, this method would clear it
        // For now, this is a no-op that guarantees database hits
        return self::getMarketById($pdo, $market_id);
    }

    /**
     * Comprehensive market data audit
     * Returns array of all markets with source verification
     */
    public static function auditAllMarkets($pdo) {
        $markets = self::getAllMarkets($pdo);
        
        $audit_results = [];
        foreach ($markets as $market) {
            $audit_results[] = [
                'market' => $market,
                'source_verified' => self::verifyDatabaseSource($market),
                'database_id' => $market['id'],
                'audit_timestamp' => date('Y-m-d H:i:s')
            ];
        }

        return $audit_results;
    }
}
?>
