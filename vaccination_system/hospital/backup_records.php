<?php
require_once '../config/session.php';
require_once '../config/database.php';
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

// Create backup directory if it doesn't exist
$backupDir = '../backups/' . $hospital['id'];
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        $tables = [
            'appointments' => "WHERE hospital_id = " . $hospital['id'],
            'hospital_vaccines' => "WHERE hospital_id = " . $hospital['id'],
            'hospital_working_hours' => "WHERE hospital_id = " . $hospital['id'],
            'hospital_vaccination_schedule' => "WHERE hospital_id = " . $hospital['id'],
            'hospital_staff' => "WHERE hospital_id = " . $hospital['id'],
            'hospital_notification_settings' => "WHERE hospital_id = " . $hospital['id']
        ];

        $timestamp = date('Y-m-d_H-i-s');
        $filename = $backupDir . "/backup_" . $timestamp . ".sql";
        $backup = "";

        foreach ($tables as $table => $condition) {
            // Get create table statement
            $stmt = $db->query("SHOW CREATE TABLE " . $table);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $backup .= "\n\n" . $row['Create Table'] . ";\n\n";

            // Get table data
            $stmt = $db->query("SELECT * FROM " . $table . " " . $condition);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fields = implode("', '", array_map('addslashes', $row));
                $backup .= "INSERT INTO " . $table . " VALUES ('" . $fields . "');\n";
            }
        }

        if (file_put_contents($filename, $backup)) {
            $success = "Backup created successfully!";
        } else {
            $error = "Failed to create backup file.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get list of existing backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $backups[] = [
                'name' => $file,
                'size' => round(filesize($backupDir . '/' . $file) / 1024, 2), // Size in KB
                'date' => date("Y-m-d H:i:s", filemtime($backupDir . '/' . $file))
            ];
        }
    }
}

// Sort backups by date (newest first)
usort($backups, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $filename = $_POST['filename'];
    $filepath = $backupDir . '/' . $filename;
    
    if (file_exists($filepath) && unlink($filepath)) {
        $success = "Backup deleted successfully!";
        // Refresh backups list
        $backups = [];
        $files = scandir($backupDir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $backups[] = [
                    'name' => $file,
                    'size' => round(filesize($backupDir . '/' . $file) / 1024, 2),
                    'date' => date("Y-m-d H:i:s", filemtime($backupDir . '/' . $file))
                ];
            }
        }
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    } else {
        $error = "Failed to delete backup file.";
    }
}

// Handle backup restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    try {
        $filename = $_POST['filename'];
        $filepath = $backupDir . '/' . $filename;
        
        if (file_exists($filepath)) {
            $sql = file_get_contents($filepath);
            $db->exec($sql);
            $success = "Backup restored successfully!";
        } else {
            $error = "Backup file not found.";
        }
    } catch (Exception $e) {
        $error = "Error restoring backup: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Records - Hospital Panel</title>
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
        .backup-card {
            transition: transform 0.2s;
        }
        .backup-card:hover {
            transform: translateY(-2px);
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
            <a class="nav-link" href="vaccination_schedule.php">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Schedule
            </a>
            <a class="nav-link" href="staff_management.php">
                <i class="fas fa-users me-2"></i>Staff Management
            </a>
            <a class="nav-link" href="notification_settings.php">
                <i class="fas fa-bell me-2"></i>Notifications
            </a>
            <a class="nav-link active" href="backup_records.php">
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
                    <h2 class="fw-bold">Backup Records</h2>
                    <p class="text-muted">Manage your data backups</p>
                </div>
                <form method="POST" class="d-inline">
                    <button type="submit" name="create_backup" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create New Backup
                    </button>
                </form>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Backup List -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Backup Name</th>
                                    <th>Size</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-database me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($backup['name']); ?>
                                        </td>
                                        <td><?php echo $backup['size']; ?> KB</td>
                                        <td><?php echo $backup['date']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <form method="POST" class="d-inline me-2">
                                                    <input type="hidden" name="filename" value="<?php echo $backup['name']; ?>">
                                                    <button type="submit" name="restore_backup" class="btn btn-sm btn-outline-success"
                                                            onclick="return confirm('Are you sure you want to restore this backup? This will overwrite current data.')">
                                                        <i class="fas fa-undo me-1"></i>Restore
                                                    </button>
                                                </form>
                                                <a href="<?php echo '../backups/' . $hospital['id'] . '/' . $backup['name']; ?>" 
                                                   download class="btn btn-sm btn-outline-primary me-2">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="filename" value="<?php echo $backup['name']; ?>">
                                                    <button type="submit" name="delete_backup" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this backup?')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($backups)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-database fa-2x mb-2 text-muted"></i>
                                            <p class="mb-0">No backups found. Create your first backup!</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
