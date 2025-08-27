<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get parent ID from query parameter
if (isset($_GET['id'])) {
    $parent_id = $_GET['id'];
    
    // Fetch parent details
    $query = "SELECT * FROM users WHERE id = :id AND user_type = 'parent'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $parent_id);
    $stmt->execute();
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($parent) {
        // Return parent details as JSON
        header('Content-Type: application/json');
        echo json_encode($parent);
    } else {
        // Parent not found
        http_response_code(404);
        echo json_encode(['error' => 'Parent not found']);
    }
} else {
    // No ID provided
    http_response_code(400);
    echo json_encode(['error' => 'Parent ID is required']);
}
?>
