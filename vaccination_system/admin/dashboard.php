<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];
$queries = [
    'total_children' => "SELECT COUNT(*) as count FROM children",
    'total_parents' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'parent'",
    'total_hospitals' => "SELECT COUNT(*) as count FROM hospitals",
    'pending_appointments' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'",
    'total_vaccines' => "SELECT COUNT(*) as count FROM vaccines",
    'completed_vaccinations' => "SELECT COUNT(*) as count FROM vaccination_schedule WHERE status = 'completed'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Get recent appointments
$recent_appointments = [];
$query = "SELECT a.*, u.full_name as parent_name, c.name as child_name, h.name as hospital_name, v.name as vaccine_name
          FROM appointments a
          JOIN users u ON a.parent_id = u.id
          JOIN children c ON a.child_id = c.id
          JOIN hospitals h ON a.hospital_id = h.id
          JOIN vaccines v ON a.vaccine_id = v.id
          ORDER BY a.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set template variables
$page_title = "Admin Dashboard - Vaccination System";
$page_heading = "Admin Dashboard";

// Start output buffering
ob_start();
?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Children</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_children']; ?></h2>
                            </div>
                            <i class="fas fa-child fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Parents</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_parents']; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Hospitals</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_hospitals']; ?></h2>
                            </div>
                            <i class="fas fa-hospital fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Pending Appointments</h6>
                                <h2 class="fw-bold"><?php echo $stats['pending_appointments']; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Vaccines</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_vaccines']; ?></h2>
                            </div>
                            <i class="fas fa-syringe fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Completed Vaccinations</h6>
                                <h2 class="fw-bold"><?php echo $stats['completed_vaccinations']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_appointments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Parent</th>
                                    <th>Child</th>
                                    <th>Hospital</th>
                                    <th>Vaccine</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo $appointment['parent_name']; ?></td>
                                        <td><?php echo $appointment['child_name']; ?></td>
                                        <td><?php echo $appointment['hospital_name']; ?></td>
                                        <td><?php echo $appointment['vaccine_name']; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($appointment['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'approved': echo 'success'; break;
                                                    case 'rejected': echo 'danger'; break;
                                                    case 'completed': echo 'info'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
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
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                        <h5>Add Hospital</h5>
                        <p>Register a new hospital</p>
                        <a href="hospitals.php?action=add" class="btn btn-primary">Add Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                        <h5>View Requests</h5>
                        <p>Check pending requests</p>
                        <a href="requests.php" class="btn btn-warning">View Requests</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h5>Generate Reports</h5>
                        <p>Create detailed reports</p>
                        <a href="reports.php" class="btn btn-info">Generate</a>
                    </div>
                </div>
            </div>
        </div>

<?php
// Get the content and include the template
$content = ob_get_clean();
include 'includes/template.php';
?>
