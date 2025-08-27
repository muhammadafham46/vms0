<?php
define('SECURE_ACCESS', true);  // ye line zaroori hai

// Use the new template system
require_once 'includes/template.php';
$page_title = 'Vaccine Inventory';
$init_result = init_hospital_page();
$db = $init_result['db'];
$hospital = $init_result['hospital'];
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'includes/feature_access.php';
redirectIfNotHospital();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new vaccine
    if (isset($_POST['add_new_vaccine'])) {
        try {
            // First add the vaccine to the vaccines table
            $query = "INSERT INTO vaccines (name, description, recommended_age_months, dosage, manufacturer) 
                     VALUES (:name, :description, :recommended_age_months, :dosage, :manufacturer)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $_POST['name']);
            $stmt->bindParam(':description', $_POST['description']);
            $stmt->bindParam(':recommended_age_months', $_POST['recommended_age_months']);
            $stmt->bindParam(':dosage', $_POST['dosage']);
            $stmt->bindParam(':manufacturer', $_POST['manufacturer']);
            
            if ($stmt->execute()) {
                $vaccine_id = $db->lastInsertId();
                
                // Add it to hospital's inventory with price
                $query = "INSERT INTO hospital_vaccines (hospital_id, vaccine_id, quantity, hospital_price) 
                         VALUES (:hospital_id, :vaccine_id, :quantity, :price)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':hospital_id', $hospital['id']);
                $stmt->bindParam(':vaccine_id', $vaccine_id);
                $stmt->bindParam(':quantity', $_POST['quantity']);
                $stmt->bindParam(':price', $_POST['price']);
                
                if ($stmt->execute()) {
                    // Redirect to prevent form resubmission
                    header("Location: vaccine_inventory.php?success=New vaccine added successfully!");
                    exit();
                } else {
                    $error = "Failed to add vaccine to inventory.";
                }
            } else {
                $error = "Failed to create new vaccine.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Update quantity
    if (isset($_POST['update_quantity'])) {
        try {
            $inventory_id = $_POST['inventory_id'];
            $new_quantity = $_POST['new_quantity'];
            
            $query = "UPDATE hospital_vaccines 
                     SET quantity = :quantity 
                     WHERE id = :id AND hospital_id = :hospital_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':quantity', $new_quantity);
            $stmt->bindParam(':id', $inventory_id);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: vaccine_inventory.php?success=Quantity updated successfully!");
                exit();
            } else {
                $error = "Failed to update quantity.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Remove vaccine
    if (isset($_POST['remove_vaccine'])) {
        try {
            $inventory_id = $_POST['inventory_id'];
            
            $query = "DELETE FROM hospital_vaccines 
                     WHERE id = :id AND hospital_id = :hospital_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $inventory_id);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: vaccine_inventory.php?success=Vaccine removed successfully!");
                exit();
            } else {
                $error = "Failed to remove vaccine.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Update price
    if (isset($_POST['update_price'])) {
        try {
            $inventory_id = $_POST['inventory_id'];
            $new_price = $_POST['new_price'];
            
            $query = "UPDATE hospital_vaccines 
                     SET hospital_price = :price 
                     WHERE id = :id AND hospital_id = :hospital_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':price', $new_price);
            $stmt->bindParam(':id', $inventory_id);
            $stmt->bindParam(':hospital_id', $hospital['id']);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: vaccine_inventory.php?success=Price updated successfully!");
                exit();
            } else {
                $error = "Failed to update price.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Render header
render_hospital_header($page_title);
display_messages();

// Handle success message from URL parameter
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get hospital's current vaccine inventory with complete vaccine details
$query = "SELECT hv.*, v.name, v.description, v.recommended_age_months, v.dosage, v.manufacturer, v.status
          FROM hospital_vaccines hv
          JOIN vaccines v ON hv.vaccine_id = v.id
          WHERE hv.hospital_id = :hospital_id
          ORDER BY v.recommended_age_months, v.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital['id']);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Vaccine Inventory</h2>
        <p class="text-muted">Manage your vaccine stock and add new vaccines</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccineModal">
            <i class="fas fa-plus me-2"></i>Add New Vaccine
        </button>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Inventory Grid -->
<div class="row g-4">
    <?php foreach ($inventory as $item): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card inventory-card h-100 <?php echo ($item['quantity'] <= 10) ? 'border-warning' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <span class="badge bg-<?php 
                            if ($item['quantity'] == 0) echo 'danger';
                            elseif ($item['quantity'] < 10) echo 'warning';
                            else echo 'success';
                        ?>">
                            <?php 
                            if ($item['quantity'] == 0) echo 'Out of Stock';
                            elseif ($item['quantity'] < 10) echo 'Low Stock';
                            else echo 'In Stock';
                            ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-baby me-1"></i>
                            Recommended age: <?php echo $item['recommended_age_months']; ?> months
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-syringe me-1"></i>
                            Dosage: <?php echo htmlspecialchars($item['dosage']); ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-industry me-1"></i>
                            Manufacturer: <?php echo htmlspecialchars($item['manufacturer']); ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-hospital me-1"></i>
                            Price: 
                            <?php if ($item['hospital_price']): ?>
                                PKR <?php echo number_format($item['hospital_price'], 2); ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0">Current Stock</p>
                            <h3 class="fw-bold <?php echo ($item['quantity'] < 10) ? 'text-warning' : 'text-success'; ?>">
                                <?php echo $item['quantity']; ?> units
                            </h3>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updateQuantityModal<?php echo $item['id']; ?>">
                                <i class="fas fa-box me-1"></i>Stock
                            </button>
                            <button class="btn btn-outline-success me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updatePriceModal<?php echo $item['id']; ?>">
                                <i class="fas fa-tag me-1"></i>Price
                            </button>
                            <form method="POST" class="d-inline" 
                                  onsubmit="return confirm('Are you sure you want to remove this vaccine?');">
                                <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_vaccine" class="btn btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Quantity Modal -->
            <div class="modal fade" id="updateQuantityModal<?php echo $item['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Stock - <?php echo htmlspecialchars($item['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Stock: <?php echo $item['quantity']; ?> units</label>
                                    <input type="number" name="new_quantity" class="form-control" 
                                           value="<?php echo $item['quantity']; ?>" min="0" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_quantity" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Update Price Modal -->
            <div class="modal fade" id="updatePriceModal<?php echo $item['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Price - <?php echo htmlspecialchars($item['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Set Price</label>
                                    <input type="number" name="new_price" class="form-control" 
                                           value="<?php echo $item['hospital_price'] ? $item['hospital_price'] : ''; ?>" 
                                           min="0" step="0.01" required>
                                    <div class="form-text">Set your hospital price for this vaccine</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_price" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Price
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Vaccine Modal -->
<div class="modal fade" id="addVaccineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Vaccine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vaccine Name</label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="form-text">Enter the official name of the vaccine</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Initial Stock Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Price (PKR)</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                            <div class="form-text">Set the price for this vaccine</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                        <div class="form-text">Include details about the vaccine's purpose and any important information</div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Recommended Age (months)</label>
                            <input type="number" name="recommended_age_months" class="form-control" min="0" required>
                            <div class="form-text">Age in months when vaccine should be given</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dosage</label>
                            <input type="text" name="dosage" class="form-control" required 
                                   placeholder="e.g., 0.5ml, 2 drops">
                            <div class="form-text">Amount per dose (ml, drops, etc.)</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" class="form-control" required>
                            <div class="form-text">Company that produces the vaccine</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_new_vaccine" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New Vaccine
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_hospital_footer(); ?>
