<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $schedule_id = $_POST['schedule_id'];
        $status = $_POST['status'];
        $hospital_id = $_POST['hospital_id'];
        $notes = $_POST['notes'];

        $query = "UPDATE vaccination_schedule SET status = :status, hospital_id = :hospital_id, notes = :notes WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':hospital_id', $hospital_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $schedule_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vaccination status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update status. Please try again.";
        }
        
        header("Location: vaccination_dates.php");
        exit();
    }
}

// Get vaccination schedules with filters
$where_conditions = [];
$params = [];

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_conditions[] = "vs.status = :status";
    $params[':status'] = $_GET['status'];
}

if (isset($_GET['child_id']) && $_GET['child_id'] !== '') {
    $where_conditions[] = "vs.child_id = :child_id";
    $params[':child_id'] = $_GET['child_id'];
}

if (isset($_GET['hospital_id']) && $_GET['hospital_id'] !== '') {
    $where_conditions[] = "vs.hospital_id = :hospital_id";
    $params[':hospital_id'] = $_GET['hospital_id'];
}

if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $where_conditions[] = "vs.scheduled_date >= :date_from";
    $params[':date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $where_conditions[] = "vs.scheduled_date <= :date_to";
    $params[':date_to'] = $_GET['date_to'];
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT vs.*, c.name as child_name, c.date_of_birth, 
                 v.name as vaccine_name, v.recommended_age_months,
                 h.name as hospital_name, u.full_name as parent_name
          FROM vaccination_schedule vs
          JOIN children c ON vs.child_id = c.id
          JOIN vaccines v ON vs.vaccine_id = v.id
          JOIN users u ON c.parent_id = u.id
          LEFT JOIN hospitals h ON vs.hospital_id = h.id
          $where_clause
          ORDER BY vs.scheduled_date DESC, vs.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all children for filter
$query = "SELECT * FROM children ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all hospitals for filter
$query = "SELECT * FROM hospitals ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set template variables
$page_title = "Vaccination Dates - Admin Dashboard";
$page_heading = "Vaccination Dates Management";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            
            <span class="badge bg-primary">Total: <?php echo count($schedules); ?> schedules</span>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filter Schedules</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="missed" <?php echo isset($_GET['status']) && $_GET['status'] === 'missed' ? 'selected' : ''; ?>>Missed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Child</label>
                        <select class="form-select" name="child_id">
                            <option value="">All Children</option>
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['id']; ?>" <?php echo isset($_GET['child_id']) && $_GET['child_id'] == $child['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hospital</label>
                        <select class="form-select" name="hospital_id">
                            <option value="">All Hospitals</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value="<?php echo $hospital['id']; ?>" <?php echo isset($_GET['hospital_id']) && $_GET['hospital_id'] == $hospital['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hospital['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>" placeholder="From">
                            <input type="date" class="form-control" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>" placeholder="To">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>Apply Filters
                        </button>
                        <a href="vaccination_dates.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vaccination Schedules Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Vaccination Schedules</h5>
            </div>
            <div class="card-body">
                <?php if (count($schedules) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Vaccine</th>
                                    <th>Scheduled Date</th>
                                    <th>Recommended Age</th>
                                    <th>Hospital</th>
                                    <th>Status</th>
                                    <th>Parent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): 
                                    $age = date_diff(date_create($schedule['date_of_birth']), date_create($schedule['scheduled_date']))->m;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($schedule['child_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">DOB: <?php echo date('M d, Y', strtotime($schedule['date_of_birth'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['vaccine_name']); ?></td>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($schedule['scheduled_date'])); ?></strong>
                                            <?php if (strtotime($schedule['scheduled_date']) < time() && $schedule['status'] === 'pending'): ?>
                                                <br>
                                                <small class="text-danger">Overdue</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $schedule['recommended_age_months']; ?> months</td>
                                        <td>
                                            <?php if ($schedule['hospital_name']): ?>
                                                <?php echo htmlspecialchars($schedule['hospital_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($schedule['status']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'missed': echo 'danger'; break;
                                                    default: echo 'warning';
                                                }
                                            ?>">
                                                <?php echo ucfirst($schedule['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['parent_name']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updateModal"
                                                    data-schedule-id="<?php echo $schedule['id']; ?>"
                                                    data-current-status="<?php echo $schedule['status']; ?>"
                                                    data-current-hospital="<?php echo $schedule['hospital_id']; ?>"
                                                    data-current-notes="<?php echo htmlspecialchars($schedule['notes'] ?? ''); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No vaccination schedules found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h5>Pending</h5>
                        <h3 class="text-warning">
                            <?php echo count(array_filter($schedules, fn($s) => $s['status'] === 'pending')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5>Completed</h5>
                        <h3 class="text-success">
                            <?php echo count(array_filter($schedules, fn($s) => $s['status'] === 'completed')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h5>Missed</h5>
                        <h3 class="text-danger">
                            <?php echo count(array_filter($schedules, fn($s) => $s['status'] === 'missed')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                        <h5>Total</h5>
                        <h3 class="text-info"><?php echo count($schedules); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div class="modal fade" id="updateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Vaccination Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="schedule_id" id="schedule_id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="missed">Missed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hospital</label>
                                <select class="form-select" name="hospital_id" id="hospital_id">
                                    <option value="">Select Hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>">
                                            <?php echo htmlspecialchars($hospital['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const updateModal = document.getElementById('updateModal');
            updateModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const scheduleId = button.getAttribute('data-schedule-id');
                const currentStatus = button.getAttribute('data-current-status');
                const currentHospital = button.getAttribute('data-current-hospital');
                const currentNotes = button.getAttribute('data-current-notes');

                document.getElementById('schedule_id').value = scheduleId;
                document.getElementById('status').value = currentStatus;
                document.getElementById('hospital_id').value = currentHospital;
                document.getElementById('notes').value = currentNotes;
            });
        </script>
<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
