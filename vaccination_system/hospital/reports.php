<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'includes/feature_access.php';
redirectIfNotHospital();

$database = new Database();
$db = $database->getConnection();

// Get hospital information
$query = "SELECT h.* FROM hospitals h
          JOIN users u ON h.id = u.id 
          WHERE u.id = :user_id AND u.user_type = 'hospital'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly statistics
$monthly_stats = [];
$query = "SELECT 
            DATE_FORMAT(a.appointment_date, '%Y-%m') as month,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
          FROM appointments a
          JOIN hospitals h ON h.id = a.hospital_id 
          WHERE h.id = :hospital_id
          GROUP BY DATE_FORMAT(a.appointment_date, '%Y-%m')
          ORDER BY month DESC
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vaccine usage statistics
$vaccine_stats = [];
$query = "SELECT 
            v.name as vaccine_name,
            COUNT(*) as total_used,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed
          FROM appointments a
          JOIN vaccines v ON a.vaccine_id = v.id
          JOIN hospitals h ON h.id = a.hospital_id
          WHERE h.id = :hospital_id
          GROUP BY v.id, v.name
          ORDER BY total_used DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$vaccine_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get age group statistics
$age_stats = [];
$query = "SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, c.date_of_birth, CURDATE()) < 1 THEN 'Under 1 year'
                WHEN TIMESTAMPDIFF(YEAR, c.date_of_birth, CURDATE()) < 5 THEN '1-4 years'
                ELSE '5+ years'
            END as age_group,
            COUNT(*) as total
          FROM appointments a
          JOIN children c ON a.child_id = c.id
          JOIN hospitals h ON h.id = a.hospital_id
          WHERE h.id = :hospital_id AND a.status = 'completed'
          GROUP BY age_group
          ORDER BY age_group";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$age_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Hospital Panel</title>
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center py-4">
            <i class="fas fa-hospital fa-2x mb-2"></i>
            <h5>Hospital Panel</h5>
        </div>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="appointments.php">
                <i class="fas fa-calendar-check me-2"></i>Appointments
            </a>
            <a class="nav-link" href="vaccine_inventory.php">
                <i class="fas fa-syringe me-2"></i>Vaccine Inventory
            </a>
            <a class="nav-link active" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user me-2"></i>Profile
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Reports & Analytics</h2>
                <p class="text-muted">View detailed statistics and reports</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
                <button class="btn btn-primary" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>

        <!-- Monthly Statistics -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Monthly Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                                <th>Cancelled</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $stat): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></td>
                                    <td><?php echo $stat['total_appointments']; ?></td>
                                    <td><?php echo $stat['completed']; ?></td>
                                    <td><?php echo $stat['cancelled']; ?></td>
                                    <td>
                                        <?php 
                                            $completion_rate = ($stat['total_appointments'] > 0) 
                                                ? round(($stat['completed'] / $stat['total_appointments']) * 100, 1) 
                                                : 0;
                                            echo $completion_rate . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Vaccine Usage -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-syringe me-2"></i>Vaccine Usage Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                                <th>Usage Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccine_stats as $stat): ?>
                                <tr>
                                    <td><?php echo $stat['vaccine_name']; ?></td>
                                    <td><?php echo $stat['total_used']; ?></td>
                                    <td><?php echo $stat['completed']; ?></td>
                                    <td>
                                        <?php 
                                            $usage_rate = ($stat['total_used'] > 0) 
                                                ? round(($stat['completed'] / $stat['total_used']) * 100, 1) 
                                                : 0;
                                            echo $usage_rate . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Age Group Distribution -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Age Group Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($age_stats as $stat): ?>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h3 class="fw-bold mb-3"><?php echo $stat['total']; ?></h3>
                                    <p class="text-muted mb-0"><?php echo $stat['age_group']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToPDF() {
            // Implement PDF export functionality
            alert('PDF export functionality will be implemented here');
        }
    </script>
</body>
</html>
