<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Set template variables
$page_title = "Upgrade Requests - Admin Dashboard";
$page_heading = "Upgrade Requests";
// Handle approve/reject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token mismatch!");
    }

    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? null;

    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        $status = 'pending';
    }

    $update_stmt = $db->prepare("UPDATE upgrade_requests SET status = :status, admin_notes = :admin_notes, updated_at = NOW() WHERE id = :id");
    $update_stmt->bindParam(':status', $status);
    $update_stmt->bindParam(':admin_notes', $admin_notes);
    $update_stmt->bindParam(':id', $request_id);
    $update_stmt->execute();
}

// Fetch upgrade requests with hospital & plan info
$query = "SELECT ur.*, 
                 hp_req.name AS requested_plan_name, hp_req.price AS requested_plan_price,
                 h.name AS hospital_name, h.email AS email, h.contact_person, h.phone AS phone,
                 hp_curr.name AS current_plan_name, hp_curr.price AS current_plan_price
          FROM upgrade_requests ur
          JOIN hospital_plans hp_req ON ur.requested_plan_id = hp_req.id
          JOIN hospitals h ON ur.hospital_id = h.id
          LEFT JOIN hospital_plans hp_curr ON ur.current_plan_id = hp_curr.id
          ORDER BY ur.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$upgrade_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);






// Get statistics
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => count($upgrade_requests)
];

foreach ($upgrade_requests as $request) {
    $status = $request['status'];
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}

// Set content for the template
ob_start();
?>
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Requests</h6>
                                <h2 class="fw-bold"><?php echo $stats['total']; ?></h2>
                            </div>
                            <i class="fas fa-list fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Pending</h6>
                                <h2 class="fw-bold"><?php echo $stats['pending']; ?></h2>
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
                                <h2 class="fw-bold"><?php echo $stats['approved']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                                <div>
                                  <h6 class="card-title">Rejected</h6>
                                <h2 class="fw-bold"><?php echo $stats['rejected']; ?></h2>  

                            </div>
                              <i class="fas fa-times-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        <div>

        <!-- Upgrade Requests Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>All Upgrade Requests</h5>
            </div>
            <div class="card-body">
                <?php if (count($upgrade_requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
    <thead>
        <tr>
            <th>Hospital</th>
            <th>Requested Plan</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($upgrade_requests as $request): ?>
        <tr>
            <td><?= htmlspecialchars($request['hospital_name']) ?></td>
            <td><?= htmlspecialchars($request['requested_plan_name']) ?></td>
            <td><?= ucfirst($request['status']) ?></td>
            <td>
                <!-- Approve / Reject Form -->
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                </form>

                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                </form>

                <!-- View Details Modal Trigger -->
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewDetailsModal<?= $request['id'] ?>">View Details</button>
            </td>
        </tr>

        <!-- Modal -->
        <div class="modal fade" id="viewDetailsModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="viewDetailsLabel<?= $request['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewDetailsLabel<?= $request['id'] ?>">Upgrade Request Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Hospital:</strong> <?= htmlspecialchars($request['hospital_name']) ?></p>
                        <p><strong>Contact Person:</strong> <?= htmlspecialchars($request['contact_person']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($request['phone_number']) ?></p>

                        <p><strong>Current Plan:</strong> <?= htmlspecialchars($request['current_plan_name'] ?? 'Basic (Trial)') ?> - Rs.<?= number_format($request['current_plan_price'] ?? 0, 2) ?></p>
                        <p><strong>Requested Plan:</strong> <?= htmlspecialchars($request['requested_plan_name']) ?> - Rs.<?= number_format($request['requested_plan_price'], 2) ?></p>

                        <?php if (!empty($request['receipt_filename']) && file_exists('../uploads/receipts/' . $request['receipt_filename'])): ?>
                            <img src="../uploads/receipts/<?= htmlspecialchars($request['receipt_filename']) ?>" class="img-fluid">
                        <?php endif; ?>

                        <?php if (!empty($request['additional_notes'])): ?>
                            <p><strong>Additional Notes:</strong> <?= nl2br(htmlspecialchars($request['additional_notes'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($request['admin_notes'])): ?>
                            <p><strong>Admin Notes:</strong> <?= nl2br(htmlspecialchars($request['admin_notes'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
    </tbody>
</table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No upgrade requests found.</p>
                <?php endif; ?>
            </div>
        </div>
<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
