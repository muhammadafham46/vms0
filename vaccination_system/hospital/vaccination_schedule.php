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

// Get all vaccines (including those with inventory)
$query = "SELECT DISTINCT v.* FROM vaccines v
          LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id AND hv.hospital_id = :hospital_id
          WHERE hv.hospital_id = :hospital_id
          ORDER BY v.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define days of the week array
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Get existing schedules
$query = "SELECT s.*, v.name as vaccine_name 
          FROM hospital_vaccination_schedule s
          JOIN vaccines v ON s.vaccine_id = v.id
          WHERE s.hospital_id = :hospital_id
          ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
          s.start_time";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        try {
            $query = "INSERT INTO hospital_vaccination_schedule 
                    (hospital_id, vaccine_id, day_of_week, start_time, end_time, max_slots)
                    VALUES (:hospital_id, :vaccine_id, :day_of_week, :start_time, :end_time, :max_slots)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            $stmt->bindParam(':vaccine_id', $_POST['vaccine_id']);
            $stmt->bindParam(':day_of_week', $_POST['day_of_week']);
            $stmt->bindParam(':start_time', $_POST['start_time']);
            $stmt->bindParam(':end_time', $_POST['end_time']);
            $stmt->bindParam(':max_slots', $_POST['max_slots']);
            
            if ($stmt->execute()) {
                $success = "Schedule added successfully!";
            } else {
                $error = "Failed to add schedule.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_schedule'])) {
        try {
            $query = "DELETE FROM hospital_vaccination_schedule 
                    WHERE id = :id AND hospital_id = :hospital_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['schedule_id']);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            
            if ($stmt->execute()) {
                $success = "Schedule deleted successfully!";
            } else {
                $error = "Failed to delete schedule.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_schedule'])) {
        try {
            $query = "UPDATE hospital_vaccination_schedule 
                    SET vaccine_id = :vaccine_id,
                        day_of_week = :day_of_week,
                        start_time = :start_time,
                        end_time = :end_time,
                        max_slots = :max_slots,
                        is_active = :is_active
                    WHERE id = :id AND hospital_id = :hospital_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['schedule_id']);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            $stmt->bindParam(':vaccine_id', $_POST['vaccine_id']);
            $stmt->bindParam(':day_of_week', $_POST['day_of_week']);
            $stmt->bindParam(':start_time', $_POST['start_time']);
            $stmt->bindParam(':end_time', $_POST['end_time']);
            $stmt->bindParam(':max_slots', $_POST['max_slots']);
            $stmt->bindParam(':is_active', isset($_POST['is_active']), PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                $success = "Schedule updated successfully!";
            } else {
                $error = "Failed to update schedule.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // Refresh schedules after any change
    $query = "SELECT s.*, v.name as vaccine_name 
              FROM hospital_vaccination_schedule s
              JOIN vaccines v ON s.vaccine_id = v.id
              WHERE s.hospital_id = :hospital_id
              ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
              s.start_time";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hospital_id', $hospital['id']);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Schedule - Hospital Panel</title>
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
        .schedule-card {
            transition: transform 0.2s;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .schedule-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
            <a class="nav-link" href="working_hours.php">
                <i class="fas fa-clock me-2"></i>Working Hours
            </a>
            <a class="nav-link active" href="vaccination_schedule.php">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Schedule
            </a>
            <a class="nav-link" href="staff_management.php">
                <i class="fas fa-users me-2"></i>Staff Management
            </a>
            <a class="nav-link" href="notification_settings.php">
                <i class="fas fa-bell me-2"></i>Notifications
            </a>
            <a class="nav-link" href="backup_records.php">
                <i class="fas fa-database me-2"></i>Backup Records
            </a>
            <a class="nav-link" href="reports.php">
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
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Vaccination Schedule</h2>
                    <p class="text-muted">Manage your vaccination schedules and time slots</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class="fas fa-plus me-2"></i>Add Schedule
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Schedule Grid -->
            <div class="row g-4">
                <?php foreach ($schedules as $schedule): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card schedule-card h-100">
                            <div class="card-body">
                                <span class="status-badge badge <?php echo $schedule['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($schedule['vaccine_name']); ?></h5>
                                <p class="mb-2">
                                    <i class="fas fa-calendar-day me-2 text-primary"></i>
                                    <?php echo $schedule['day_of_week']; ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-clock me-2 text-info"></i>
                                    <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                </p>
                                <p class="mb-3">
                                    <i class="fas fa-users me-2 text-success"></i>
                                    Max Slots: <?php echo $schedule['max_slots']; ?>
                                </p>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editScheduleModal<?php echo $schedule['id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" name="delete_schedule" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Schedule Modal -->
                        <div class="modal fade" id="editScheduleModal<?php echo $schedule['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Schedule</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Vaccine</label>
                                                <select name="vaccine_id" class="form-select" required>
                                                    <?php foreach ($vaccines as $vaccine): ?>
                                                        <option value="<?php echo $vaccine['id']; ?>" 
                                                                <?php echo $vaccine['id'] == $schedule['vaccine_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($vaccine['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Day of Week</label>
                                                <select name="day_of_week" class="form-select" required>
                                                    <?php 
                                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                    foreach ($days as $day): 
                                                    ?>
                                                        <option value="<?php echo $day; ?>" 
                                                                <?php echo $day == $schedule['day_of_week'] ? 'selected' : ''; ?>>
                                                            <?php echo $day; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" name="start_time" class="form-control" 
                                                       value="<?php echo $schedule['start_time']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">End Time</label>
                                                <input type="time" name="end_time" class="form-control" 
                                                       value="<?php echo $schedule['end_time']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Slots</label>
                                                <input type="number" name="max_slots" class="form-control" 
                                                       value="<?php echo $schedule['max_slots']; ?>" min="1" required>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="is_active<?php echo $schedule['id']; ?>" 
                                                       name="is_active" <?php echo $schedule['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active<?php echo $schedule['id']; ?>">Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_schedule" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Add Schedule Modal -->
            <div class="modal fade" id="addScheduleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Schedule</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Vaccine</label>
                                    <select name="vaccine_id" class="form-select" required>
                                        <option value="">Select Vaccine</option>
                                        <?php 
                                        // Get all available vaccines for the hospital
                                        $vaccine_query = "SELECT DISTINCT v.* FROM vaccines v
                                                      LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id 
                                                      WHERE hv.hospital_id = :hospital_id
                                                      ORDER BY v.name";
                                        $stmt = $db->prepare($vaccine_query);
                                        $stmt->bindParam(':hospital_id', $hospital['id']);
                                        $stmt->execute();
                                        $available_vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($available_vaccines as $vaccine): 
                                        ?>
                                            <option value="<?php echo $vaccine['id']; ?>">
                                                <?php echo htmlspecialchars($vaccine['name']); ?>
                                                <?php
                                                // Get vaccine stock
                                                $stock_query = "SELECT quantity FROM hospital_vaccines 
                                                              WHERE hospital_id = :hospital_id AND vaccine_id = :vaccine_id";
                                                $stmt = $db->prepare($stock_query);
                                                $stmt->bindParam(':hospital_id', $hospital['id']);
                                                $stmt->bindParam(':vaccine_id', $vaccine['id']);
                                                $stmt->execute();
                                                $stock = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo " (Stock: " . ($stock['quantity'] ?? 0) . ")";
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day of Week</label>
                                    <select name="day_of_week" class="form-select" required>
                                        <option value="">Select Day</option>
                                        <?php 
                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        foreach ($days as $day): 
                                        ?>
                                            <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                    <small class="text-muted">Hospital working hours should be considered</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                    <small class="text-muted">Hospital working hours should be considered</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Maximum Slots</label>
                                    <input type="number" name="max_slots" class="form-control" value="20" min="1" required>
                                    <small class="text-muted">Consider vaccine stock and time slot duration</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_schedule" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
