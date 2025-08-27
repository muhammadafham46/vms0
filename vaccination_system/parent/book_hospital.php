<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Get vaccination details if ID is provided
$vaccination_id = isset($_GET['vaccination_id']) ? $_GET['vaccination_id'] : null;
$vaccination = null;
$child = null;

if ($vaccination_id) {
    $query = "SELECT vs.*, v.name as vaccine_name, c.name as child_name, c.id as child_id
              FROM vaccination_schedule vs
              JOIN vaccines v ON vs.vaccine_id = v.id
              JOIN children c ON vs.child_id = c.id
              WHERE vs.id = :vaccination_id AND c.parent_id = :parent_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vaccination_id', $vaccination_id);
    $stmt->bindParam(':parent_id', $_SESSION['user_id']);
    $stmt->execute();
    $vaccination = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all hospitals
$query = "SELECT * FROM hospitals WHERE status = 'active' ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all children of this parent for general booking
$query = "SELECT * FROM children WHERE parent_id = :parent_id ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT v.*, hv.hospital_price
          FROM vaccines v 
          LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id 
          WHERE v.status = 'available' 
          ORDER BY v.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debugging output
if (empty($vaccines)) {
    error_log("No available vaccines found in the database.");
} else {
    error_log("Available vaccines retrieved: " . json_encode($vaccines));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['book_appointment'])) {
        $child_id = $_POST['child_id'];
        $vaccine_id = $_POST['vaccine_id'];
        $hospital_id = $_POST['hospital_id'];
        $scheduled_date = $_POST['scheduled_date'];
        $notes = $_POST['notes'];
        
        // Check if this vaccination already exists
        $check_query = "SELECT id FROM vaccination_schedule 
                       WHERE child_id = :child_id AND vaccine_id = :vaccine_id AND status = 'pending'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':child_id', $child_id);
        $check_stmt->bindParam(':vaccine_id', $vaccine_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "This child already has a pending appointment for this vaccine.";
        } else {
            // Insert new vaccination schedule
            $query = "INSERT INTO vaccination_schedule 
                     (child_id, vaccine_id, hospital_id, scheduled_date, notes, status) 
                     VALUES (:child_id, :vaccine_id, :hospital_id, :scheduled_date, :notes, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':child_id', $child_id);
            $stmt->bindParam(':vaccine_id', $vaccine_id);
            $stmt->bindParam(':hospital_id', $hospital_id);
            $stmt->bindParam(':scheduled_date', $scheduled_date);
            $stmt->bindParam(':notes', $notes);
            
            if ($stmt->execute()) {
                // Also create an appointment record
                $appointment_query = "INSERT INTO appointments 
                                    (parent_id, child_id, hospital_id, vaccine_id, appointment_date, status, notes) 
                                    VALUES (:parent_id, :child_id, :hospital_id, :vaccine_id, :scheduled_date, 'pending', :notes)";
                
                $appointment_stmt = $db->prepare($appointment_query);
                $appointment_stmt->bindParam(':parent_id', $_SESSION['user_id']);
                $appointment_stmt->bindParam(':child_id', $child_id);
                $appointment_stmt->bindParam(':hospital_id', $hospital_id);
                $appointment_stmt->bindParam(':vaccine_id', $vaccine_id);
                $appointment_stmt->bindParam(':scheduled_date', $scheduled_date);
                $appointment_stmt->bindParam(':notes', $notes);
                $appointment_stmt->execute();
                
                $success = "Appointment booked successfully!";
                // Clear form if successful
                unset($_POST);
            } else {
                $error = "Failed to book appointment. Please try again.";
            }
        }
    } elseif (isset($_POST['update_vaccination'])) {
        $vaccination_id = $_POST['vaccination_id'];
        $hospital_id = $_POST['hospital_id'];
        $scheduled_date = $_POST['scheduled_date'];
        
        $query = "UPDATE vaccination_schedule 
                 SET hospital_id = :hospital_id, scheduled_date = :scheduled_date 
                 WHERE id = :vaccination_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital_id);
        $stmt->bindParam(':scheduled_date', $scheduled_date);
        $stmt->bindParam(':vaccination_id', $vaccination_id);
        
        if ($stmt->execute()) {
            $success = "Vaccination updated successfully!";
            header("Location: vaccination_dates.php");
            exit();
        } else {
            $error = "Failed to update vaccination. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Hospital - Parent Panel</title>
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
        .hospital-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .hospital-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .hospital-card.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
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
            <a class="nav-link active" href="book_hospital.php">
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
            <h2 class="fw-bold">
                <?php echo $vaccination ? 'Update Vaccination' : 'Book Hospital Appointment'; ?>
            </h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($vaccination): ?>
            <!-- Update existing vaccination -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-syringe me-2"></i>Update Vaccination</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Vaccination Details:</strong><br>
                        Child: <?php echo $vaccination['child_name']; ?><br>
                        Vaccine: <?php echo $vaccination['vaccine_name']; ?><br>
                        Current Date: <?php echo date('M d, Y', strtotime($vaccination['scheduled_date'])); ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="vaccination_id" value="<?php echo $vaccination_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Hospital *</label>
                                <select class="form-select" name="hospital_id" required>
                                    <option value="">Choose Hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>" 
                                            <?php echo $hospital['id'] == $vaccination['hospital_id'] ? 'selected' : ''; ?>>
                                            <?php echo $hospital['name']; ?> - <?php echo $hospital['address']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scheduled Date *</label>
                                <input type="date" class="form-control" name="scheduled_date" 
                                       value="<?php echo $vaccination['scheduled_date']; ?>" required
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_vaccination" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Vaccination
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Book new appointment -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>New Appointment Booking</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Child *</label>
                                <select class="form-select" name="child_id" required>
                                    <option value="">Choose Child</option>
                                    <?php foreach ($children as $child): ?>
                                        <option value="<?php echo $child['id']; ?>" 
                                            <?php echo isset($_POST['child_id']) && $_POST['child_id'] == $child['id'] ? 'selected' : ''; ?>>
                                            <?php echo $child['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Vaccine *</label>
                                <select class="form-select" name="vaccine_id" id="vaccineSelect" required>
                                    <option value="">Choose Vaccine</option>
                                    <?php foreach ($vaccines as $vaccine): ?>
                                        <option value="<?php echo $vaccine['id']; ?>" 
                                            data-price="<?php echo $vaccine['hospital_price'] ? $vaccine['hospital_price'] : ''; ?>" 
                                            <?php echo isset($_POST['vaccine_id']) && $_POST['vaccine_id'] == $vaccine['id'] ? 'selected' : ''; ?>>
                                            <?php echo $vaccine['name']; ?> (Recommended: <?php echo $vaccine['recommended_age_months']; ?> months)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="vaccinePriceDisplay" class="mt-2 text-success fw-bold" style="display: none;">
                                    Price: PKR <span id="priceValue">0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Hospital *</label>
                                <select class="form-select" name="hospital_id" required>
                                    <option value="">Choose Hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>" 
                                            <?php echo isset($_POST['hospital_id']) && $_POST['hospital_id'] == $hospital['id'] ? 'selected' : ''; ?>>
                                            <?php echo $hospital['name']; ?> - <?php echo $hospital['address']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scheduled Date *</label>
                                <input type="date" class="form-control" name="scheduled_date" 
                                       value="<?php echo isset($_POST['scheduled_date']) ? $_POST['scheduled_date'] : ''; ?>" 
                                       required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Any special instructions or notes..."><?php echo isset($_POST['notes']) ? $_POST['notes'] : ''; ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="book_appointment" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-1"></i>Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Available Hospitals -->
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Available Hospitals</h5>
            </div>
            <div class="card-body">
                <?php if (count($hospitals) > 0): ?>
                    <div class="row">
                        <?php foreach ($hospitals as $hospital): ?>
                            <div class="col-md-6 mb-3">
                                <div class="hospital-card">
                                    <h6 class="mb-2"><?php echo $hospital['name']; ?></h6>
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo $hospital['address']; ?>
                                    </p>
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo $hospital['phone']; ?>
                                    </p>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Hours: <?php echo isset($hospital['working_hours']) ? $hospital['working_hours'] : 'Not specified'; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No hospitals available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vaccineSelect = document.getElementById('vaccineSelect');
            const priceDisplay = document.getElementById('vaccinePriceDisplay');
            const priceValue = document.getElementById('priceValue');

            vaccineSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                
                if (price && price !== '0') {
                    priceValue.textContent = parseFloat(price).toFixed(2);
                    priceDisplay.style.display = 'block';
                } else {
                    priceDisplay.style.display = 'none';
                }
            });

            // Trigger change event on page load if a vaccine is already selected
            if (vaccineSelect.value) {
                vaccineSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
