<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Set template variables
$page_title = "Vaccines Management - Admin Dashboard";
$page_heading = "Vaccines Management";

// Handle vaccine operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_vaccine'])) {
        // Add new vaccine
        $name = $_POST['name'];
        $description = $_POST['description'];
        $recommended_age = $_POST['recommended_age_months'];
        $dosage = $_POST['dosage'];
        $manufacturer = $_POST['manufacturer'];
        $status = $_POST['status'];

        $query = "INSERT INTO vaccines (name, description, recommended_age_months, dosage, manufacturer, status) 
                  VALUES (:name, :description, :recommended_age_months, :dosage, :manufacturer, :status)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':recommended_age_months', $recommended_age);
        $stmt->bindParam(':dosage', $dosage);
        $stmt->bindParam(':manufacturer', $manufacturer);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vaccine added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add vaccine. Please try again.";
        }
    } elseif (isset($_POST['update_vaccine'])) {
        // Update vaccine
        $id = $_POST['vaccine_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $recommended_age = $_POST['recommended_age_months'];
        $dosage = $_POST['dosage'];
        $manufacturer = $_POST['manufacturer'];
        $status = $_POST['status'];

        $query = "UPDATE vaccines SET name = :name, description = :description, 
                  recommended_age_months = :recommended_age_months, dosage = :dosage, 
                  manufacturer = :manufacturer, status = :status 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':recommended_age_months', $recommended_age);
        $stmt->bindParam(':dosage', $dosage);
        $stmt->bindParam(':manufacturer', $manufacturer);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vaccine updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update vaccine. Please try again.";
        }
    } elseif (isset($_POST['delete_vaccine'])) {
        // Delete vaccine
        $id = $_POST['vaccine_id'];

        $query = "DELETE FROM vaccines WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vaccine deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete vaccine. It may be in use by existing schedules.";
        }
    }
    
    header("Location: vaccines.php");
    exit();
}

// Get all vaccines
$query = "SELECT * FROM vaccines ORDER BY recommended_age_months, name";
$stmt = $db->prepare($query);
$stmt->execute();
$vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vaccine for editing if ID is provided
$edit_vaccine = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM vaccines WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_vaccine = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get vaccine usage statistics
$vaccine_usage = [];
$query = "SELECT v.id, v.name, 
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

// Create usage map for easy access
$usage_map = [];
foreach ($vaccine_usage as $usage) {
    $usage_map[$usage['id']] = $usage;
}

