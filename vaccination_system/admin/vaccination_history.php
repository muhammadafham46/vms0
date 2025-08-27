<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Check if child_id is provided
if (!isset($_GET['child_id'])) {
    header('Location: children.php');
    exit;
}

$child_id = $_GET['child_id'];

// Get child details
$query = "SELECT c.*, u.full_name as parent_name 
          FROM children c 
          JOIN users u ON c.parent_id = u.id 
          WHERE c.id = :child_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':child_id', $child_id);
$stmt->execute();
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    header('Location: children.php');
    exit;
}

// Get vaccination history for this child
$query = "SELECT vs.*, v.name as vaccine_name, v.description, h.name as hospital_name,
                 CASE 
                     WHEN vs.status = 'completed' THEN 'Completed'
                     WHEN vs.status = 'pending' THEN 'Pending'
                     WHEN vs.status = 'missed' THEN 'Missed'
                     ELSE vs.status
                 END as status_display
          FROM vaccination_schedule vs
          JOIN vaccines v ON vs.vaccine_id = v.id
          LEFT JOIN hospitals h ON vs.hospital_id = h.id
          WHERE vs.child_id = :child_id
          ORDER BY vs.scheduled_date ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':child_id', $child_id);
$stmt->execute();
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_vaccinations = count($vaccinations);
$completed = count(array_filter($vaccinations, fn($v) => $v['status'] === 'completed'));
$pending = count(array_filter($vaccinations, fn($v) => $v['status'] === 'pending'));
$missed = count(array_filter($vaccinations, fn($v) => $v['status'] === 'missed'));

// Set template variables
$page_title = "Vaccination History - Admin Dashboard";
$page_heading = "Vaccination History";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                
                <p class="text-muted mb-0">Child: <strong><?php echo htmlspecialchars($child['name']); ?></strong> | 
                Parent: <strong><?php echo htmlspecialchars($child['parent_name']); ?></strong></p>
            </div>
            <div>
                <a href="children.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Children
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-syringe fa-2x text-primary mb-2"></i>
                        <h5>Total Vaccinations</h5>
                        <h3 class="text-primary"><?php echo $total_vaccinations; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5>Completed</h5>
                        <h3 class="text-success"><?php echo $completed; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h5>Pending</h5>
                        <h3 class="text-warning"><?php echo $pending; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h5>Missed</h5>
                        <h3 class="text-danger"><?php echo $missed; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination History Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Vaccination Schedule</h5>
            </div>
            <div class="card-body">
                <?php if (count($vaccinations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Vaccine</th>
                                    <th>Scheduled Date</th>
                                    <th>Completed Date</th>
                                    <th>Hospital</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vaccinations as $vaccination): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></strong>
                                            <?php if ($vaccination['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($vaccination['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?></td>
                                        <td>
                                            <?php if ($vaccination['status'] === 'completed'): ?>
                                                <?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($vaccination['hospital_name']): ?>
                                                <?php echo htmlspecialchars($vaccination['hospital_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($vaccination['status']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'pending': echo 'warning'; break;
                                                    case 'missed': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo $vaccination['status_display']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($vaccination['notes']): ?>
                                                <?php echo htmlspecialchars($vaccination['notes']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No notes</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-syringe fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No vaccination records found</h5>
                        <p class="text-muted">This child doesn't have any vaccination schedule yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
