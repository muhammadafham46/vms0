<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Get vaccination schedule for all children of this parent
$query = "SELECT vs.*, v.name as vaccine_name, v.recommended_age_months, 
                 h.name as hospital_name, c.name as child_name,
                 c.date_of_birth
          FROM vaccination_schedule vs
          JOIN vaccines v ON vs.vaccine_id = v.id
          LEFT JOIN hospitals h ON vs.hospital_id = h.id
          JOIN children c ON vs.child_id = c.id
          WHERE c.parent_id = :parent_id 
          ORDER BY vs.scheduled_date ASC, c.name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$vaccination_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group vaccinations by status for better organization
$pending_vaccinations = array_filter($vaccination_schedule, function($v) {
    return $v['status'] === 'pending';
});
$completed_vaccinations = array_filter($vaccination_schedule, function($v) {
    return $v['status'] === 'completed';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Dates - Parent Panel</title>
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
        .vaccination-card {
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        .vaccination-card:hover {
            transform: translateY(-2px);
        }
        .status-pending {
            border-left-color: #ffc107;
        }
        .status-completed {
            border-left-color: #198754;
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
            <a class="nav-link active" href="vaccination_dates.php">
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
            <h2 class="fw-bold">Vaccination Dates</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Vaccinations</h6>
                        <h2 class="fw-bold"><?php echo count($vaccination_schedule); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending</h6>
                        <h2 class="fw-bold"><?php echo count($pending_vaccinations); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h6 class="card-title">Completed</h6>
                        <h2 class="fw-bold"><?php echo count($completed_vaccinations); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Vaccinations -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Vaccinations (<?php echo count($pending_vaccinations); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($pending_vaccinations) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($pending_vaccinations as $vaccination): ?>
                            <div class="col-md-6">
                                <div class="card vaccination-card status-pending">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0"><?php echo $vaccination['child_name']; ?></h6>
                                            <span class="badge bg-warning">Pending</span>
                                        </div>
                                        <p class="card-text mb-1">
                                            <strong>Vaccine:</strong> <?php echo $vaccination['vaccine_name']; ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <strong>Scheduled Date:</strong> 
                                            <?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?>
                                        </p>
                                        <?php if ($vaccination['hospital_name']): ?>
                                            <p class="card-text mb-1">
                                                <strong>Hospital:</strong> <?php echo $vaccination['hospital_name']; ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="card-text mb-0">
                                            <small class="text-muted">
                                                Recommended age: <?php echo $vaccination['recommended_age_months']; ?> months
                                            </small>
                                        </p>
                                        <?php if (!$vaccination['hospital_name']): ?>
                                            <div class="mt-3">
                                                <a href="book_hospital.php?vaccination_id=<?php echo $vaccination['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-hospital me-1"></i>Book Hospital
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No pending vaccinations.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completed Vaccinations -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Completed Vaccinations (<?php echo count($completed_vaccinations); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($completed_vaccinations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Vaccine</th>
                                    <th>Completed Date</th>
                                    <th>Hospital</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_vaccinations as $vaccination): ?>
                                    <tr>
                                        <td><?php echo $vaccination['child_name']; ?></td>
                                        <td><?php echo $vaccination['vaccine_name']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?></td>
                                        <td><?php echo $vaccination['hospital_name'] ?: 'Not specified'; ?></td>
                                        <td>
                                            <span class="badge bg-success">Completed</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No completed vaccinations yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
