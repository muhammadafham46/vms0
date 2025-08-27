<?php
define('SECURE_ACCESS', true);
require_once 'includes/template.php';

$page_title = 'Notification Settings';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];

// Check feature access
redirect_if_feature_not_accessible('notification_settings', $hospital);

// Get notification settings
$query = "SELECT ns.* FROM hospital_notification_settings ns
          WHERE ns.hospital_id = :hospital_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist, create default settings
if (!$settings) {
    $query = "INSERT INTO hospital_notification_settings (hospital_id) VALUES (:hospital_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hospital_id', $hospital['id']);
    $stmt->execute();
    
    // Fetch the newly created settings
    $query = "SELECT * FROM hospital_notification_settings WHERE hospital_id = :hospital_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hospital_id', $hospital['id']);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $query = "UPDATE hospital_notification_settings SET 
                  new_appointment = :new_appointment,
                  appointment_reminder = :appointment_reminder,
                  appointment_cancellation = :appointment_cancellation,
                  vaccination_reminder = :vaccination_reminder,
                  stock_alert = :stock_alert,
                  stock_threshold = :stock_threshold,
                  email_notifications = :email_notifications,
                  sms_notifications = :sms_notifications,
                  reminder_days_before = :reminder_days_before,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE hospital_id = :hospital_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->bindParam(':new_appointment', isset($_POST['new_appointment']), PDO::PARAM_BOOL);
        $stmt->bindParam(':appointment_reminder', isset($_POST['appointment_reminder']), PDO::PARAM_BOOL);
        $stmt->bindParam(':appointment_cancellation', isset($_POST['appointment_cancellation']), PDO::PARAM_BOOL);
        $stmt->bindParam(':vaccination_reminder', isset($_POST['vaccination_reminder']), PDO::PARAM_BOOL);
        $stmt->bindParam(':stock_alert', isset($_POST['stock_alert']), PDO::PARAM_BOOL);
        $stmt->bindParam(':stock_threshold', $_POST['stock_threshold'], PDO::PARAM_INT);
        $stmt->bindParam(':email_notifications', isset($_POST['email_notifications']), PDO::PARAM_BOOL);
        $stmt->bindParam(':sms_notifications', isset($_POST['sms_notifications']), PDO::PARAM_BOOL);
        $stmt->bindParam(':reminder_days_before', $_POST['reminder_days_before'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Notification settings updated successfully!";
            // Refresh settings
            $query = "SELECT * FROM hospital_notification_settings WHERE hospital_id = :hospital_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Failed to update settings.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header('Location: notification_settings.php');
    exit();
}

render_hospital_header($page_title);
display_messages();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Notification Settings</h2>
        <p class="text-muted">Manage your notification preferences</p>
    </div>
</div>

<form method="POST">
    <div class="row g-4">
        <!-- Appointment Notifications -->
        <div class="col-md-6">
            <div class="card setting-card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                        Appointment Notifications
                    </h5>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="new_appointment" 
                               name="new_appointment" <?php echo $settings['new_appointment'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="new_appointment">New appointment notifications</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="appointment_reminder" 
                               name="appointment_reminder" <?php echo $settings['appointment_reminder'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="appointment_reminder">Appointment reminders</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="appointment_cancellation" 
                               name="appointment_cancellation" <?php echo $settings['appointment_cancellation'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="appointment_cancellation">Appointment cancellation alerts</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination Notifications -->
        <div class="col-md-6">
            <div class="card setting-card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-syringe me-2 text-success"></i>
                        Vaccination Notifications
                    </h5>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="vaccination_reminder" 
                               name="vaccination_reminder" <?php echo $settings['vaccination_reminder'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="vaccination_reminder">Vaccination schedule reminders</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="stock_alert" 
                               name="stock_alert" <?php echo $settings['stock_alert'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="stock_alert">Low stock alerts</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock alert threshold</label>
                        <input type="number" class="form-control" name="stock_threshold" 
                               value="<?php echo $settings['stock_threshold']; ?>" min="1">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Methods -->
        <div class="col-md-6">
            <div class="card setting-card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-envelope me-2 text-warning"></i>
                        Notification Methods
                    </h5>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="email_notifications" 
                               name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">Email notifications</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="sms_notifications" 
                               name="sms_notifications" <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sms_notifications">SMS notifications</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timing Settings -->
        <div class="col-md-6">
            <div class="card setting-card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-clock me-2 text-info"></i>
                        Timing Settings
                    </h5>
                    <div class="mb-3">
                        <label class="form-label">Send reminders days before appointment</label>
                        <input type="number" class="form-control" name="reminder_days_before" 
                               value="<?php echo $settings['reminder_days_before']; ?>" min="1" max="7">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" name="update_settings" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Save Settings
        </button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
render_hospital_footer();
?>
