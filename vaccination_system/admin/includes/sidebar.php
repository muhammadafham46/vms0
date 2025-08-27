<div class="sidebar">
    <div class="text-center py-4">
        <i class="fas fa-syringe fa-2x mb-2"></i>
        <h5>Vaccination System</h5>
        <small>Admin Panel</small>
    </div>
    <hr>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'children.php' ? 'active' : ''; ?>" href="children.php">
            <i class="fas fa-child me-2"></i>All Children
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parents.php' ? 'active' : ''; ?>" href="parents.php">
            <i class="fas fa-users me-2"></i>Parents
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'hospitals.php' ? 'active' : ''; ?>" href="hospitals.php">
            <i class="fas fa-hospital me-2"></i>Hospitals
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vaccines.php' ? 'active' : ''; ?>" href="vaccines.php">
            <i class="fas fa-syringe me-2"></i>Vaccines
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vaccination_dates.php' ? 'active' : ''; ?>" href="vaccination_dates.php">
            <i class="fas fa-calendar-alt me-2"></i>Vaccination Dates
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
            <i class="fas fa-calendar-check me-2"></i>Booking Details
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
            <i class="fas fa-chart-bar me-2"></i>Reports
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'active' : ''; ?>" href="requests.php">
            <i class="fas fa-bell me-2"></i>Parent Requests
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'subscription_plans.php' ? 'active' : ''; ?>" href="subscription_plans.php">
            <i class="fas fa-credit-card me-2"></i>Subscription Plans
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'upgrade_requests.php' ? 'active' : ''; ?>" href="upgrade_requests.php">
            <i class="fas fa-arrow-up me-2"></i>Upgrade Requests
        </a> 
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </nav>
</div>
