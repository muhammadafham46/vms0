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

// Get current working hours
$query = "SELECT * FROM hospital_working_hours WHERE hospital_id = :hospital_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$working_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    try {
        $db->beginTransaction();
        
        // Delete existing hours
        $query = "DELETE FROM hospital_working_hours WHERE hospital_id = :hospital_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->execute();
        
        // Insert new hours
        foreach ($days as $day) {
            if (isset($_POST[$day . '_enabled'])) {
                $query = "INSERT INTO hospital_working_hours (hospital_id, day, open_time, close_time, is_open) 
                         VALUES (:hospital_id, :day, :open_time, :close_time, 1)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':hospital_id', $hospital['id']);
                $stmt->bindParam(':day', $day);
                $stmt->bindParam(':open_time', $_POST[$day . '_open']);
                $stmt->bindParam(':close_time', $_POST[$day . '_close']);
                $stmt->execute();
            } else {
                $query = "INSERT INTO hospital_working_hours (hospital_id, day, is_open) 
                         VALUES (:hospital_id, :day, 0)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':hospital_id', $hospital['id']);
                $stmt->bindParam(':day', $day);
                $stmt->execute();
            }
        }
        
        $db->commit();
        $success = "Working hours updated successfully!";
        
        // Refresh working hours
        $query = "SELECT * FROM hospital_working_hours WHERE hospital_id = :hospital_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->execute();
        $working_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Failed to update working hours: " . $e->getMessage();
    }
}

// Convert working hours to associative array for easier access
$hours_by_day = [];
foreach ($working_hours as $hours) {
    $hours_by_day[$hours['day']] = $hours;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working Hours - Hospital Panel</title>
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
        .time-slot {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .time-slot.closed {
            opacity: 0.7;
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Working Hours</h2>
                <p class="text-muted">Set your hospital's vaccination service hours</p>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?php
                    $days = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday'
                    ];
                    
                    foreach ($days as $day_key => $day_name):
                        $is_open = isset($hours_by_day[$day_key]) ? $hours_by_day[$day_key]['is_open'] : 1;
                        $open_time = isset($hours_by_day[$day_key]) ? $hours_by_day[$day_key]['open_time'] : '09:00';
                        $close_time = isset($hours_by_day[$day_key]) ? $hours_by_day[$day_key]['close_time'] : '17:00';
                    ?>
                        <div class="time-slot <?php echo $is_open ? '' : 'closed'; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="<?php echo $day_key; ?>_enabled" 
                                               id="<?php echo $day_key; ?>_enabled"
                                               <?php echo $is_open ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="<?php echo $day_key; ?>_enabled">
                                            <?php echo $day_name; ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Opening Time</label>
                                            <input type="time" class="form-control" 
                                                   name="<?php echo $day_key; ?>_open"
                                                   value="<?php echo $open_time; ?>"
                                                   <?php echo $is_open ? '' : 'disabled'; ?>>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Closing Time</label>
                                            <input type="time" class="form-control" 
                                                   name="<?php echo $day_key; ?>_close"
                                                   value="<?php echo $close_time; ?>"
                                                   <?php echo $is_open ? '' : 'disabled'; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Working Hours
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable/disable time inputs based on checkbox
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const dayKey = this.id.replace('_enabled', '');
                const timeInputs = document.querySelectorAll(`input[name^="${dayKey}_"]`);
                const timeSlot = this.closest('.time-slot');
                
                timeInputs.forEach(input => {
                    if (input.type === 'time') {
                        input.disabled = !this.checked;
                    }
                });
                
                if (this.checked) {
                    timeSlot.classList.remove('closed');
                } else {
                    timeSlot.classList.add('closed');
                }
            });
        });
    </script>
</body>
</html>
