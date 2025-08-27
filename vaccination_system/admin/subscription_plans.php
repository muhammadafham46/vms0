<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_plan'])) {
        // Add new plan
        $name = $_POST['name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $bookings_limit = $_POST['bookings_limit'];
        $branches_limit = $_POST['branches_limit'] ?: null;
        $staff_accounts_limit = $_POST['staff_accounts_limit'] ?: null;
        $sms_reminders = $_POST['sms_reminders'] ?: null;
        $features = $_POST['features'];

        $query = "INSERT INTO hospital_plans (name, price, duration, bookings_limit, branches_limit, staff_accounts_limit, sms_reminders, features) 
                  VALUES (:name, :price, :duration, :bookings_limit, :branches_limit, :staff_accounts_limit, :sms_reminders, :features)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':bookings_limit', $bookings_limit);
        $stmt->bindParam(':branches_limit', $branches_limit);
        $stmt->bindParam(':staff_accounts_limit', $staff_accounts_limit);
        $stmt->bindParam(':sms_reminders', $sms_reminders);
        $stmt->bindParam(':features', $features);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Subscription plan added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add subscription plan. Please try again.";
        }
    } elseif (isset($_POST['update_plan'])) {
        // Update plan
        $id = $_POST['plan_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $bookings_limit = $_POST['bookings_limit'];
        $branches_limit = $_POST['branches_limit'] ?: null;
        $staff_accounts_limit = $_POST['staff_accounts_limit'] ?: null;
        $sms_reminders = $_POST['sms_reminders'] ?: null;
        $features = $_POST['features'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $query = "UPDATE hospital_plans SET name = :name, price = :price, duration = :duration, 
                  bookings_limit = :bookings_limit, branches_limit = :branches_limit, 
                  staff_accounts_limit = :staff_accounts_limit, sms_reminders :sms_reminders, 
                  features = :features, is_active = :is_active WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':bookings_limit', $bookings_limit);
        $stmt->bindParam(':branches_limit', $branches_limit);
        $stmt->bindParam(':staff_accounts_limit', $staff_accounts_limit);
        $stmt->bindParam(':sms_reminders', $sms_reminders);
        $stmt->bindParam(':features', $features);
        $stmt->bindParam(':is_active', $is_active);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Subscription plan updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update subscription plan. Please try again.";
        }
    }
    
    header("Location: subscription_plans.php");
    exit();
}

