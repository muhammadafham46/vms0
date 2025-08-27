<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'plan_features.php';

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

// Initialize plan features checker
$plan_features = new PlanFeatures($hospital['id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="text-center py-4">
        <i class="fas fa-syringe fa-2x mb-2"></i>
        <h5>Vaccination System</h5>
        <small>Hospital Panel</small>
    </div>
    <hr>
    <nav class="nav flex-column">
        <div class="sidebar-section mb-2">           
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
                <i class="fas fa-calendar-check me-2"></i>Appointments
            </a>
            <a class="nav-link <?php echo $current_page == 'vaccine_inventory.php' ? 'active' : ''; ?>" href="vaccine_inventory.php">
                <i class="fas fa-syringe me-2"></i>Vaccine Inventory
            </a>
            <?php if ($plan_features->hasFeature('reports_basic')): ?>
            <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Reports" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-chart-bar me-2"></i>Reports <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-section mb-2">
            
            <?php if ($plan_features->hasFeature('working_hours')): ?>
            <a class="nav-link <?php echo $current_page == 'working_hours.php' ? 'active' : ''; ?>" href="working_hours.php">
                <i class="fas fa-clock me-2"></i>Working Hours
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Working Hours" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-clock me-2"></i>Working Hours <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
            
            <?php if ($plan_features->hasFeature('vaccination_schedule')): ?>
            <a class="nav-link <?php echo $current_page == 'vaccination_schedule.php' ? 'active' : ''; ?>" href="vaccination_schedule.php">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Schedule
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Vaccination Schedule" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Schedule <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-section mb-2">
            
            <?php if ($plan_features->hasFeature('staff_management')): ?>
            <a class="nav-link <?php echo $current_page == 'staff_management.php' ? 'active' : ''; ?>" href="staff_management.php">
                <i class="fas fa-users me-2"></i>Staff Management
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Staff Management" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-users me-2"></i>Staff Management <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
            
            <?php if ($plan_features->hasFeature('notification_settings')): ?>
            <a class="nav-link <?php echo $current_page == 'notification_settings.php' ? 'active' : ''; ?>" href="notification_settings.php">
                <i class="fas fa-bell me-2"></i>Notification Settings
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Notification Settings" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-bell me-2"></i>Notification Settings <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
            
            <?php if ($plan_features->hasFeature('backup_records')): ?>
            <a class="nav-link <?php echo $current_page == 'backup_records.php' ? 'active' : ''; ?>" href="backup_records.php">
                <i class="fas fa-database me-2"></i>Backup Records
            </a>
            <?php else: ?>
            <a class="nav-link text-muted" href="#" data-bs-toggle="tooltip" title="Upgrade to access Backup Records" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-database me-2"></i>Backup Records <small class="badge bg-warning ms-1">Upgrade</small>
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-section">
            
            <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                <i class="fas fa-user me-2"></i>Profile
            </a>
            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-cog me-2"></i>Hospital Settings
            </a>
            <a class="nav-link <?php echo $current_page == 'plan_tracking.php' ? 'active' : ''; ?>" href="plan_tracking.php">
                <i class="fas fa-chart-line me-2"></i>Plan Tracking
            </a>
            <a class="nav-link <?php echo $current_page == '../logout.php' ? 'active' : ''; ?>" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </nav>
</div>
