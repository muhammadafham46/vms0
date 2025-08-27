<?php

define('SECURE_ACCESS', true);

// Use the new template system
require_once 'includes/template.php';
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'includes/plan_features.php';

redirectIfNotHospital();

$page_title = 'Dashboard';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];
$stats = $init_result['stats'];
$recent_appointments = $init_result['recent_appointments'];
$low_stock_vaccines = $init_result['low_stock_vaccines'];

// Get hospital information with plan details
$query = "SELECT h.*, hp.name as plan_name, hp.price as plan_price, hp.duration, hp.features
          FROM hospitals h
          JOIN users u ON h.id = u.id 
          LEFT JOIN hospital_plans hp ON h.current_plan_id = hp.id
          WHERE u.id = :user_id AND u.user_type = 'hospital'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$hospital_tracking = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upgrade request history
$query = "SELECT ur.*, hp.name as requested_plan_name, hp.price as requested_plan_price
          FROM upgrade_requests ur
          JOIN hospital_plans hp ON ur.requested_plan_id = hp.id
          WHERE ur.hospital_id = :hospital_id
          ORDER BY ur.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital_tracking['id']);
$stmt->execute();
$upgrade_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available plans for upgrade modal
$current_plan_id = $hospital['current_plan_id'] ?? 0;
$stmt = $db->prepare("
    SELECT * FROM hospital_plans 
    WHERE is_active = 1 AND id != :current_plan_id 
    ORDER BY price ASC
");
$stmt->bindParam(':current_plan_id', $current_plan_id, PDO::PARAM_INT);
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render header
render_hospital_header($page_title);
display_messages();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Hospital Dashboard</h2>
    <div class="d-flex align-items-center">
        <span class="me-3">Welcome, <?php echo $hospital['name']; ?></span>
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-cog me-1"></i>Settings
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-hospital me-2"></i>Hospital Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-users me-2"></i>Staff Management</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-clock me-2"></i>Working Hours</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Subscription Plan Information -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-crown me-2"></i>Subscription Plan</h5>
    </div>
    <div class="card-body">
        <div class="col-md-8">
                        <h3 class="text-primary"><?php echo !empty($hospital_tracking['plan_name']) ? $hospital_tracking['plan_name'] : 'Basic (Trial)'; ?></h3>
                        <p class="text-muted">
                            <?php if (!empty($hospital_tracking['plan_price'])): ?>
                                PKR <?php echo number_format($hospital_tracking['plan_price'], 2); ?> 
                                <?php echo !empty($hospital_tracking['duration']) ? '/ ' . $hospital_tracking['duration'] : ''; ?>
                            <?php else: ?>
                                Free Trial Plan
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($hospital_tracking['features'])): ?>
                            <h6>Features:</h6>
                            <ul class="feature-list">
                                <?php 
                                $features = explode(',', $hospital_tracking['features']);
                                foreach ($features as $feature): 
                                ?>
                                    <li><?php echo trim($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
            <?php
            //  Safe access variables
            $bookings_limit = isset($hospital['bookings_limit']) && $hospital['bookings_limit'] > 0 
                ? $hospital['bookings_limit'] 
                : 'Unlimited';

            $branches_limit = isset($hospital['branches_limit']) && $hospital['branches_limit'] > 0 
                ? $hospital['branches_limit'] 
                : 'Unlimited';

            $staff_accounts_limit = isset($hospital['staff_accounts_limit']) && $hospital['staff_accounts_limit'] > 0 
                ? $hospital['staff_accounts_limit'] 
                : 'Unlimited';
            ?>
            <div class="row">
                <div class="col-md-8">
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-check text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">Appointments</h6>
                                    <p>
                                        <?php echo $hospital['current_month_appointments'] ?? 0; ?> /
                                        <?php echo $bookings_limit; ?> this month
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-building text-success me-2"></i>
                                <div>
                                    <h6 class="mb-0">Branches</h6>
                                    <p>
                                        <?php echo $hospital['branches'] ?? 0; ?> /
                                        <?php echo $branches_limit; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-warning me-2"></i>
                                <div>
                                    <h6 class="mb-0">Staff Accounts</h6>
                                    <p>
                                        <?php echo $hospital['staff_accounts'] ?? 0; ?> /
                                        <?php echo $staff_accounts_limit; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#upgradePlanModal">
                        <i class="fas fa-arrow-up me-2"></i>Upgrade Plan
                    </button>
                </div>
            </div>
        
    </div>
</div>


<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card stat-card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Appointments</h6>
                        <h2 class="fw-bold"><?php echo $stats['total_appointments']; ?></h2>
                    </div>
                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Pending Approval</h6>
                        <h2 class="fw-bold"><?php echo $stats['pending_appointments']; ?></h2>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Approved</h6>
                        <h2 class="fw-bold"><?php echo $stats['approved_appointments']; ?></h2>
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
                        <h6 class="card-title">Today's Appointments</h6>
                        <h2 class="fw-bold"><?php echo $stats['today_appointments']; ?></h2>
                    </div>
                    <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Appointments -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Appointments</h5>
    </div>
    <div class="card-body">
        <?php if (count($recent_appointments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Child Name</th>
                            <th>Parent</th>
                            <th>Vaccine</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['child_name']; ?></td>
                                <td><?php echo $appointment['parent_name']; ?></td>
                                <td><?php echo $appointment['vaccine_name']; ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($appointment['status']) {
                                            case 'pending': echo 'warning'; break;
                                            case 'approved': echo 'success'; break;
                                            case 'completed': echo 'info'; break;
                                            case 'rejected': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Action
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="appointments.php?action=view&id=<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a></li>
                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                <li><a class="dropdown-item" href="appointments.php?action=approve&id=<?php echo $appointment['id']; ?>">
                                                    <i class="fas fa-check me-2"></i>Approve
                                                </a></li>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] == 'approved'): ?>
                                                <li><a class="dropdown-item" href="appointments.php?action=complete&id=<?php echo $appointment['id']; ?>">
                                                    <i class="fas fa-check-double me-2"></i>Complete
                                                </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No recent appointments found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                <h5>Manage Appointments</h5>
                <p>View and manage all appointments</p>
                <a href="appointments.php" class="btn btn-primary">View Appointments</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-syringe fa-3x text-success mb-3"></i>
                <h5>Vaccine Inventory</h5>
                <p>Update vaccine stock levels</p>
                <a href="vaccine_inventory.php" class="btn btn-success">Manage Inventory</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                <h5>Generate Reports</h5>
                <p>View hospital reports</p>
                <a href="reports.php" class="btn btn-info">View Reports</a>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Plan Modal -->
<div class="modal fade" id="upgradePlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-arrow-up me-2"></i>Upgrade Subscription Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="upgrade_request.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hospital Name</label>
                            <input type="text" class="form-control" value="<?php echo $hospital['name']; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Plan</label>
                            <input type="text" class="form-control" value="<?php echo isset($hospital['plan_name']) ? $hospital['plan_name'] : 'Basic (Trial)'; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person Name *</label>
                            <input type="text" class="form-control" name="contact_person" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone_number" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Plan to Upgrade *</label>
                            <select class="form-select" name="requested_plan_id" required>
                                <option value="">Select Plan</option>
                                <?php
                                $query = "SELECT * FROM hospital_plans WHERE is_active = 1 AND id != :current_plan_id ORDER BY price ASC";
                                $stmt = $db->prepare($query);
                                $current_plan_id = !empty($hospital['current_plan_id']) ? $hospital['current_plan_id'] : 0;
                                $stmt->bindParam(':current_plan_id', $current_plan_id);
                                $stmt->execute();
                                $available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($available_plans as $plan): ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo $plan['name']; ?> - PKR <?php echo number_format($plan['price'], 2); ?> 
                                        (<?php echo ucfirst($plan['duration']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="payment_method" id="paymentMethod" required>
                                <option value="">Select Payment Method</option>
                                <option value="easypaisa">EasyPaisa</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="bank_transfer">Bank Transfer (Contact Admin)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="receiptUploadSection">
                            <label class="form-label">Upload Payment Receipt *</label>
                            <input type="file" class="form-control" name="receipt" accept="image/*,.pdf" id="receiptInput" required>
                            <small class="form-text">Upload screenshot or photo of payment receipt</small>
                        </div>
                    </div>
                    
                    <div id="easypaisaDetails" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please make payment to the following EasyPaisa account and upload the receipt:
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">EasyPaisa Account Title</label>
                                <input type="text" class="form-control" value="Muhammad Afham" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">EasyPaisa Account Number</label>
                                <input type="text" class="form-control" value="03460895203" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div id="jazzcashDetails" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please make payment to the following JazzCash account and upload the receipt:
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">JazzCash Account Title</label>
                                <input type="text" class="form-control" value="Muhammad Afham" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">JazzCash Account Number</label>
                                <input type="text" class="form-control" value="03460895203" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-control" name="additional_notes" rows="3" placeholder="Any special requirements or notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Upgrade Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show/hide payment method details and receipt upload
    document.getElementById('paymentMethod').addEventListener('change', function() {
        document.getElementById('easypaisaDetails').style.display = 'none';
        document.getElementById('jazzcashDetails').style.display = 'none';
        
        if (this.value === 'easypaisa') {
            document.getElementById('easypaisaDetails').style.display = 'block';
            document.getElementById('receiptUploadSection').style.display = 'block';
            document.getElementById('receiptInput').setAttribute('required', 'required');
        } else if (this.value === 'jazzcash') {
            document.getElementById('jazzcashDetails').style.display = 'block';
            document.getElementById('receiptUploadSection').style.display = 'block';
            document.getElementById('receiptInput').setAttribute('required', 'required');
        } else if (this.value === 'bank_transfer') {
            document.getElementById('receiptUploadSection').style.display = 'none';
            document.getElementById('receiptInput').removeAttribute('required');
        } else {
            document.getElementById('receiptUploadSection').style.display = 'block';
            document.getElementById('receiptInput').setAttribute('required', 'required');
        }
    });
</script>

<?php render_hospital_footer(); ?>
