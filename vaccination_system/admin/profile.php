<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile information update
    if (isset($_POST['full_name'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        $query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, address = :address WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $message = 'Profile updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating profile. Please try again.';
            $message_type = 'danger';
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pictures/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_picture'];
        
        // Check file type
        if (!in_array($file['type'], $allowed_types)) {
            $message = 'Invalid file type. Please upload JPEG, PNG, or GIF images only.';
            $message_type = 'danger';
        }
        // Check file size
        elseif ($file['size'] > $max_size) {
            $message = 'File too large. Maximum size is 2MB.';
            $message_type = 'danger';
        }
        else {
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            // Delete old profile picture if exists
            if (!empty($user['profile_picture'])) {
                $old_file_path = $upload_dir . $user['profile_picture'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Update database with new filename
                $query = "UPDATE users SET profile_picture = :profile_picture WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':profile_picture', $new_filename);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $message = $message ? $message . ' Profile picture updated successfully!' : 'Profile picture updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating profile picture in database.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Error uploading file. Please try again.';
                $message_type = 'danger';
            }
        }
    }
    
    // Handle profile picture removal
    if (isset($_POST['remove_picture']) && $_POST['remove_picture'] == '1') {
        $upload_dir = '../uploads/profile_pictures/';
        
        if (!empty($user['profile_picture'])) {
            $old_file_path = $upload_dir . $user['profile_picture'];
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
            
            // Update database to remove profile picture
            $query = "UPDATE users SET profile_picture = NULL WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $message = $message ? $message . ' Profile picture removed successfully!' : 'Profile picture removed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error removing profile picture from database.';
                $message_type = 'danger';
            }
        }
    }
    
    // Refresh user data
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Set template variables
$page_title = "My Profile - Admin Dashboard";
$page_heading = "My Profile";

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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">User Type</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['user_type']); ?>" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Status</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['status']); ?>" disabled>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="position-relative d-inline-block">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         class="rounded-circle" 
                                         style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #007bff;"
                                         alt="Profile Picture">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; border: 3px solid #007bff;">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <button class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle" style="width: 30px; height: 30px;" onclick="document.getElementById('profilePicture').click()">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                                    <input type="file" id="profilePicture" name="profile_picture" class="d-none" accept="image/*" onchange="document.getElementById('profilePictureForm').submit()">
                                </form>
                                <button class="btn btn-outline-primary btn-sm" onclick="document.getElementById('profilePicture').click()">
                                    <i class="fas fa-upload me-1"></i>Change Photo
                                </button>
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="remove_picture" value="1">
                                        <button type="submit" class="btn btn-outline-danger btn-sm ms-1" onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                            <i class="fas fa-trash me-1"></i>Remove
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>Member Since:</strong><br>
                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Last Login:</strong><br>
                            <?php 
                            if (isset($_SESSION['last_login']) && !empty($_SESSION['last_login'])) {
                                echo date('F j, Y g:i A', strtotime($_SESSION['last_login']));
                            } else {
                                echo 'Not available';
                            }
                            ?><br>
                            <small class="text-muted">Current Time: <?php echo date('g:i A'); ?></small>
                        </div>
                        <div class="mb-3">
                            <strong>Current Location:</strong><br>
                            <span id="userLocation" class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <span id="locationText">Detecting location...</span>
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Account Status:</strong><br>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Live Location Detection
            function getLiveLocation() {
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
                                    document.getElementById('locationText').textContent = locationText;
                                    document.getElementById('locationText').innerHTML += ' <span class="badge bg-success">Live</span>';
                                })
                                .catch(error => {
                                    document.getElementById('locationText').textContent = `Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`;
                                    document.getElementById('locationText').innerHTML += ' <span class="badge bg-success">Live</span>';
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
                            document.getElementById('locationText').textContent = errorMessage;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    document.getElementById('locationText').textContent = 'Geolocation not supported by this browser';
                }
            }

            // Get location when page loads
            document.addEventListener('DOMContentLoaded', getLiveLocation);

            // Refresh location every 30 seconds
            setInterval(getLiveLocation, 30000);
        </script>

<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
