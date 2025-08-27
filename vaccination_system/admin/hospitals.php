<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_hospital'])) {
        // Add new hospital
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $contact_person = $_POST['contact_person'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        $query = "INSERT INTO hospitals (name, address, phone, email, contact_person, latitude, longitude) 
                  VALUES (:name, :address, :phone, :email, :contact_person, :latitude, :longitude)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);

        if ($stmt->execute()) {
            $success = "Hospital added successfully!";
        } else {
            $error = "Failed to add hospital. Please try again.";
        }
    } elseif (isset($_POST['update_hospital'])) {
        // Update hospital
        $id = $_POST['hospital_id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $contact_person = $_POST['contact_person'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $status = $_POST['status'];

        $query = "UPDATE hospitals SET name = :name, address = :address, phone = :phone, 
                  email = :email, contact_person = :contact_person, latitude = :latitude, 
                  longitude = :longitude, status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $success = "Hospital updated successfully!";
        } else {
            $error = "Failed to update hospital. Please try again.";
        }
    } elseif (isset($_POST['delete_hospital'])) {
        // Delete hospital
        $id = $_POST['hospital_id'];
        
        $query = "DELETE FROM hospitals WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success = "Hospital deleted successfully!";
        } else {
            $error = "Failed to delete hospital. Please try again.";
        }
    }
}

// Get all hospitals
$query = "SELECT * FROM hospitals ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get hospital for editing if ID is provided
$edit_hospital = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM hospitals WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_hospital = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Set template variables
$page_title = "Hospitals Management - Admin Dashboard";
$page_heading = "Hospitals Management";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- <h2 class="fw-bold">Hospitals Management</h2> -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHospitalModal">
                <i class="fas fa-plus me-1"></i>Add New Hospital
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Hospitals List -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Registered Hospitals (<?php echo count($hospitals); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($hospitals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Hospital Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hospitals as $hospital): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($hospital['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($hospital['contact_person']); ?></td>
                                        <td><?php echo $hospital['phone']; ?></td>
                                        <td><?php echo $hospital['email']; ?></td>
                                        <td>
                                            <small><?php echo substr($hospital['address'], 0, 50); ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $hospital['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($hospital['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($hospital['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editHospital(<?php echo $hospital['id']; ?>)"
                                                        title="Edit Hospital">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="hospital_vaccine_inventory.php?hospital_id=<?php echo $hospital['id']; ?>" 
                                                   class="btn btn-sm btn-info"
                                                   title="View Vaccine Inventory">
                                                    <i class="fas fa-syringe"></i>
                                                </a>
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="viewHospital(<?php echo $hospital['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                                    <button type="submit" name="delete_hospital" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this hospital?')"
                                                            title="Delete Hospital">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-hospital fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No hospitals registered yet</h5>
                        <p class="text-muted">Click the button above to add your first hospital.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hospital Map View -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Hospitals Location Map</h5>
            </div>
            <div class="card-body">
                <div class="map-container bg-light">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center text-muted">
                            <i class="fas fa-map fa-3x mb-3"></i>
                            <h6>Interactive Map View</h6>
                            <small>Hospital locations would be displayed here with Google Maps integration</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Hospital Modal -->
        <div class="modal fade" id="addHospitalModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Hospital</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hospital Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person *</label>
                                    <input type="text" class="form-control" name="contact_person" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <textarea class="form-control" name="address" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" name="latitude" placeholder="28.6139">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" name="longitude" placeholder="77.2090">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_hospital" class="btn btn-primary">Add Hospital</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Hospital Modal -->
        <?php if ($edit_hospital): ?>
        <div class="modal fade show" id="editHospitalModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Hospital</h5>
                        <a href="hospitals.php" class="btn-close"></a>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="hospital_id" value="<?php echo $edit_hospital['id']; ?>">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hospital Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $edit_hospital['name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person *</label>
                                    <input type="text" class="form-control" name="contact_person" value="<?php echo $edit_hospital['contact_person']; ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo $edit_hospital['phone']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $edit_hospital['email']; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <textarea class="form-control" name="address" rows="3" required><?php echo $edit_hospital['address']; ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" name="latitude" value="<?php echo $edit_hospital['latitude']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" name="longitude" value="<?php echo $edit_hospital['longitude']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo $edit_hospital['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $edit_hospital['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="hospitals.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_hospital" class="btn btn-warning">Update Hospital</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hospital Details Modal -->
        <div class="modal fade" id="hospitalDetailsModal" tabindex="-1" aria-labelledby="hospitalDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="hospitalDetailsModalLabel">Hospital Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="hospitalDetails"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

<?php
// Get the content and include the template
$content = ob_get_clean();

// Add custom scripts
$custom_scripts = '
<script>
function editHospital(id) {
    window.location.href = \'hospitals.php?edit=\' + id;
}

function viewHospital(id) {
    fetch(`get_hospital_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Populate the modal with hospital details
            const details = `
                <strong>Hospital Name:</strong> ${data.name}<br>
                <strong>Username:</strong> ${data.username}<br>
                <strong>Password:</strong> ${data.password}<br>
                <strong>Address:</strong> ${data.address}<br>
                <strong>Phone:</strong> ${data.phone}<br>
                <strong>Email:</strong> ${data.email}
            `;
            document.getElementById(\'hospitalDetails\').innerHTML = details;
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById(\'hospitalDetailsModal\'));
            modal.show();
        })
        .catch(error => {
            console.error(\'Error fetching hospital details:\', error);
        });
}

// Show edit modal if editing
';
if ($edit_hospital) {
    $custom_scripts .= '
document.addEventListener(\'DOMContentLoaded\', function() {
    const modal = new bootstrap.Modal(document.getElementById(\'editHospitalModal\'));
    modal.show();
});';
}

$custom_scripts .= '
</script>';

include 'includes/template.php';
?>
