<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get hospital ID from query parameter
$hospital_id = $_GET['id'] ?? null;

if ($hospital_id) {
    // Fetch hospital details from hospitals table
    $query = "SELECT * FROM hospitals WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $hospital_id);
    $stmt->execute();
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    // Try to find associated hospital user by email
    $query = "SELECT username, password FROM users WHERE email = :email AND user_type = 'hospital'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $hospital['email']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Combine results
    $result = array_merge($hospital, $user ? $user : ['username' => 'Not found', 'password' => 'Not found']);

    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Hospital ID is required']);
}
?>