// Set content for the template
ob_start();
?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Vaccines Grid -->
        <?php if (count($vaccines) > 0): ?>
            <div class="row g-4">
                <?php foreach ($vaccines as $vaccine): 
                    $usage = $usage_map[$vaccine['id']] ?? ['total_scheduled' => 0, 'completed' => 0, 'pending' => 0];
                    $completion_rate = $usage['total_scheduled'] > 0 ? round(($usage['completed'] / $usage['total_scheduled']) * 100) : 0;
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card vaccine-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo htmlspecialchars($vaccine['name']); ?></h5>
                                    <span class="badge bg-<?php echo $vaccine['status'] === 'available' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($vaccine['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars(substr($vaccine['description'], 0, 100)); ?>...
                                </p>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Recommended Age</small>
                                        <br>
                                        <strong><?php echo $vaccine['recommended_age_months']; ?> months</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Dosage</small>
                                        <br>
                                        <strong><?php echo htmlspecialchars($vaccine['dosage']); ?></strong>
                                    </div>
                                </div>
                                
                                <?php if ($vaccine['manufacturer']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Manufacturer</small>
                                        <br>
                                        <strong><?php echo htmlspecialchars($vaccine['manufacturer']); ?></strong>
                                    </div>
                                <?php endif; ?>

                                <!-- Usage Statistics -->
                                <div class="mb-3">
                                    <small class="text-muted">Vaccination Progress</small>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $completion_rate; ?>%"
                                             aria-valuenow="<?php echo $completion_rate; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $usage['completed']; ?> completed of <?php echo $usage['total_scheduled']; ?> scheduled
                                    </small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                            onclick="editVaccine(<?php echo $vaccine['id']; ?>)">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal"
                                            data-vaccine-id="<?php echo $vaccine['id']; ?>"
                                            data-vaccine-name="<?php echo htmlspecialchars($vaccine['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-syringe fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No vaccines found</h5>
                <p class="text-muted">Add your first vaccine to get started with vaccination scheduling.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccineModal">
                    <i class="fas fa-plus me-1"></i>Add First Vaccine
                </button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mt-5">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-syringe fa-2x text-primary mb-2"></i>
                        <h5>Total Vaccines</h5>
                        <h3 class="text-primary"><?php echo count($vaccines); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5>Available</h5>
                        <h3 class="text-success">
                            <?php echo count(array_filter($vaccines, fn($v) => $v['status'] === 'available')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ban fa-2x text-secondary mb-2"></i>
                        <h5>Unavailable</h5>
                        <h3 class="text-secondary">
                            <?php echo count(array_filter($vaccines, fn($v) => $v['status'] === 'unavailable')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h5>Total Scheduled</h5>
                        <h3 class="text-info">
                            <?php echo array_sum(array_column($vaccine_usage, 'total_scheduled')); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Vaccine Modal -->
        <div class="modal fade" id="addVaccineModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Vaccine</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Vaccine Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="Vaccine description and purpose..."></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Recommended Age (months) *</label>
                                    <input type="number" class="form-control" name="recommended_age_months" min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Dosage</label>
                                    <input type="text" class="form-control" name="dosage" placeholder="e.g., 0.5ml, 2 drops">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer" placeholder="Manufacturer company name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_vaccine" class="btn btn-primary">Add Vaccine</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Vaccine Modal -->
        <?php if ($edit_vaccine): ?>
        <div class="modal fade show" id="editVaccineModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Vaccine</h5>
                        <a href="vaccines.php" class="btn-close"></a>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="vaccine_id" value="<?php echo $edit_vaccine['id']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Vaccine Name *</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_vaccine['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit_vaccine['description']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Recommended Age (months) *</label>
                                    <input type="number" class="form-control" name="recommended_age_months" value="<?php echo $edit_vaccine['recommended_age_months']; ?>" min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Dosage</label>
                                    <input type="text" class="form-control" name="dosage" value="<?php echo htmlspecialchars($edit_vaccine['dosage']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer" value="<?php echo htmlspecialchars($edit_vaccine['manufacturer']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="available" <?php echo $edit_vaccine['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="unavailable" <?php echo $edit_vaccine['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="vaccines.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_vaccine" class="btn btn-warning">Update Vaccine</button>
                        </div>
                        </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="vaccine_id" id="delete_vaccine_id">
                        <div class="modal-body">
                            <p>Are you sure you want to delete the vaccine "<strong id="delete_vaccine_name"></strong>"?</p>
                            <p class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Warning: This action cannot be undone. If this vaccine is used in any vaccination schedules, deletion may fail.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_vaccine" class="btn btn-danger">Delete Vaccine</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function editVaccine(id) {
                window.location.href = 'vaccines.php?edit=' + id;
            }

            // Show edit modal if editing
            <?php if ($edit_vaccine): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    const modal = new bootstrap.Modal(document.getElementById('editVaccineModal'));
                    modal.show();
                });
            <?php endif; ?>

            // Delete modal setup
            const deleteModal = document.getElementById('deleteModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const vaccineId = button.getAttribute('data-vaccine-id');
                const vaccineName = button.getAttribute('data-vaccine-name');

                document.getElementById('delete_vaccine_id').value = vaccineId;
                document.getElementById('delete_vaccine_name').textContent = vaccineName;
            });
        </script>
<?php
$content = ob_get_clean();

// Add custom scripts
$custom_scripts = '
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
';

// Include template
include 'includes/template.php';
?>
