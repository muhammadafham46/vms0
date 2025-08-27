<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error changing password. Please try again.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'New password must be at least 6 characters long.';
                $message_type = 'warning';
            }
        } else {
            $message = 'New passwords do not match.';
            $message_type = 'warning';
        }
    } else {
        $message = 'Current password is incorrect.';
        $message_type = 'danger';
    }
}

// Handle notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $appointment_reminders = isset($_POST['appointment_reminders']) ? 1 : 0;
    
    // In a real system, you would save these to a user_settings table
    $message = 'Notification settings saved successfully!';
    $message_type = 'success';
}

// Set template variables
$page_title = "Settings - Admin Dashboard";
$page_heading = "Account Settings";

// Start output buffering
ob_start();
?>

        

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <!-- Password Change Card -->
                <div class="card settings-card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sync-alt me-1"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Notification Settings Card -->
                <div class="card settings-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="save_notifications" value="1">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications">Email Notifications</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sms_notifications" id="sms_notifications">
                                    <label class="form-check-label" for="sms_notifications">SMS Notifications</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="appointment_reminders" id="appointment_reminders" checked>
                                    <label class="form-check-label" for="appointment_reminders">Appointment Reminders</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save me-1"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Preferences Card -->
                <div class="card settings-card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>System Preferences</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Theme Preference</label>
                            <select class="form-select">
                                <option selected>Light Theme</option>
                                <option>Dark Theme</option>
                                <option>System Default</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Language</label>
                            <select class="form-select">
                                <option selected>English</option>
                                <option>Urdu</option>
                                <option>Arabic</option>
                                <option>Punjabi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Timezone</label>
                            <select class="form-select">
                                <option selected>UTC+5:00 (Pakistan Standard Time - PST)</option>
                                <option>UTC+5:30 (India Standard Time - IST)</option>
                                <option>UTC+4:00 (Gulf Standard Time - GST)</option>
                                <option>UTC+0:00 (Greenwich Mean Time - GMT)</option>
                            </select>
                            <small class="text-muted">Current Server Time: <?php echo date('F j, Y g:i A'); ?></small>
                        </div>
                        <button class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Preferences
                            </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Security Card -->
        <div class="card settings-card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Security Notice:</strong> These actions are irreversible. Please proceed with caution.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Login Activity</h6>
                        <p class="text-muted">Last login: <?php 
                        if (isset($_SESSION['last_login']) && !empty($_SESSION['last_login'])) {
                            echo date('F j, Y g:i A', strtotime($_SESSION['last_login']));
                        } else {
                            echo 'Not available';
                        }
                        ?></p>
                        <p class="text-muted">Current session: Active</p>
                        <p class="text-muted">
                            <strong>Current Location:</strong><br>
                            <span id="settingsLocation" class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <span id="settingsLocationText">Detecting location...</span>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Danger Zone</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#logoutAllModal">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout from all devices
                            </button>
                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-trash me-1"></i>Delete account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout All Modal -->
        <div class="modal fade" id="logoutAllModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Logout from all devices</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to logout from all devices? This will terminate all active sessions.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">Confirm Logout</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <div class="modal fade" id="deleteAccountModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.
                        </div>
                        <p>Please type "DELETE" to confirm:</p>
                        <input type="text" class="form-control" placeholder="Type DELETE here">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" disabled>Delete Account</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .settings-card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
        </style>

        <script>
            // Live Location Detection for Settings Page
            function getSettingsLiveLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;
                            
                            // Reverse geocoding to get address
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
                                .then(response => response.json())
                                .then(data => {
                                    let locationText = '';
                                    if (data.address) {
                                        const address = data.address;
                                        locationText = `${address.city || address.town || address.village || ''}, ${address.state || ''}, ${address.country || ''}`;
                                    } else {
                                        locationText = `Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`;
                                    }
                                    document.getElementById('settingsLocationText').textContent = locationText;
                                    document.getElementById('settingsLocationText').innerHTML += ' <span class="badge bg-success">Live</span>';
                                })
                                .catch(error => {
                                    document.getElementById('settingsLocationText').textContent = `Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`;
                                    document.getElementById('settingsLocationText').innerHTML += ' <span class="badge bg-success">Live</span>';
                                });
                        },
                        function(error) {
                            let errorMessage = 'Location access denied';
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMessage = 'Location access denied by user';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMessage = 'Location information unavailable';
                                    break;
                                case error.TIMEOUT:
                                    errorMessage = 'Location request timed out';
                                    break;
                                default:
                                    errorMessage = 'Unknown location error';
                            }
                            document.getElementById('settingsLocationText').textContent = errorMessage;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    document.getElementById('settingsLocationText').textContent = 'Geolocation not supported by this browser';
                }
            }

            // Get location when page loads
            document.addEventListener('DOMContentLoaded', getSettingsLiveLocation);

            // Refresh location every 30 seconds
            setInterval(getSettingsLiveLocation, 30000);
        </script>

<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
