<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    $query = "UPDATE appointments SET status = 'cancelled' 
              WHERE id = :appointment_id AND parent_id = :parent_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->bindParam(':parent_id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Appointment cancelled successfully!";
    } else {
        $error = "Failed to cancel appointment. Please try again.";
    }
}

// Get all appointments for this parent
$query = "SELECT a.*, h.name as hospital_name, h.address as hospital_address,
                 h.phone as hospital_phone, v.name as vaccine_name,
                 c.name as child_name
          FROM appointments a
          LEFT JOIN hospitals h ON a.hospital_id = h.id
          LEFT JOIN vaccines v ON a.vaccine_id = v.id
          LEFT JOIN children c ON a.child_id = c.id
          WHERE a.parent_id = :parent_id 
          ORDER BY a.appointment_date DESC, a.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group appointments by status
$pending_appointments = array_filter($appointments, function($a) {
    return $a['status'] === 'pending';
});
$confirmed_appointments = array_filter($appointments, function($a) {
    return $a['status'] === 'confirmed';
});
$completed_appointments = array_filter($appointments, function($a) {
    return $a['status'] === 'completed';
});
$cancelled_appointments = array_filter($appointments, function($a) {
    return $a['status'] === 'cancelled';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Parent Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
        }
        .sidebar .nav-link {
            color: white;
            padding: 15px 20px;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .appointment-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-pending {
            border-left-color: #ffc107;
        }
        .status-confirmed {
            border-left-color: #007bff;
        }
        .status-completed {
            border-left-color: #198754;
        }
        .status-cancelled {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center py-4">
            <i class="fas fa-user fa-2x mb-2"></i>
            <h5>Parent Panel</h5>
        </div>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="children.php">
                <i class="fas fa-child me-2"></i>My Children
            </a>
            <a class="nav-link" href="vaccination_dates.php">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Dates
            </a>
            <a class="nav-link" href="book_hospital.php">
                <i class="fas fa-hospital me-2"></i>Book Hospital
            </a>
            <a class="nav-link active" href="appointments.php">
                <i class="fas fa-calendar-check me-2"></i>My Appointments
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-file-medical me-2"></i>Vaccination Reports
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user me-2"></i>My Profile
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">My Appointments</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total</h6>
                        <h2 class="fw-bold"><?php echo count($appointments); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending</h6>
                        <h2 class="fw-bold"><?php echo count($pending_appointments); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h6 class="card-title">Confirmed</h6>
                        <h2 class="fw-bold"><?php echo count($confirmed_appointments); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h6 class="card-title">Completed</h6>
                        <h2 class="fw-bold"><?php echo count($completed_appointments); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Appointments -->
        <?php if (count($pending_appointments) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Appointments (<?php echo count($pending_appointments); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($pending_appointments as $appointment): ?>
                    <div class="col-md-6">
                        <div class="card appointment-card status-pending">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo $appointment['child_name']; ?></h6>
                                    <span class="badge bg-warning">Pending</span>
                                </div>
                                
                                <p class="card-text mb-1">
                                    <strong>Vaccine:</strong> <?php echo $appointment['vaccine_name']; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Hospital:</strong> 
                                    <?php if ($appointment['hospital_name']): ?>
                                        <?php echo $appointment['hospital_name']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Appointment Date:</strong> 
                                    <?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?>
                                </p>
                                
                                <p class="card-text mb-3">
                                    <strong>Created:</strong> 
                                    <?php echo date('M d, Y', strtotime($appointment['created_at'])); ?>
                                </p>
                                
                                <div class="d-flex gap-2">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                    <?php if (!$appointment['hospital_name']): ?>
                                        <a href="book_hospital.php" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-hospital me-1"></i>Book Hospital
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Confirmed Appointments -->
        <?php if (count($confirmed_appointments) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Confirmed Appointments (<?php echo count($confirmed_appointments); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($confirmed_appointments as $appointment): ?>
                    <div class="col-md-6">
                        <div class="card appointment-card status-confirmed">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo $appointment['child_name']; ?></h6>
                                    <span class="badge bg-info">Confirmed</span>
                                </div>
                                
                                <p class="card-text mb-1">
                                    <strong>Vaccine:</strong> <?php echo $appointment['vaccine_name']; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Hospital:</strong> <?php echo $appointment['hospital_name']; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Address:</strong> <?php echo $appointment['hospital_address']; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Phone:</strong> <?php echo $appointment['hospital_phone']; ?>
                                </p>
                                
                                <p class="card-text mb-1">
                                    <strong>Appointment Date:</strong> 
                                    <?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?>
                                </p>
                                
                                <div class="mt-3">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-directions me-1"></i>Get Directions
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Completed Appointments -->
        <?php if (count($completed_appointments) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-double me-2"></i>Completed Appointments (<?php echo count($completed_appointments); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Child</th>
                                <th>Vaccine</th>
                                <th>Hospital</th>
                                <th>Appointment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['child_name']; ?></td>
                                <td><?php echo $appointment['vaccine_name']; ?></td>
                                <td><?php echo $appointment['hospital_name']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cancelled Appointments -->
        <?php if (count($cancelled_appointments) > 0): ?>
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Cancelled Appointments (<?php echo count($cancelled_appointments); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Child</th>
                                <th>Vaccine</th>
                                <th>Hospital</th>
                                <th>Appointment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelled_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['child_name']; ?></td>
                                <td><?php echo $appointment['vaccine_name']; ?></td>
                                <td><?php echo $appointment['hospital_name'] ?: 'N/A'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge bg-danger">Cancelled</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($appointments) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No appointments found</h5>
                <p class="text-muted">You haven't booked any appointments yet.</p>
                <a href="book_hospital.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus me-1"></i>Book Your First Appointment
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
