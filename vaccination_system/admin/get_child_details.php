<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Child ID is required']);
    exit;
}

$child_id = $_GET['id'];

// Get child details with parent information
$query = "SELECT c.*, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone
          FROM children c
          JOIN users u ON c.parent_id = u.id
          WHERE c.id = :child_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':child_id', $child_id);
$stmt->execute();
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if ($child) {
    echo json_encode(['success' => true, 'child' => $child]);
} else {
    echo json_encode(['success' => false, 'message' => 'Child not found']);
}
?>
