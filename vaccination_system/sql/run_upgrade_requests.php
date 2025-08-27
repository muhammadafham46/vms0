<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Read the SQL file
    $sql = file_get_contents('add_upgrade_requests.sql');

    // Execute the SQL commands
    $db->exec($sql);
    echo "Upgrade requests system has been successfully set up.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
