<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotParent();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_child'])) {
        // Add new child
        $name = $_POST['name'];
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $parent_id = $_SESSION['user_id'];

        $query = "INSERT INTO children (parent_id, name, date_of_birth, gender, blood_group) 
                  VALUES (:parent_id, :name, :date_of_birth, :gender, :blood_group)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $parent_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':date_of_birth', $date_of_birth);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':blood_group', $blood_group);

        if ($stmt->execute()) {
            $success = "Child added successfully!";
        } else {
            $error = "Failed to add child. Please try again.";
        }
    } elseif (isset($_POST['update_child'])) {
        // Update child
        $child_id = $_POST['child_id'];
        $name = $_POST['name'];
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];

        $query = "UPDATE children SET name = :name, date_of_birth = :date_of_birth, 
                  gender = :gender, blood_group = :blood_group 
                  WHERE id = :child_id AND parent_id = :parent_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':child_id', $child_id);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':date_of_birth', $date_of_birth);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':blood_group', $blood_group);

        if ($stmt->execute()) {
            $success = "Child updated successfully!";
        } else {
            $error = "Failed to update child. Please try again.";
        }
    } elseif (isset($_POST['delete_child'])) {
        // Delete child
        $child_id = $_POST['child_id'];
        
        $query = "DELETE FROM children WHERE id = :child_id AND parent_id = :parent_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':child_id', $child_id);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Child deleted successfully!";
        } else {
            $error = "Failed to delete child. Please try again.";
        }
    }
}

// Get all children for this parent
$query = "SELECT * FROM children WHERE parent_id = :parent_id ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get child for editing if ID is provided
$edit_child = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM children WHERE id = :child_id AND parent_id = :parent_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':child_id', $_GET['edit']);
    $stmt->bindParam(':parent_id', $_SESSION['user_id']);
    $stmt->execute();
    $edit_child = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Children - Parent Panel</title>
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
            <a class="nav-link active" href="children.php">
                <i class="fas fa-child me-2"></i>My Children
            </a>
            <a class="nav-link" href="vaccination_dates.php">
                <i class="fas fa-calendar-alt me-2"></i>Vaccination Dates
            </a>
            <a class="nav-link" href="book_hospital.php">
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
            <h2 class="fw-bold">My Children</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChildModal">
                <i class="fas fa-plus me-1"></i>Add New Child
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Children List -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-child me-2"></i>Registered Children (<?php echo count($children); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($children) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date of Birth</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($child['name']); ?></strong>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($child['date_of_birth'])); ?></td>
                                        <td>
                                            <?php 
                                                $birthDate = new DateTime($child['date_of_birth']);
                                                $today = new DateTime();
                                                $age = $today->diff($birthDate);
                                                echo $age->y . ' years ' . $age->m . ' months';
                                            ?>
                                        </td>
                                        <td><?php echo ucfirst($child['gender']); ?></td>
                                        <td><?php echo $child['blood_group'] ?: 'Not specified'; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editChild(<?php echo $child['id']; ?>)"
                                                        title="Edit Child">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                                    <button type="submit" name="delete_child" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this child?')"
                                                            title="Delete Child">
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
                        <i class="fas fa-child fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No children registered yet</h5>
                        <p class="text-muted">Click the button above to add your first child.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Child Modal -->
    <div class="modal fade" id="addChildModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Child</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Child's Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" name="date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Blood Group</label>
                            <select class="form-select" name="blood_group">
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_child" class="btn btn-primary">Add Child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Child Modal -->
    <?php if ($edit_child): ?>
    <div class="modal fade show" id="editChildModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Child</h5>
                    <a href="children.php" class="btn-close"></a>
                </div>
                <form method="POST">
                    <input type="hidden" name="child_id" value="<?php echo $edit_child['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Child's Name *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $edit_child['name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo $edit_child['date_of_birth']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" name="gender" required>
                                <option value="male" <?php echo $edit_child['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $edit_child['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo $edit_child['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Blood Group</label>
                            <select class="form-select" name="blood_group">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo $edit_child['blood_group'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo $edit_child['blood_group'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo $edit_child['blood_group'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo $edit_child['blood_group'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo $edit_child['blood_group'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo $edit_child['blood_group'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo $edit_child['blood_group'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo $edit_child['blood_group'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="children.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_child" class="btn btn-warning">Update Child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editChild(id) {
            window.location.href = 'children.php?edit=' + id;
        }

        // Show edit modal if editing
        <?php if ($edit_child): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('editChildModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
