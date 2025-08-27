<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotHospital();

$database = new Database();
$db = $database->getConnection();

// Get hospital information with plan details
$query = "SELECT h.*, hp.name as plan_name 
          FROM hospitals h
          JOIN users u ON h.id = u.id 
          LEFT JOIN hospital_plans hp ON h.current_plan_id = hp.id
          WHERE u.id = :user_id AND u.user_type = 'hospital'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['contact_person', 'phone_number', 'email', 'requested_plan_id'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill all required fields.");
            }
        }

        // Handle file upload - only required for EasyPaisa and JazzCash
        $receipt_filename = null;
        $payment_method = $_POST['payment_method'];
        
        // Only require receipt for EasyPaisa and JazzCash payments
        if ($payment_method === 'easypaisa' || $payment_method === 'jazzcash') {
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/receipts/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
                $receipt_filename = 'receipt_' . time() . '_' . $hospital['id'] . '.' . $file_extension;
                $upload_path = $upload_dir . $receipt_filename;
                
                if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_path)) {
                    throw new Exception("Failed to upload receipt file.");
                }
            } else {
                throw new Exception("Please upload a payment receipt.");
            }
        }

        // Handle current_plan_id - if not set, use 1 (Basic Plan) as default
        $current_plan_id = $hospital['current_plan_id'];
        if (empty($current_plan_id)) {
            $current_plan_id = 1; // Default to Basic Plan ID
        }
        
        $query = "INSERT INTO upgrade_requests (
                    hospital_id, current_plan_id, requested_plan_id, 
                    contact_person, phone_number, email, 
                    payment_method, account_title, account_number, 
                    receipt_filename, additional_notes, status
                  ) VALUES (
                    :hospital_id, :current_plan_id, :requested_plan_id,
                    :contact_person, :phone_number, :email,
                    :payment_method, :account_title, :account_number,
                    :receipt_filename, :additional_notes, 'pending'
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->bindParam(':current_plan_id', $current_plan_id);
        $stmt->bindParam(':requested_plan_id', $_POST['requested_plan_id']);
        $stmt->bindParam(':contact_person', $_POST['contact_person']);
        $stmt->bindParam(':phone_number', $_POST['phone_number']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':payment_method', $_POST['payment_method']);
        $stmt->bindParam(':account_title', $_POST['account_title']);
        $stmt->bindParam(':account_number', $_POST['account_number']);
        $stmt->bindParam(':receipt_filename', $receipt_filename);
        $stmt->bindParam(':additional_notes', $_POST['additional_notes']);
        
        if ($stmt->execute()) {
            // Get plan details for email
            $query = "SELECT * FROM hospital_plans WHERE id = :plan_id";
            $stmt2 = $db->prepare($query);
            $stmt2->bindParam(':plan_id', $_POST['requested_plan_id']);
            $stmt2->execute();
            $plan = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            // Get email settings from database
            $query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('upgrade_request_email', 'upgrade_request_subject')";
            $stmt2 = $db->prepare($query);
            $stmt2->execute();
            $settings = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $to = $settings['upgrade_request_email'] ?? 'afham2406f@aptechsite.net';
            $subject = $settings['upgrade_request_subject'] ?? 'New Hospital Upgrade Request';
            $message = "
                New upgrade request received:
                
                Hospital: {$hospital['name']}
                Current Plan: " . (isset($hospital['plan_name']) ? $hospital['plan_name'] : 'Basic (Trial)') . "
                Requested Plan: {$plan['name']} - PKR " . number_format($plan['price'], 2) . "
                Contact Person: {$_POST['contact_person']}
                Phone: {$_POST['phone_number']}
                Email: {$_POST['email']}
                Payment Method: {$_POST['payment_method']}
                
                Please check the admin panel for details.
            ";
            
            mail($to, $subject, $message);
            
            $_SESSION['success'] = "Upgrade request submitted successfully! We'll contact you shortly.";
        } else {
            throw new Exception("Failed to submit upgrade request.");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
}

// If not POST request, redirect to dashboard
header("Location: dashboard.php");
exit();
?>
