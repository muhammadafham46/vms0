<?php
define('SECURE_ACCESS', true);
require_once 'includes/template.php';

$page_title = 'Staff Management';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];

// Get staff members
$query = "SELECT * FROM hospital_staff WHERE hospital_id = :hospital_id ORDER BY role";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle staff member addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $shift = $_POST['shift'];
    
    try {
        $query = "INSERT INTO hospital_staff (hospital_id, full_name, email, phone, role, shift) 
                 VALUES (:hospital_id, :full_name, :email, :phone, :role, :shift)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':shift', $shift);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Staff member added successfully!";
            // Refresh staff list
            $query = "SELECT * FROM hospital_staff WHERE hospital_id = :hospital_id ORDER BY role";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            $stmt->execute();
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Failed to add staff member.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: staff_management.php");
    exit();
}

// Handle staff member deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $staff_id = $_POST['staff_id'];
    
    $query = "DELETE FROM hospital_staff WHERE id = :id AND hospital_id = :hospital_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $staff_id);
    $stmt->bindParam(':hospital_id', $hospital['id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff member removed successfully!";
        // Refresh staff list
        $query = "SELECT * FROM hospital_staff WHERE hospital_id = :hospital_id ORDER BY role";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hospital_id', $hospital['id']);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Failed to remove staff member.";
    }
    
    header("Location: staff_management.php");
    exit();
}

render_hospital_header($page_title);
display_messages();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Staff Management</h2>
        <p class="text-muted">Manage hospital staff members</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="fas fa-plus me-2"></i>Add Staff Member
    </button>
</div>

<!-- Staff Grid -->
<div class="row g-4">
    <?php foreach ($staff as $member): ?>
        <div class="col-md-4">
            <div class="card staff-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title mb-0"><?php echo $member['full_name']; ?></h5>
                        <span class="badge bg-primary"><?php echo ucfirst($member['role']); ?></span>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo $member['email']; ?></p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo $member['phone']; ?></p>
                        <p class="mb-1">
                            <i class="fas fa-clock me-2"></i>
                            Shift: <?php echo ucfirst($member['shift']); ?>
                        </p>
                    </div>
                    <div class="d-flex justify-content-end">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this staff member?');">
                            <input type="hidden" name="staff_id" value="<?php echo $member['id']; ?>">
                            <button type="submit" name="delete_staff" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash me-1"></i>Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="technician">Technician</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift</label>
                        <select name="shift" class="form-select" required>
                            <option value="morning">Morning</option>
                            <option value="evening">Evening</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_staff" class="btn btn-primary">Add Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
render_hospital_footer();
?>
