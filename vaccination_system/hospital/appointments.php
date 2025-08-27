<?php
define('SECURE_ACCESS', true);  // ye line zaroori hai

// Use the new template system
require_once 'includes/template.php';
$page_title = 'Appointments';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'includes/feature_access.php';

// Render header
render_hospital_header($page_title);
display_messages();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];

    // First get appointment details to know which vaccine and hospital
    $get_appointment_query = "SELECT * FROM appointments WHERE id = :appointment_id";
    $get_appointment_stmt = $db->prepare($get_appointment_query);
    $get_appointment_stmt->bindParam(':appointment_id', $appointment_id);
    $get_appointment_stmt->execute();
    $appointment = $get_appointment_stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {
        $update_query = "UPDATE appointments SET status = :status WHERE id = :appointment_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':status', $new_status);
        $update_stmt->bindParam(':appointment_id', $appointment_id);

        if ($update_stmt->execute()) {
            // If status is changed to 'completed', decrease vaccine stock
            if ($new_status === 'completed') {
                $decrease_stock_query = "UPDATE hospital_vaccines 
                                       SET quantity = quantity - 1 
                                       WHERE hospital_id = :hospital_id 
                                       AND vaccine_id = :vaccine_id 
                                       AND quantity > 0";
                $decrease_stock_stmt = $db->prepare($decrease_stock_query);
                $decrease_stock_stmt->bindParam(':hospital_id', $appointment['hospital_id']);
                $decrease_stock_stmt->bindParam(':vaccine_id', $appointment['vaccine_id']);
                
                if ($decrease_stock_stmt->execute()) {
                    $success = "Appointment status updated successfully and vaccine stock decreased!";
                } else {
                    $error = "Appointment status updated but failed to decrease vaccine stock.";
                }
            } else {
                $success = "Appointment status updated successfully!";
            }
        } else {
            $error = "Failed to update appointment status. Please try again.";
        }
    } else {
        $error = "Appointment not found.";
    }
}

// Get appointments
$query = "SELECT a.*, c.name as child_name, u.full_name as parent_name, v.name as vaccine_name 
          FROM appointments a
          JOIN children c ON a.child_id = c.id
          JOIN users u ON a.parent_id = u.id
          JOIN vaccines v ON a.vaccine_id = v.id
          WHERE a.hospital_id = :hospital_id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Appointments</h2>
        <p class="text-muted">Manage vaccination appointments</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fas fa-filter me-2"></i>Filter
        </button>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Appointments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Parent</th>
                        <th>Child</th>
                        <th>Vaccine</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                            </td>
                            <td><?php echo $appointment['parent_name']; ?></td>
                            <td><?php echo $appointment['child_name']; ?></td>
                            <td><?php echo $appointment['vaccine_name']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($appointment['status']) {
                                        case 'pending': echo 'warning'; break;
                                        case 'approved': echo 'success'; break;
                                        case 'completed': echo 'info'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?> status-badge">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $appointment['id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Update Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Update Status Modal -->
                        <div class="modal fade" id="updateStatusModal<?php echo $appointment['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Appointment Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">New Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $appointment['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php render_hospital_footer(); ?>
