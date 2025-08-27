<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_booking'])) {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $notes = $_POST['notes'];

        $query = "UPDATE appointments SET status = :status, appointment_date = :appointment_date, 
                  appointment_time = :appointment_time, notes = :notes WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':appointment_date', $appointment_date);
        $stmt->bindParam(':appointment_time', $appointment_time);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $booking_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Booking updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update booking. Please try again.";
        }
    } elseif (isset($_POST['delete_booking'])) {
        $booking_id = $_POST['booking_id'];

        $query = "DELETE FROM appointments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $booking_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Booking deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete booking. Please try again.";
        }
    }
    
    header("Location: bookings.php");
    exit();
}

// Get bookings with filters
$status_filter = $_GET['status'] ?? '';
$hospital_filter = $_GET['hospital_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $status_filter;
}

if ($hospital_filter) {
    $where_conditions[] = "a.hospital_id = :hospital_id";
    $params[':hospital_id'] = $hospital_filter;
}

if ($date_from) {
    $where_conditions[] = "a.appointment_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "a.appointment_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT a.*, 
                 u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone,
                 c.name as child_name, c.date_of_birth,
                 v.name as vaccine_name, v.recommended_age_months,
                 h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone,
                 TIMESTAMPDIFF(DAY, a.appointment_date, CURDATE()) as days_until
          FROM appointments a
          JOIN users u ON a.parent_id = u.id
          JOIN children c ON a.child_id = c.id
          JOIN vaccines v ON a.vaccine_id = v.id
          JOIN hospitals h ON a.hospital_id = h.id
          $where_clause
          ORDER BY a.appointment_date, a.appointment_time";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all hospitals for filter
$query = "SELECT * FROM hospitals WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stat_queries = [
    'total' => "SELECT COUNT(*) as count FROM appointments",
    'pending' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'",
    'approved' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'approved'",
    'completed' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'",
    'rejected' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'rejected'",
    'today' => "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()",
    'upcoming' => "SELECT COUNT(*) as count FROM appointments WHERE appointment_date > CURDATE()",
    'past' => "SELECT COUNT(*) as count FROM appointments WHERE appointment_date < CURDATE()"
];

