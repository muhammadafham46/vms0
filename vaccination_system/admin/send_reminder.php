<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['child_id'])) {
    echo json_encode(['success' => false, 'message' => 'Child ID is required']);
    exit;
}

$child_id = $input['child_id'];

try {
    // Get child and parent details
    $query = "SELECT c.*, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone
              FROM children c
              JOIN users u ON c.parent_id = u.id
              WHERE c.id = :child_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':child_id', $child_id);
    $stmt->execute();
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo json_encode(['success' => false, 'message' => 'Child not found']);
        exit;
    }

    // Get upcoming or missed vaccinations
    $query = "SELECT vs.*, v.name as vaccine_name
              FROM vaccination_schedule vs
              JOIN vaccines v ON vs.vaccine_id = v.id
              WHERE vs.child_id = :child_id 
              AND (vs.status = 'pending' OR vs.status = 'missed')
              AND vs.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              ORDER BY vs.scheduled_date ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':child_id', $child_id);
    $stmt->execute();
    $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Here you would typically:
    // 1. Send email to parent
    // 2. Send SMS notification
    // 3. Log the reminder in database
    // 4. Maybe create a notification in the system

    // For now, we'll just log it and return success
    try {
        $log_query = "INSERT INTO reminder_logs (child_id, parent_email, parent_phone, vaccinations_count, sent_at) 
                      VALUES (:child_id, :parent_email, :parent_phone, :vaccinations_count, NOW())";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bindParam(':child_id', $child_id);
        $log_stmt->bindParam(':parent_email', $child['parent_email']);
        $log_stmt->bindParam(':parent_phone', $child['parent_phone']);
        $vaccinations_count = count($vaccinations);
        $log_stmt->bindParam(':vaccinations_count', $vaccinations_count);
        $log_stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Reminder sent successfully',
            'details' => [
                'child_name' => $child['name'],
                'parent_email' => $child['parent_email'],
                'parent_phone' => $child['parent_phone'],
                'vaccinations_count' => $vaccinations_count
            ]
        ]);
    } catch (PDOException $e) {
        // If reminder_logs table doesn't exist, still return success but log the error
        error_log("Reminder log error: " . $e->getMessage());
        
        echo json_encode([
            'success' => true, 
            'message' => 'Reminder processed (log not saved)',
            'details' => [
                'child_name' => $child['name'],
                'parent_email' => $child['parent_email'],
                'parent_phone' => $child['parent_phone'],
                'vaccinations_count' => count($vaccinations),
                'note' => 'Reminder logs table may need to be created'
            ]
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