// Get all subscription plans
$query = "SELECT * FROM hospital_plans ORDER BY price ASC, duration ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get plan for editing if ID is provided
$edit_plan = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM hospital_plans WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_plan = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get subscription statistics
$stats = [];
$queries = [
    'total_plans' => "SELECT COUNT(*) as count FROM hospital_plans",
    'active_plans' => "SELECT COUNT(*) as count FROM hospital_plans WHERE is_active = 1",
    'total_subscriptions' => "SELECT COUNT(*) as count FROM hospital_subscriptions",
    'active_subscriptions' => "SELECT COUNT(*) as count FROM hospital_subscriptions WHERE status = 'active'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Set template variables
$page_title = "Subscription Plans - Admin Dashboard";
$page_heading = "Subscription Plans Management";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                
                <p class="text-muted">Manage hospital subscription plans and pricing</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                <i class="fas fa-plus me-1"></i>Add New Plan
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Plans</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_plans']; ?></h2>
                            </div>
                            <i class="fas fa-list-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Active Plans</h6>
                                <h2 class="fw-bold"><?php echo $stats['active_plans']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Subscriptions</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_subscriptions']; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Active Subscriptions</h6>
                                <h2 class="fw-bold"><?php echo $stats['active_subscriptions']; ?></h2>
                            </div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Plans Grid -->
        <div class="row g-4">
            <?php foreach ($plans as $plan): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card plan-card h-100 border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                        <!-- Plan Header with Gradient Background -->
                        <div class="plan-header <?php 
                            echo strtolower(explode(' ', $plan['name'])[0]) . '-plan'; 
                        ?> text-center py-5 position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-<?php echo $plan['is_active'] ? 'light text-success' : 'light text-secondary'; ?> px-3 py-2 fw-semibold">
                                    <i class="fas <?php echo $plan['is_active'] ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                    <?php echo $plan['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                            </div>
                            
                            <div class="text-white">
                                <h5 class="card-title mb-2 fw-bold text-uppercase letter-spacing-1"><?php echo $plan['name']; ?></h5>
                                <h2 class="fw-bold display-4 mb-1">PKR <?php echo number_format($plan['price'], 0); ?></h2>
                                <small class="opacity-80">per <?php echo ucfirst($plan['duration']); ?></small>
                            </div>
                        </div>
                        
                        <!-- Plan Body -->
                        <div class="card-body p-4">
                            <!-- Features List -->
                            <div class="features mb-4">
                                <div class="d-flex align-items-center mb-3 p-3 rounded-3" style="background: #f8f9fa;">
                                    <div class="feature-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">Bookings</h6>
                                        <small class="text-muted"><?php echo $plan['bookings_limit']; ?> included</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3 p-3 rounded-3" style="background: #f8f9fa;">
                                    <div class="feature-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">Branches</h6>
                                        <small class="text-muted"><?php echo $plan['branches_limit'] ?: 'Unlimited'; ?> supported</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3 p-3 rounded-3" style="background: #f8f9fa;">
                                    <div class="feature-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">Staff Accounts</h6>
                                        <small class="text-muted"><?php echo $plan['staff_accounts_limit'] ?: 'Unlimited'; ?> users</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-4 p-3 rounded-3" style="background: #f8f9fa;">
                                    <div class="feature-icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-sms"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">SMS Reminders</h6>
                                        <small class="text-muted"><?php echo $plan['sms_reminders'] ?: 'Unlimited'; ?> messages</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features Description -->
                            <div class="features-description mb-4 text-center">
                                <div class="bg-light p-3 rounded-3">
                                    <p class="text-dark small mb-0 fw-medium">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        <?php echo $plan['features']; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Edit Button -->
                            <div class="text-center">
                                <a href="?edit=<?php echo $plan['id']; ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold">
                                    <i class="fas fa-edit me-2"></i>Edit Plan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Plan Modal -->
        <div class="modal fade" id="addPlanModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Subscription Plan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Plan Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (PKR) *</label>
                                    <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration *</label>
                                    <select class="form-select" name="duration" required>
                                        <option value="monthly">Monthly</option>
                                        <option value="3-months">3 Months</option>
                                        <option value="6-months">6 Months</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bookings Limit *</label>
                                    <input type="text" class="form-control" name="bookings_limit" placeholder="e.g., 100/month, Unlimited" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Branches Limit</label>
                                    <input type="number" class="form-control" name="branches_limit" min="0" placeholder="Leave empty for unlimited">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Staff Accounts Limit</label>
                                    <input type="number" class="form-control" name="staff_accounts_limit" min="0" placeholder="Leave empty for unlimited">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">SMS Reminders</label>
                                    <input type="number" class="form-control" name="sms_reminders" min="0" placeholder="Leave empty for unlimited">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Features Description *</label>
                                <textarea class="form-control" name="features" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <?php if ($edit_plan): ?>
        <div class="modal fade show" id="editPlanModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Subscription Plan</h5>
                        <a href="subscription_plans.php" class="btn-close"></a>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="plan_id" value="<?php echo $edit_plan['id']; ?>">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Plan Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $edit_plan['name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (PKR) *</label>
                                    <input type="number" class="form-control" name="price" step="0.01" min="0" value="<?php echo $edit_plan['price']; ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration *</label>
                                    <select class="form-select" name="duration" required>
                                        <option value="monthly" <?php echo $edit_plan['duration'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="3-months" <?php echo $edit_plan['duration'] === '3-months' ? 'selected' : ''; ?>>3 Months</option>
                                        <option value="6-months" <?php echo $edit_plan['duration'] === '6-months' ? 'selected' : ''; ?>>6 Months</option>
                                        <option value="yearly" <?php echo $edit_plan['duration'] === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bookings Limit *</label>
                                    <input type="text" class="form-control" name="bookings_limit" value="<?php echo $edit_plan['bookings_limit']; ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Branches Limit</label>
                                    <input type="number" class="form-control" name="branches_limit" min="0" value="<?php echo $edit_plan['branches_limit']; ?>" placeholder="Leave empty for unlimited">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Staff Accounts Limit</label>
                                    <input type="number" class="form-control" name="staff_accounts_limit" min="0" value="<?php echo $edit_plan['staff_accounts_limit']; ?>" placeholder="Leave empty for unlimited">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">SMS Reminders</label>
                                    <input type="number" class="form-control" name="sms_reminders" min="0" value="<?php echo $edit_plan['sms_reminders']; ?>" placeholder="Leave empty for unlimited">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Features Description *</label>
                                <textarea class="form-control" name="features" rows="3" required><?php echo $edit_plan['features']; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo $edit_plan['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active Plan
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="subscription_plans.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_plan" class="btn btn-warning">Update Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php
// Get the content and include the template
$content = ob_get_clean();

// Add custom scripts
$custom_scripts = '
<script>
// Show edit modal if editing
';
if ($edit_plan) {
    $custom_scripts .= '
document.addEventListener(\'DOMContentLoaded\', function() {
    const modal = new bootstrap.Modal(document.getElementById(\'editPlanModal\'));
    modal.show();
});';
}

$custom_scripts .= '
</script>';

include 'includes/template.php';
?>
