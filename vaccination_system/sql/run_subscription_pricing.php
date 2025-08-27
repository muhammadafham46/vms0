<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Read the SQL file
    $sql = file_get_contents('add_subscription_pricing.sql');

    // Execute the SQL commands
    $db->exec($sql);
    echo "Subscription pricing system has been successfully set up.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
