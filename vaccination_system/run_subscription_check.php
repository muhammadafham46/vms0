<?php
/**
 * Script to run subscription expiration checker
 * This should be scheduled to run daily via cron job or Windows Task Scheduler
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting subscription expiration check at: " . date('Y-m-d H:i:s') . "\n";

require_once 'subscription_expiration_checker.php';

try {
    $checker = new SubscriptionExpirationChecker();
    
    // Create the downgrade logs table if it doesn't exist
    $checker->createDowngradeLogsTable();
    
    // Check for expired subscriptions
    $result = $checker->checkExpiredSubscriptions();
    
    echo "Subscription check completed: " . $result . "\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error running subscription check: " . $e->getMessage() . "\n";
    error_log("Subscription check error: " . $e->getMessage());
}
?>
