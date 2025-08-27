<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_parent'])) {
        // Add new parent
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        $query = "INSERT INTO users (username, password, email, full_name, phone, address, user_type) 
                  VALUES (:username, :password, :email, :full_name, :phone, :address, 'parent')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            $success = "Parent added successfully!";
        } else {
            $error = "Failed to add parent. Please try again.";
        }
    } elseif (isset($_POST['update_parent'])) {
        // Update parent
        $id = $_POST['parent_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $status = $_POST['status'];

        $query = "UPDATE users SET username = :username, email = :email, 
                  full_name = :full_name, phone = :phone, address = :address, 
                  status = :status WHERE id = :id AND user_type = 'parent'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $success = "Parent updated successfully!";
        } else {
            $error = "Failed to update parent. Please try again.";
        }
    } elseif (isset($_POST['delete_parent'])) {
        // Delete parent
        $id = $_POST['parent_id'];
        
        $query = "DELETE FROM users WHERE id = :id AND user_type = 'parent'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success = "Parent deleted successfully!";
        } else {
            $error = "Failed to delete parent. Please try again.";
        }
    }
}

// Get all parents
$query = "SELECT * FROM users WHERE user_type = 'parent' ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent for editing if ID is provided
$edit_parent = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM users WHERE id = :id AND user_type = 'parent'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_parent = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Set template variables
$page_title = "Parents Management - Admin Dashboard";
$page_heading = "Parents Management";

// Start output buffering
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- <h2 class="fw-bold">Parents Management</h2> -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
        <i class="fas fa-plus me-1"></i>Add New Parent
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Parents List -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Registered Parents (<?php echo count($parents); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (count($parents) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parents as $parent): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($parent['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($parent['full_name']); ?></td>
                                <td><?php echo $parent['email']; ?></td>
                                <td><?php echo $parent['phone']; ?></td>
                                <td>
                                    <small><?php echo substr($parent['address'], 0, 50); ?>...</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $parent['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($parent['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($parent['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="editParent(<?php echo $parent['id']; ?>)"
                                                title="Edit Parent">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewParent(<?php echo $parent['id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                            <button type="submit" name="delete_parent" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this parent?')"
                                                    title="Delete Parent">
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
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No parents registered yet</h5>
                <p class="text-muted">Click the button above to add your first parent.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Parent Modal -->
<div class="modal fade" id="addParentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Parent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_parent" class="btn btn-primary">Add Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Parent Modal -->
<?php if ($edit_parent): ?>
<div class="modal fade show" id="editParentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Parent</h5>
                <a href="parents.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="parent_id" value="<?php echo $edit_parent['id']; ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" value="<?php echo $edit_parent['username']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo $edit_parent['full_name']; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $edit_parent['email']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo $edit_parent['phone']; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo $edit_parent['address']; ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $edit_parent['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_parent['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="parents.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="update_parent" class="btn btn-warning">Update Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Parent Details Modal -->
<div class="modal fade" id="parentDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Parent Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="parentDetails"></div>
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
function editParent(id) {
    window.location.href = "parents.php?edit=" + id;
}

function viewParent(id) {
    // Fetch parent details and show in modal
    fetch("get_parent_details.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            // Populate the modal with parent details
            const phone = data.phone || "Not provided";
            const address = data.address || "Not provided";
            const statusClass = data.status === "active" ? "success" : "secondary";
            const registeredOn = new Date(data.created_at).toLocaleDateString();
            const lastLogin = data.last_login ? new Date(data.last_login).toLocaleDateString() : "Never";
            
            const details = "<div class=\\"row\\">" +
                "<div class=\\"col-md-6\\">" +
                "<p><strong>Username:</strong> " + data.username + "</p>" +
                "<p><strong>Email:</strong> " + data.email + "</p>" +
                "<p><strong>Full Name:</strong> " + data.full_name + "</p>" +
                "</div>" +
                "<div class=\\"col-md-6\\">" +
                "<p><strong>Phone:</strong> " + phone + "</p>" +
                "<p><strong>Address:</strong> " + address + "</p>" +
                "<p><strong>Status:</strong> <span class=\\"badge bg-" + statusClass + "\\">" + data.status + "</span></p>" +
                "</div>" +
                "</div>" +
                "<div class=\\"row mt-3\\">" +
                "<div class=\\"col-12\\">" +
                "<p><strong>Registered On:</strong> " + registeredOn + "</p>" +
                "<p><strong>Last Login:</strong> " + lastLogin + "</p>" +
                "</div>" +
                "</div>";
                
            document.getElementById("parentDetails").innerHTML = details;
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById("parentDetailsModal"));
            modal.show();
        })
        .catch(error => {
            console.error("Error fetching parent details:", error);
            alert("Error loading parent details. Please try again.");
        });
}

// Show edit modal if editing
' . ($edit_parent ? 'document.addEventListener("DOMContentLoaded", function() {
    const modal = new bootstrap.Modal(document.getElementById("editParentModal"));
    modal.show();
});' : '') . '
</script>';

include 'includes/template.php';
?>
