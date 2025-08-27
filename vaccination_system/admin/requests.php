<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_request'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            $query = "UPDATE parent_requests SET status = 'approved', processed_at = NOW() WHERE id = :id";
            $message = "Request approved successfully!";
        } else {
            $query = "UPDATE parent_requests SET status = 'rejected', processed_at = NOW() WHERE id = :id";
            $message = "Request rejected successfully!";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $request_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = "Failed to process request. Please try again.";
        }
        
        header("Location: requests.php");
        exit();
    }
}

// Get parent requests with filters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "pr.status = :status";
    $params[':status'] = $status_filter;
} else {
    $where_conditions[] = "pr.status = 'pending'";
}

if ($type_filter) {
    $where_conditions[] = "pr.request_type = :type";
    $params[':type'] = $type_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT pr.*, u.full_name as parent_name, u.email, u.phone,
                 c.name as child_name, c.date_of_birth,
                 TIMESTAMPDIFF(DAY, pr.created_at, NOW()) as days_pending
          FROM parent_requests pr
          JOIN users u ON pr.parent_id = u.id
          LEFT JOIN children c ON pr.child_id = c.id
          $where_clause
          ORDER BY pr.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stat_queries = [
    'total' => "SELECT COUNT(*) as count FROM parent_requests",
    'pending' => "SELECT COUNT(*) as count FROM parent_requests WHERE status = 'pending'",
    'approved' => "SELECT COUNT(*) as count FROM parent_requests WHERE status = 'approved'",
    'rejected' => "SELECT COUNT(*) as count FROM parent_requests WHERE status = 'rejected'",
    'account' => "SELECT COUNT(*) as count FROM parent_requests WHERE request_type = 'account_update'",
    'child' => "SELECT COUNT(*) as count FROM parent_requests WHERE request_type = 'child_info'",
    'appointment' => "SELECT COUNT(*) as count FROM parent_requests WHERE request_type = 'appointment_change'",
    'other' => "SELECT COUNT(*) as count FROM parent_requests WHERE request_type = 'other'"
];

foreach ($stat_queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Set template variables
$page_title = "Parent Requests - Admin Dashboard";
$page_heading = "Parent Requests Management";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="badge bg-primary">
                <i class="fas fa-bell me-1"></i>
                <?php echo $stats['pending']; ?> Pending Requests
            </span>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-2x text-primary mb-2"></i>
                        <h5>Total Requests</h5>
                        <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h5>Pending</h5>
                        <h3 class="text-warning"><?php echo $stats['pending']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5>Approved</h5>
                        <h3 class="text-success"><?php echo $stats['approved']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h5>Rejected</h5>
                        <h3 class="text-danger"><?php echo $stats['rejected']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filter Requests</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Request Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="account_update" <?php echo $type_filter === 'account_update' ? 'selected' : ''; ?>>Account Update</option>
                            <option value="child_info" <?php echo $type_filter === 'child_info' ? 'selected' : ''; ?>>Child Information</option>
                            <option value="appointment_change" <?php echo $type_filter === 'appointment_change' ? 'selected' : ''; ?>>Appointment Change</option>
                            <option value="other" <?php echo $type_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Apply Filters
                            </button>
                            <a href="requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests List -->
        <?php if (count($requests) > 0): ?>
            <div class="requests-list">
                <?php foreach ($requests as $request): ?>
                    <div class="card request-card <?php echo $request['days_pending'] > 3 ? 'urgent' : 'normal'; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($request['parent_name']); ?>
                                        <?php if ($request['child_name']): ?>
                                            <small class="text-muted">- <?php echo htmlspecialchars($request['child_name']); ?></small>
                                        <?php endif; ?>
                                    </h5>
                                    <span class="badge bg-<?php 
                                        switch($request['status']) {
                                            case 'approved': echo 'success'; break;
                                            case 'rejected': echo 'danger'; break;
                                            default: echo 'warning';
                                        }
                                    ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['request_type'])); ?>
                                    </span>
                                    <?php if ($request['days_pending'] > 3): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Urgent
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <br>
                                        <span class="text-warning">
                                            <?php echo $request['days_pending']; ?> days pending
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($request['subject']); ?>
                            </div>

                            <div class="mb-3">
                                <strong>Message:</strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['message'])); ?></p>
                            </div>

                            <?php if ($request['contact_preference']): ?>
                                <div class="mb-3">
                                    <strong>Contact Preference:</strong>
                                    <?php echo ucfirst($request['contact_preference']); ?>
                                    <?php if ($request['contact_preference'] === 'phone' && $request['phone']): ?>
                                        - <?php echo $request['phone']; ?>
                                    <?php elseif ($request['contact_preference'] === 'email' && $request['email']): ?>
                                        - <?php echo $request['email']; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="d-flex gap-2">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" name="approve_request" class="btn btn-success btn-sm">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" name="approve_request" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php elseif ($request['processed_at']): ?>
                                <small class="text-muted">
                                    Processed on: <?php echo date('M d, Y H:i', strtotime($request['processed_at'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bell fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No requests found</h5>
                <p class="text-muted">
                    <?php if ($status_filter || $type_filter): ?>
                        Try adjusting your filters to see more results.
                    <?php else: ?>
                        There are currently no parent requests. Check back later.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Request Type Statistics -->
        <div class="card mt-5">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Request Type Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="bg-light p-3 rounded">
                            <h6 class="text-primary">Account Updates</h6>
                            <h4 class="text-primary"><?php echo $stats['account']; ?></h4>
                            <small class="text-muted"><?php echo round(($stats['account'] / max(1, $stats['total'])) * 100); ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="bg-light p-3 rounded">
                            <h6 class="text-success">Child Information</h6>
                            <h4 class="text-success"><?php echo $stats['child']; ?></h4>
                            <small class="text-muted"><?php echo round(($stats['child'] / max(1, $stats['total'])) * 100); ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="bg-light p-3 rounded">
                            <h6 class="text-warning">Appointment Changes</h6>
                            <h4 class="text-warning"><?php echo $stats['appointment']; ?></h4>
                            <small class="text-muted"><?php echo round(($stats['appointment'] / max(1, $stats['total'])) * 100); ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="bg-light p-3 rounded">
                            <h6 class="text-secondary">Other Requests</h6>
                            <h4 class="text-secondary"><?php echo $stats['other']; ?></h4>
                            <small class="text-muted"><?php echo round(($stats['other'] / max(1, $stats['total'])) * 100); ?>%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .request-card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .urgent {
                border-left: 4px solid #dc3545;
            }
            .normal {
                border-left: 4px solid #28a745;
            }
        </style>
<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
