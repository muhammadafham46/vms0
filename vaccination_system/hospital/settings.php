<?php
$page_title = 'Hospital Settings';
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        try {
            $query = "UPDATE hospitals 
                     SET name = :name, address = :address, phone = :phone, email = :email
                     WHERE id = :hospital_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $_POST['name']);
            $stmt->bindParam(':address', $_POST['address']);
            $stmt->bindParam(':phone', $_POST['phone']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            
            if ($stmt->execute()) {
                $success = "Hospital settings updated successfully!";
                // Refresh hospital data
                $query = "SELECT h.* FROM hospitals h
                          JOIN users u ON h.id = u.id 
                          WHERE u.id = :user_id AND u.user_type = 'hospital'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update hospital settings.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Hospital Settings</h2>
        <p class="text-muted">Manage your hospital's information and preferences</p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Hospital Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($hospital['name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($hospital['phone'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($hospital['email'] ?? ''); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($hospital['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
