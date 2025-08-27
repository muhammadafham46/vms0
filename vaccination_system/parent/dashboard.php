<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Get parent's children
$query = "SELECT * FROM children WHERE parent_id = :parent_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming vaccinations
$upcoming_vaccinations = [];
if (count($children) > 0) {
    $child_ids = array_column($children, 'id');
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    
    $query = "SELECT vs.*, v.name as vaccine_name, v.recommended_age_months, 
                     h.name as hospital_name, c.name as child_name
              FROM vaccination_schedule vs
              JOIN vaccines v ON vs.vaccine_id = v.id
              LEFT JOIN hospitals h ON vs.hospital_id = h.id
              JOIN children c ON vs.child_id = c.id
              WHERE vs.child_id IN ($placeholders) 
              AND vs.status = 'pending'
              AND vs.scheduled_date >= CURDATE()
              ORDER BY vs.scheduled_date ASC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute($child_ids);
    $upcoming_vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get vaccination history
$vaccination_history = [];
if (count($children) > 0) {
    $query = "SELECT vs.*, v.name as vaccine_name, h.name as hospital_name, c.name as child_name
              FROM vaccination_schedule vs
              JOIN vaccines v ON vs.vaccine_id = v.id
              LEFT JOIN hospitals h ON vs.hospital_id = h.id
              JOIN children c ON vs.child_id = c.id
              WHERE vs.child_id IN ($placeholders) 
              AND (vs.status = 'completed' OR vs.status = 'missed')
              ORDER BY vs.scheduled_date DESC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute($child_ids);
    $vaccination_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get appointments
$appointments = [];
$query = "SELECT a.*, h.name as hospital_name, v.name as vaccine_name, c.name as child_name
          FROM appointments a
          JOIN hospitals h ON a.hospital_id = h.id
          JOIN vaccines v ON a.vaccine_id = v.id
          JOIN children c ON a.child_id = c.id
          WHERE a.parent_id = :parent_id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - Vaccination System</title>
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
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .child-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center py-4">
            <i class="fas fa-syringe fa-2x mb-2"></i>
            <h5>Vaccination System</h5>
            <small>Parent Panel</small>
        </div>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
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
            <a class="nav-link" href="appointments.php">
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
            <h2 class="fw-bold">Parent Dashboard</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>Quick Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="children.php?action=add"><i class="fas fa-plus me-2"></i>Add Child</a></li>
                        <li><a class="dropdown-item" href="book_hospital.php"><i class="fas fa-calendar-plus me-2"></i>New Appointment</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                    </ul>
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
                                <h6 class="card-title">My Children</h6>
                                <h2 class="fw-bold"><?php echo count($children); ?></h2>
                            </div>
                            <i class="fas fa-child fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Upcoming Vaccinations</h6>
                                <h2 class="fw-bold"><?php echo count($upcoming_vaccinations); ?></h2>
                            </div>
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Completed Vaccinations</h6>
                                <h2 class="fw-bold"><?php echo count(array_filter($vaccination_history, fn($v) => $v['status'] === 'completed')); ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Pending Appointments</h6>
                                <h2 class="fw-bold"><?php echo count(array_filter($appointments, fn($a) => $a['status'] === 'pending')); ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Children -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold">My Children</h4>
                    <a href="children.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Child
                    </a>
                </div>
                <?php if (count($children) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($children as $child): 
                            $age = date_diff(date_create($child['date_of_birth']), date_create('today'))->y;
                            $months = date_diff(date_create($child['date_of_birth']), date_create('today'))->m;
                        ?>
                            <div class="col-md-4">
                                <div class="card child-card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-child fa-3x text-<?php echo $child['gender'] === 'male' ? 'primary' : 'danger'; ?>"></i>
                                        </div>
                                        <h5 class="card-title"><?php echo $child['name']; ?></h5>
                                        <p class="text-muted">
                                            <?php echo $age > 0 ? $age . ' years' : $months . ' months'; ?> old • 
                                            <span class="text-<?php echo $child['gender'] === 'male' ? 'primary' : 'danger'; ?>">
                                                <?php echo ucfirst($child['gender']); ?>
                                            </span>
                                        </p>
                                        <?php if ($child['blood_group']): ?>
                                            <span class="badge bg-info">Blood Group: <?php echo $child['blood_group']; ?></span>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <a href="children.php?action=edit&id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="vaccination_dates.php?child_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-success me-1">
                                                <i class="fas fa-syringe"></i>
                                            </a>
                                            <a href="book_hospital.php?child_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-child fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No children registered yet</h5>
                        <p class="text-muted">Add your first child to get started with vaccination scheduling.</p>
                        <a href="children.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add First Child
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Vaccinations -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Upcoming Vaccinations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($upcoming_vaccinations) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($upcoming_vaccinations as $vaccination): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo $vaccination['vaccine_name']; ?></h6>
                                                <small class="text-muted">
                                                    For: <?php echo $vaccination['child_name']; ?> • 
                                                    <?php echo $vaccination['recommended_age_months']; ?> months
                                                </small>
                                                <br>
                                                <small>
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?>
                                                    <?php if ($vaccination['hospital_name']): ?>
                                                        • <i class="fas fa-hospital me-1"></i><?php echo $vaccination['hospital_name']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-warning">Pending</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No upcoming vaccinations</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Recent Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($appointments) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($appointments as $appointment): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo $appointment['vaccine_name']; ?></h6>
                                                <small class="text-muted">
                                                    For: <?php echo $appointment['child_name']; ?> • 
                                                    <?php echo $appointment['hospital_name']; ?>
                                                </small>
                                                <br>
                                                <small>
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                    <i class="fas fa-clock ms-2 me-1"></i>
                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </small>
                                            </div>
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
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No appointments yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                        <h5>Add Child</h5>
                        <p>Register a new child</p>
                        <a href="children.php?action=add" class="btn btn-primary">Add Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-plus fa-3x text-success mb-3"></i>
                        <h5>Book Appointment</h5>
                        <p>Schedule vaccination</p>
                        <a href="book_hospital.php" class="btn btn-success">Book Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-file-medical fa-3x text-info mb-3"></i>
                        <h5>View Reports</h5>
                        <p>Check vaccination history</p>
                        <a href="reports.php" class="btn btn-info">View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
