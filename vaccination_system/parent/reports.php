<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Get vaccination history for all children of this parent
$query = "SELECT vs.*, v.name as vaccine_name, v.recommended_age_months, 
                 h.name as hospital_name, c.name as child_name,
                 c.date_of_birth, c.gender, c.blood_group
          FROM vaccination_schedule vs
          JOIN vaccines v ON vs.vaccine_id = v.id
          LEFT JOIN hospitals h ON vs.hospital_id = h.id
          JOIN children c ON vs.child_id = c.id
          WHERE c.parent_id = :parent_id 
          ORDER BY c.name ASC, vs.scheduled_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$vaccination_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group vaccinations by child
$children_vaccinations = [];
foreach ($vaccination_history as $vaccination) {
    $child_id = $vaccination['child_id'];
    if (!isset($children_vaccinations[$child_id])) {
        $children_vaccinations[$child_id] = [
            'child_info' => [
                'name' => $vaccination['child_name'],
                'date_of_birth' => $vaccination['date_of_birth'],
                'gender' => $vaccination['gender'],
                'blood_group' => $vaccination['blood_group']
            ],
            'vaccinations' => []
        ];
    }
    $children_vaccinations[$child_id]['vaccinations'][] = $vaccination;
}

// Get statistics
$total_vaccinations = count($vaccination_history);
$completed_vaccinations = array_filter($vaccination_history, function($v) {
    return $v['status'] === 'completed';
});
$pending_vaccinations = array_filter($vaccination_history, function($v) {
    return $v['status'] === 'pending';
});

// Calculate vaccination coverage by age
$vaccination_coverage = [];
foreach ($children_vaccinations as $child_id => $data) {
    $age = calculateAge($data['child_info']['date_of_birth']);
    $completed = array_filter($data['vaccinations'], function($v) {
        return $v['status'] === 'completed';
    });
    $coverage = count($completed) > 0 ? round((count($completed) / count($data['vaccinations'])) * 100, 1) : 0;
    $vaccination_coverage[$child_id] = [
        'child_name' => $data['child_info']['name'],
        'age' => $age,
        'total_vaccines' => count($data['vaccinations']),
        'completed' => count($completed),
        'coverage' => $coverage
    ];
}

// Helper function to calculate age
function calculateAge($birthDate) {
    $birthDate = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y . ' years ' . $age->m . ' months';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Reports - Parent Panel</title>
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
        .progress {
            height: 20px;
        }
        .vaccination-card {
            border-left: 4px solid;
            margin-bottom: 15px;
        }
        .status-completed {
            border-left-color: #198754;
        }
        .status-pending {
            border-left-color: #ffc107;
        }
        .coverage-chart {
            height: 300px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 10px;
            padding: 20px;
            color: white;
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
            <a class="nav-link" href="appointments.php">
                <i class="fas fa-calendar-check me-2"></i>My Appointments
            </a>
            <a class="nav-link active" href="reports.php">
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
            <h2 class="fw-bold">Vaccination Reports</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Report
                </button>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Children</h6>
                        <h2 class="fw-bold"><?php echo count($children_vaccinations); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Vaccinations</h6>
                        <h2 class="fw-bold"><?php echo $total_vaccinations; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h6 class="card-title">Completed</h6>
                        <h2 class="fw-bold"><?php echo count($completed_vaccinations); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending</h6>
                        <h2 class="fw-bold"><?php echo count($pending_vaccinations); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination Coverage by Child -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Vaccination Coverage by Child</h5>
            </div>
            <div class="card-body">
                <?php if (count($vaccination_coverage) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Age</th>
                                    <th>Total Vaccines</th>
                                    <th>Completed</th>
                                    <th>Coverage</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vaccination_coverage as $coverage): ?>
                                    <tr>
                                        <td><strong><?php echo $coverage['child_name']; ?></strong></td>
                                        <td><?php echo $coverage['age']; ?></td>
                                        <td><?php echo $coverage['total_vaccines']; ?></td>
                                        <td><?php echo $coverage['completed']; ?></td>
                                        <td><?php echo $coverage['coverage']; ?>%</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar 
                                                    <?php echo $coverage['coverage'] >= 80 ? 'bg-success' : 
                                                          ($coverage['coverage'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>"
                                                    style="width: <?php echo $coverage['coverage']; ?>%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No vaccination data available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Vaccination History -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Detailed Vaccination History</h5>
            </div>
            <div class="card-body">
                <?php if (count($children_vaccinations) > 0): ?>
                    <?php foreach ($children_vaccinations as $child_id => $data): ?>
                        <div class="mb-5">
                            <h5 class="mb-3">
                                <i class="fas fa-child me-2"></i>
                                <?php echo $data['child_info']['name']; ?>
                                <small class="text-muted">
                                    (<?php echo calculateAge($data['child_info']['date_of_birth']); ?>, 
                                    <?php echo ucfirst($data['child_info']['gender']); ?>,
                                    <?php echo $data['child_info']['blood_group'] ?: 'Blood group not specified'; ?>)
                                </small>
                            </h5>
                            
                            <?php if (count($data['vaccinations']) > 0): ?>
                                <div class="row">
                                    <?php foreach ($data['vaccinations'] as $vaccination): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card vaccination-card 
                                                <?php echo $vaccination['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0"><?php echo $vaccination['vaccine_name']; ?></h6>
                                                        <span class="badge 
                                                            <?php echo $vaccination['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo ucfirst($vaccination['status']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <p class="card-text mb-1">
                                                        <strong>Recommended Age:</strong> <?php echo $vaccination['recommended_age_months']; ?> months
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
                                                    
                                                    <?php if ($vaccination['status'] === 'completed' && isset($vaccination['completed_at'])): ?>
                                                        <p class="card-text mb-1">
                                                            <strong>Completed:</strong> 
                                                            <?php echo date('M d, Y', strtotime($vaccination['completed_at'])); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($vaccination['notes']): ?>
                                                        <p class="card-text mb-0">
                                                            <strong>Notes:</strong> <?php echo $vaccination['notes']; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No vaccinations scheduled for this child.</p>
                            <?php endif; ?>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-medical fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No vaccination records found</h5>
                        <p class="text-muted">Vaccination reports will appear here once you schedule appointments.</p>
                        <a href="book_hospital.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-1"></i>Schedule Vaccination
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vaccination Summary Chart (Placeholder) -->
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Vaccination Summary</h5>
            </div>
            <div class="card-body">
                <div class="coverage-chart text-center d-flex align-items-center justify-content-center">
                    <div>
                        <i class="fas fa-chart-line fa-5x mb-3"></i>
                        <h4>Vaccination Coverage Analytics</h4>
                        <p>Interactive charts showing vaccination progress over time</p>
                        <small>(Chart visualization would be implemented with Chart.js or similar library)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