foreach ($stat_queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Set template variables
$page_title = "Booking Details - Admin Dashboard";
$page_heading = "Appointment Booking Details";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            
            <span class="badge bg-primary">
                <i class="fas fa-calendar me-1"></i>
                <?php echo $stats['total']; ?> Total Bookings
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
                        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                        <h5>Completed</h5>
                        <h3 class="text-primary"><?php echo $stats['completed']; ?></h3>
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

        <!-- Date Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                        <h5>Today's Appointments</h5>
                        <h3 class="text-info"><?php echo $stats['today']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-plus fa-2x text-success mb-2"></i>
                        <h5>Upcoming</h5>
                        <h3 class="text-success"><?php echo $stats['upcoming']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-minus fa-2x text-secondary mb-2"></i>
                        <h5>Past Appointments</h5>
                        <h3 class="text-secondary"><?php echo $stats['past']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filter Bookings</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value 'rejected' <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hospital</label>
                        <select class="form-select" name="hospital_id">
                            <option value="">All Hospitals</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value="<?php echo $hospital['id']; ?>" <?php echo $hospital_filter == $hospital['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hospital['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>" placeholder="From">
                            <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>" placeholder="To">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Apply Filters
                            </button>
                            <a href="bookings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (count($bookings) > 0): ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $booking): 
                    $card_class = '';
                    if ($booking['appointment_date'] == date('Y-m-d')) {
                        $card_class = 'today';
                    } elseif ($booking['days_until'] < 0) {
                        $card_class = 'urgent';
                    } elseif ($booking['days_until'] > 0) {
                        $card_class = 'upcoming';
                    }
                ?>
                    <div class="card booking-card <?php echo $card_class; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($booking['child_name']); ?>
                                        <small class="text-muted">(<?php echo date_diff(date_create($booking['date_of_birth']), date_create('today'))->y; ?> years)</small>
                                    </h5>
                                    <div>
                                        <span class="badge bg-<?php 
                                            switch($booking['status']) {
                                                case 'approved': echo 'success'; break;
                                                case 'completed': echo 'primary'; break;
                                                case 'rejected': echo 'danger'; break;
                                                default: echo 'warning';
                                            }
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                        <?php if ($booking['appointment_date'] == date('Y-m-d')): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-calendar-day me-1"></i>Today
                                            </span>
                                        <?php elseif ($booking['days_until'] < 0): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <strong><?php echo date('M d, Y', strtotime($booking['appointment_date'])); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('h:i A', strtotime($booking['appointment_time'])); ?>
                                        <?php if ($booking['days_until'] > 0): ?>
                                            <br>
                                            <span class="text-info">in <?php echo $booking['days_until']; ?> days</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Vaccine:</strong>
                                    <br>
                                    <?php echo htmlspecialchars($booking['vaccine_name']); ?>
                                    <small class="text-muted">(<?php echo $booking['recommended_age_months']; ?> months)</small>
                                </div>
                                <div class="col-md-6">
                                    <strong>Hospital:</strong>
                                    <br>
                                    <?php echo htmlspecialchars($booking['hospital_name']); ?>
                                    <?php if ($booking['hospital_phone']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo $booking['hospital_phone']; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Parent:</strong>
                                    <br>
                                    <?php echo htmlspecialchars($booking['parent_name']); ?>
                                    <?php if ($booking['parent_email']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i><?php echo $booking['parent_email']; ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($booking['parent_phone']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo $booking['parent_phone']; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($booking['notes']): ?>
                                        <strong>Notes:</strong>
                                        <br>
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editBookingModal"
                                        data-booking-id="<?php echo $booking['id']; ?>"
                                        data-current-status="<?php echo $booking['status']; ?>"
                                        data-current-date="<?php echo $booking['appointment_date']; ?>"
                                        data-current-time="<?php echo $booking['appointment_time']; ?>"
                                        data-current-notes="<?php echo htmlspecialchars($booking['notes'] ?? ''); ?>">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteBookingModal"
                                        data-booking-id="<?php echo $booking['id']; ?>"
                                        data-booking-child="<?php echo htmlspecialchars($booking['child_name']); ?>"
                                        data-booking-date="<?php echo date('M d, Y', strtotime($booking['appointment_date'])); ?>">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No bookings found</h5>
                <p class="text-muted">
                    <?php if ($status_filter || $hospital_filter || $date_from || $date_to): ?>
                        Try adjusting your filters to see more results.
                    <?php else: ?>
                        There are currently no appointment bookings. Check back later.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Edit Booking Modal -->
        <div class="modal fade" id="editBookingModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Booking</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="booking_id" id="booking_id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Appointment Date</label>
                                    <input type="date" class="form-control" name="appointment_date" id="appointment_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Appointment Time</label>
                                    <select class="form-select" name="appointment_time" id="appointment_time" required>
                                        <?php 
                                        $start = strtotime('09:00');
                                        $end = strtotime('17:00');
                                        for ($time = $start; $time <= $end; $time = strtotime('+30 minutes', $time)): ?>
                                            <option value="<?php echo date('H:i:s', $time); ?>">
                                                <?php echo date('h:i A', $time); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_booking" class="btn btn-primary">Update Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Booking Modal -->
        <div class="modal fade" id="deleteBookingModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="booking_id" id="delete_booking_id">
                        <div class="modal-body">
                            <p>Are you sure you want to delete the booking for "<strong id="delete_booking_child"></strong>" scheduled on "<strong id="delete_booking_date"></strong>"?</p>
                            <p class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Warning: This action cannot be undone.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_booking" class="btn btn-danger">Delete Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            .booking-card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .urgent {
                border-left: 4px solid #dc3545;
            }
            .today {
                border-left: 4px solid #28a745;
            }
            .upcoming {
                border-left: 4px solid #007bff;
            }
        </style>

        <script>
            // Edit modal setup
            const editModal = document.getElementById('editBookingModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const bookingId = button.getAttribute('data-booking-id');
                const currentStatus = button.getAttribute('data-current-status');
                const currentDate = button.getAttribute('data-current-date');
                const currentTime = button.getAttribute('data-current-time');
                const currentNotes = button.getAttribute('data-current-notes');

                document.getElementById('booking_id').value = bookingId;
                document.getElementById('status').value = currentStatus;
                document.getElementById('appointment_date').value = currentDate;
                document.getElementById('appointment_time').value = currentTime;
                document.getElementById('notes').value = currentNotes;
            });

            // Delete modal setup
            const deleteModal = document.getElementById('deleteBookingModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const bookingId = button.getAttribute('data-booking-id');
                const bookingChild = button.getAttribute('data-booking-child');
                const bookingDate = button.getAttribute('data-booking-date');

                document.getElementById('delete_booking_id').value = bookingId;
                document.getElementById('delete_booking_child').textContent = bookingChild;
                document.getElementById('delete_booking_date').textContent = bookingDate;
            });
        </script>
<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
