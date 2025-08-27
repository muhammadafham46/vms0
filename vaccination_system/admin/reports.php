<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get report data
$report_type = $_GET['report'] ?? 'overview';

// Overview statistics
$stats = [];
$queries = [
    'total_children' => "SELECT COUNT(*) as count FROM children",
    'total_parents' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'parent' AND status = 'active'",
    'total_hospitals' => "SELECT COUNT(*) as count FROM hospitals WHERE status = 'active'",
    'total_vaccinations' => "SELECT COUNT(*) as count FROM vaccination_schedule",
    'completed_vaccinations' => "SELECT COUNT(*) as count FROM vaccination_schedule WHERE status = 'completed'",
    'pending_vaccinations' => "SELECT COUNT(*) as count FROM vaccination_schedule WHERE status = 'pending'",
    'total_appointments' => "SELECT COUNT(*) as count FROM appointments",
    'completed_appointments' => "SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Vaccination completion by age group
$age_groups = [
    '0-6 months' => "SELECT COUNT(*) as count FROM vaccination_schedule vs 
                     JOIN children c ON vs.child_id = c.id 
                     WHERE TIMESTAMPDIFF(MONTH, c.date_of_birth, CURDATE()) BETWEEN 0 AND 6 
                     AND vs.status = 'completed'",
    '7-12 months' => "SELECT COUNT(*) as count FROM vaccination_schedule vs 
                      JOIN children c ON vs.child_id = c.id 
                      WHERE TIMESTAMPDIFF(MONTH, c.date_of_birth, CURDATE()) BETWEEN 7 AND 12 
                      AND vs.status = 'completed'",
    '1-2 years' => "SELECT COUNT(*) as count FROM vaccination_schedule vs 
                    JOIN children c ON vs.child_id = c.id 
                    WHERE TIMESTAMPDIFF(MONTH, c.date_of_birth, CURDATE()) BETWEEN 13 AND 24 
                    AND vs.status = 'completed'",
    '2+ years' => "SELECT COUNT(*) as count FROM vaccination_schedule vs 
                   JOIN children c ON vs.child_id = c.id 
                   WHERE TIMESTAMPDIFF(MONTH, c.date_of_birth, CURDATE()) > 24 
                   AND vs.status = 'completed'"
];

$age_stats = [];
foreach ($age_groups as $group => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $age_stats[$group] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Hospital performance
$hospital_performance = [];
$query = "SELECT h.name, 
                 COUNT(a.id) as total_appointments,
                 SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
                 SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected
          FROM hospitals h
          LEFT JOIN appointments a ON h.id = a.hospital_id
          WHERE h.status = 'active'
          GROUP BY h.id
          ORDER BY total_appointments DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$hospital_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vaccine usage
$vaccine_usage = [];
$query = "SELECT v.name, 
                 COUNT(vs.id) as total_scheduled,
                 SUM(CASE WHEN vs.status = 'completed' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN vs.status = 'pending' THEN 1 ELSE 0 END) as pending
          FROM vaccines v
          LEFT JOIN vaccination_schedule vs ON v.id = vs.vaccine_id
          GROUP BY v.id
          ORDER BY total_scheduled DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$vaccine_usage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $report_type = $_GET['report'] ?? 'overview';
    
    if ($export_type === 'pdf') {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="vaccination_report_' . $report_type . '_' . date('Y-m-d') . '.pdf"');
        
        // Simple PDF content
        $pdf_content = "%PDF-1.4\n";
        $pdf_content .= "1 0 obj\n";
        $pdf_content .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $pdf_content .= "endobj\n";
        $pdf_content .= "2 0 obj\n";
        $pdf_content .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $pdf_content .= "endobj\n";
        $pdf_content .= "3 0 obj\n";
        $pdf_content .= "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\n";
        $pdf_content .= "endobj\n";
        $pdf_content .= "4 0 obj\n";
        $pdf_content .= "<< /Length 1000 >>\n";
        $pdf_content .= "stream\n";
        $pdf_content .= "BT\n";
        $pdf_content .= "/F1 12 Tf\n";
        $pdf_content .= "50 750 Td\n";
        $pdf_content .= "(Vaccination System Report - " . ucfirst($report_type) . ") Tj\n";
        $pdf_content .= "50 730 Td\n";
        $pdf_content .= "(Generated on: " . date('Y-m-d H:i:s') . ") Tj\n";
        $pdf_content .= "ET\n";
        $pdf_content .= "endstream\n";
        $pdf_content .= "endobj\n";
        $pdf_content .= "xref\n";
        $pdf_content .= "0 5\n";
        $pdf_content .= "0000000000 65535 f \n";
        $pdf_content .= "0000000010 00000 n \n";
        $pdf_content .= "0000000053 00000 n \n";
        $pdf_content .= "0000000110 00000 n \n";
        $pdf_content .= "0000000234 00000 n \n";
        $pdf_content .= "trailer\n";
        $pdf_content .= "<< /Size 5 /Root 1 0 R >>\n";
        $pdf_content .= "startxref\n";
        $pdf_content .= "500\n";
        $pdf_content .= "%%EOF";
        
        echo $pdf_content;
        exit;
        
    } elseif ($export_type === 'excel') {
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="vaccination_report_' . $report_type . '_' . date('Y-m-d') . '.xls"');
        
        // Simple Excel content
        echo "<table border='1'>\n";
        echo "<tr><th colspan='5'>Vaccination System Report - " . ucfirst($report_type) . "</th></tr>\n";
        echo "<tr><th colspan='5'>Generated on: " . date('Y-m-d H:i:s') . "</th></tr>\n";
        echo "<tr><td colspan='5'></td></tr>\n";
        
        if ($report_type === 'overview') {
            echo "<tr><th>Metric</th><th>Value</th></tr>\n";
            echo "<tr><td>Total Children</td><td>" . $stats['total_children'] . "</td></tr>\n";
            echo "<tr><td>Registered Parents</td><td>" . $stats['total_parents'] . "</td></tr>\n";
            echo "<tr><td>Active Hospitals</td><td>" . $stats['total_hospitals'] . "</td></tr>\n";
            echo "<tr><td>Total Vaccinations</td><td>" . $stats['total_vaccinations'] . "</td></tr>\n";
            echo "<tr><td>Completed Vaccinations</td><td>" . $stats['completed_vaccinations'] . "</td></tr>\n";
            echo "<tr><td>Pending Vaccinations</td><td>" . $stats['pending_vaccinations'] . "</td></tr>\n";
            echo "<tr><td>Total Appointments</td><td>" . $stats['total_appointments'] . "</td></tr>\n";
            echo "<tr><td>Completed Appointments</td><td>" . $stats['completed_appointments'] . "</td></tr>\n";
            
        } elseif ($report_type === 'vaccinations') {
            echo "<tr><th>Vaccine Name</th><th>Total Scheduled</th><th>Completed</th><th>Pending</th><th>Completion Rate</th></tr>\n";
            foreach ($vaccine_usage as $vaccine) {
                $completion_rate = $vaccine['total_scheduled'] > 0 ? round(($vaccine['completed'] / $vaccine['total_scheduled']) * 100) : 0;
                echo "<tr>\n";
                echo "<td>" . htmlspecialchars($vaccine['name']) . "</td>\n";
                echo "<td>" . $vaccine['total_scheduled'] . "</td>\n";
                echo "<td>" . $vaccine['completed'] . "</td>\n";
                echo "<td>" . $vaccine['pending'] . "</td>\n";
                echo "<td>" . $completion_rate . "%</td>\n";
                echo "</tr>\n";
            }
            
        } elseif ($report_type === 'hospitals') {
            echo "<tr><th>Hospital Name</th><th>Total Appointments</th><th>Completed</th><th>Pending</th><th>Rejected</th><th>Success Rate</th></tr>\n";
            foreach ($hospital_performance as $hospital) {
                $success_rate = $hospital['total_appointments'] > 0 ? round(($hospital['completed'] / $hospital['total_appointments']) * 100) : 0;
                echo "<tr>\n";
                echo "<td>" . htmlspecialchars($hospital['name']) . "</td>\n";
                echo "<td>" . $hospital['total_appointments'] . "</td>\n";
                echo "<td>" . $hospital['completed'] . "</td>\n";
                echo "<td>" . $hospital['pending'] . "</td>\n";
                echo "<td>" . $hospital['rejected'] . "</td>\n";
                echo "<td>" . $success_rate . "%</td>\n";
                echo "</tr>\n";
            }
        }
        
        echo "</table>";
        exit;
    }
}

// Set template variables
$page_title = "Reports - Admin Dashboard";
$page_heading = "System Reports & Analytics";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-1"></i>Export Reports
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?export=pdf&report=<?php echo $report_type; ?>"><i class="fas fa-file-pdf me-2"></i>PDF Report</a></li>
                        <li><a class="dropdown-item" href="?export=excel&report=<?php echo $report_type; ?>"><i class="fas fa-file-excel me-2"></i>Excel Report</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Report Navigation -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?report=overview" class="btn btn-<?php echo $report_type === 'overview' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-chart-pie me-1"></i>Overview
                    </a>
                    <a href="?report=vaccinations" class="btn btn-<?php echo $report_type === 'vaccinations' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-syringe me-1"></i>Vaccinations
                    </a>
                    <a href="?report=appointments" class="btn btn-<?php echo $report_type === 'appointments' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-calendar-check me-1"></i>Appointments
                    </a>
                    <a href="?report=hospitals" class="btn btn-<?php echo $report_type === 'hospitals' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-hospital me-1"></i>Hospitals
                    </a>
                </div>
            </div>
        </div>

        <?php if ($report_type === 'overview'): ?>
        <!-- Overview Report -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
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
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Registered Parents</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_parents']; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Active Hospitals</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_hospitals']; ?></h2>
                            </div>
                            <i class="fas fa-hospital fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Vaccinations</h6>
                                <h2 class="fw-bold"><?php echo $stats['total_vaccinations']; ?></h2>
                            </div>
                            <i class="fas fa-syringe fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Performance Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h4 class="text-success"><?php echo round(($stats['completed_vaccinations'] / max(1, $stats['total_vaccinations'])) * 100); ?>%</h4>
                                    <small class="text-muted">Vaccination Completion Rate</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h4 class="text-primary"><?php echo round(($stats['completed_appointments'] / max(1, $stats['total_appointments'])) * 100); ?>%</h4>
                                    <small class="text-muted">Appointment Success Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Pending Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h4 class="text-warning"><?php echo $stats['pending_vaccinations']; ?></h4>
                                    <small class="text-muted">Pending Vaccinations</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h4 class="text-danger"><?php echo $stats['total_appointments'] - $stats['completed_appointments']; ?></h4>
                                    <small class="text-muted">Pending Appointments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'vaccinations'): ?>
        <!-- Vaccinations Report -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-syringe me-2"></i>Vaccine Usage Report</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vaccine Name</th>
                                <th>Total Scheduled</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccine_usage as $vaccine): 
                                $completion_rate = $vaccine['total_scheduled'] > 0 ? round(($vaccine['completed'] / $vaccine['total_scheduled']) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vaccine['name']); ?></td>
                                    <td><?php echo $vaccine['total_scheduled']; ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $vaccine['completed']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $vaccine['pending']; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $completion_rate; ?>%"
                                                 aria-valuenow="<?php echo $completion_rate; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo $completion_rate; ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'hospitals'): ?>
        <!-- Hospitals Report -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Hospital Performance Report</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Hospital Name</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Rejected</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospital_performance as $hospital): 
                                $success_rate = $hospital['total_appointments'] > 0 ? round(($hospital['completed'] / $hospital['total_appointments']) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hospital['name']); ?></td>
                                    <td><?php echo $hospital['total_appointments']; ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $hospital['completed']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $hospital['pending']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $hospital['rejected']; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $success_rate; ?>%"
                                                 aria-valuenow="<?php echo $success_rate; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo $success_rate; ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
