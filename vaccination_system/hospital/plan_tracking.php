<?php
define('SECURE_ACCESS', true);
require_once 'includes/template.php';

$page_title = 'Plan Tracking';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];

// Clear any cached results to ensure fresh data
$db->query("RESET QUERY CACHE");

// Get hospital information with plan details - force fresh query
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


// Get all available plans for upgrade
$query = "SELECT * FROM hospital_plans WHERE is_active = TRUE ORDER BY price ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_hospital_header($page_title);
display_messages();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Plan Tracking</h2>
        <p class="text-muted">Manage your hospital subscription plan</p>
    </div>
    <a href="upgrade_request.php" class="btn btn-primary">
        <i class="fas fa-arrow-up me-2"></i>Request Upgrade
    </a>
</div>

<!-- Current Plan Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card plan-card current-plan">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-"><i class="fas fa-crown me-2"></i>Current Plan</h5>
            </div>
            <div class="card-body">
                <div class="row">
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
                    <div class="col-md-4 text-end">
                        <div class="bg-light p-3 rounded">
                            <h6>Plan Status</h6>
                            <span class="badge bg-success">Active</span>
                            <?php if ($hospital_tracking['trial_end_date']): ?>
                                <p class="mt-2 mb-0">
                                    <small>Trial ends: <?php echo date('M d, Y', strtotime($hospital_tracking['trial_end_date'])); ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Requests History -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Upgrade Request History</h5>
    </div>
    <div class="card-body">
        <?php if (count($upgrade_requests) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Requested Plan</th>
                            <th>Price</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upgrade_requests as $request): ?>
                            <tr>
                                <td><?php echo $request['requested_plan_name']; ?></td>
                                <td>PKR <?php echo number_format($request['requested_plan_price'], 2); ?></td>
                                <td><?php echo ucfirst($request['payment_method']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($request['status']) {
                                            case 'pending': echo 'warning'; break;
                                            case 'approved': echo 'success'; break;
                                            case 'rejected': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewRequestModal<?php echo $request['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    
                                    <!-- View Request Modal -->
                                    <div class="modal fade" id="viewRequestModal<?php echo $request['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title">Request Details</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Plan Information</h6>
                                                            <p><strong>Requested Plan:</strong> <?php echo $request['requested_plan_name']; ?></p>
                                                            <p><strong>Price:</strong> PKR <?php echo number_format($request['requested_plan_price'], 2); ?></p>
                                                            <p><strong>Status:</strong> 
                                                                <span class="badge bg-<?php 
                                                                    switch($request['status']) {
                                                                        case 'pending': echo 'warning'; break;
                                                                        case 'approved': echo 'success'; break;
                                                                        case 'rejected': echo 'danger'; break;
                                                                        default: echo 'secondary';
                                                                    }
                                                                ?>">
                                                                    <?php echo ucfirst($request['status']); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Payment Details</h6>
                                                            <p><strong>Payment Method:</strong> <?php echo ucfirst($request['payment_method']); ?></p>
                                                            <?php if ($request['account_title']): ?>
                                                                <p><strong>Account Title:</strong> <?php echo $request['account_title']; ?></p>
                                                            <?php endif; ?>
                                                            <?php if ($request['account_number']): ?>
                                                                <p><strong>Account Number:</strong> <?php echo $request['account_number']; ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($request['additional_notes']): ?>
                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <h6>Additional Notes</h6>
                                                                <p><?php echo $request['additional_notes']; ?></p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($request['admin_notes']): ?>
                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <h6>Admin Notes</h6>
                                                                <p class="text-muted"><?php echo $request['admin_notes']; ?></p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No upgrade requests found.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
render_hospital_footer();
?>
