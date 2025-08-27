<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get hospital ID from query parameter
$hospital_id = $_GET['hospital_id'] ?? null;

if (!$hospital_id) {
    header('Location: hospitals.php');
    exit();
}

// Get hospital details
$query = "SELECT * FROM hospitals WHERE id = :hospital_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital_id);
$stmt->execute();
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    header('Location: hospitals.php');
    exit();
}

// Get hospital's vaccine inventory with pricing
$query = "SELECT 
            hv.*, 
            v.name, 
            v.description, 
            v.recommended_age_months, 
            v.dosage, 
            v.manufacturer, 
            v.status as vaccine_status,
            hv.hospital_price,
            (SELECT COUNT(*) FROM appointments a 
             WHERE a.hospital_id = hv.hospital_id 
             AND a.vaccine_id = hv.vaccine_id 
             AND a.status = 'completed') as vaccines_used
          FROM hospital_vaccines hv
          JOIN vaccines v ON hv.vaccine_id = v.id
          WHERE hv.hospital_id = :hospital_id
          ORDER BY v.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital_id);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $inventory_id = $_POST['inventory_id'];
    $new_price = $_POST['new_price'];
    $change_reason = $_POST['change_reason'] ?? '';
    
    // Get current price for history
    $query = "SELECT hospital_price, vaccine_id FROM hospital_vaccines WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $inventory_id);
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        // Update price
        $query = "UPDATE hospital_vaccines 
                 SET hospital_price = :price 
                 WHERE id = :id AND hospital_id = :hospital_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':price', $new_price);
        $stmt->bindParam(':id', $inventory_id);
        $stmt->bindParam(':hospital_id', $hospital_id);
        
        if ($stmt->execute()) {
            // Add to price history
            $query = "INSERT INTO vaccine_price_history 
                     (hospital_id, vaccine_id, old_price, new_price, changed_by, change_reason)
                     VALUES (:hospital_id, :vaccine_id, :old_price, :new_price, :changed_by, :change_reason)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':hospital_id', $hospital_id);
            $stmt->bindParam(':vaccine_id', $current['vaccine_id']);
            $stmt->bindParam(':old_price', $current['hospital_price']);
            $stmt->bindParam(':new_price', $new_price);
            $stmt->bindParam(':changed_by', $_SESSION['full_name']);
            $stmt->bindParam(':change_reason', $change_reason);
            $stmt->execute();
            
            $_SESSION['success'] = "Price updated successfully!";
            // Refresh inventory
            header("Location: hospital_vaccine_inventory.php?hospital_id=$hospital_id");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update price.";
        }
    }
}

// Get price history for the hospital
$query = "SELECT vph.*, v.name as vaccine_name
          FROM vaccine_price_history vph
          JOIN vaccines v ON vph.vaccine_id = v.id
          WHERE vph.hospital_id = :hospital_id
          ORDER BY vph.changed_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':hospital_id', $hospital_id);
$stmt->execute();
$price_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set template variables
$page_title = "Hospital Vaccine Inventory - Admin Dashboard";
$page_heading = "Vaccine Inventory";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Vaccine Inventory</h2>
                <p class="text-muted"><?php echo htmlspecialchars($hospital['name']); ?> - <?php echo htmlspecialchars($hospital['address']); ?></p>
            </div>
            <div>
                <a href="hospitals.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Hospitals
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Inventory Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Total Vaccines</h6>
                        <h3 class="fw-bold text-primary"><?php echo count($inventory); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Vaccines Used</h6>
                        <h3 class="fw-bold text-info">
                            <?php echo array_sum(array_column($inventory, 'vaccines_used')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Low Stock (<10)</h6>
                        <h3 class="fw-bold text-warning">
                            <?php echo count(array_filter($inventory, fn($item) => $item['quantity'] < 10 && $item['quantity'] > 0)); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Out of Stock</h6>
                        <h3 class="fw-bold text-danger">
                            <?php echo count(array_filter($inventory, fn($item) => $item['quantity'] == 0)); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccine Inventory Table -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-syringe me-2"></i>Vaccine Inventory Details</h5>
            </div>
            <div class="card-body">
                <?php if (count($inventory) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Vaccine Name</th>
                                    <th>Stock</th>
                                    <th>Used</th>
                                    <th>Hospital Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['manufacturer']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                if ($item['quantity'] == 0) echo 'danger';
                                                elseif ($item['quantity'] < 10) echo 'warning';
                                                else echo 'success';
                                            ?>">
                                                <?php echo $item['quantity']; ?> units
                                            </span>
                                        </td>
                                        <td><?php echo $item['vaccines_used']; ?></td>
                                        <td>₹<?php echo number_format($item['hospital_price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['vaccine_status'] === 'available' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($item['vaccine_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editPriceModal<?php echo $item['id']; ?>">
                                                <i class="fas fa-edit me-1"></i>Edit Price
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Price Modal -->
                                    <div class="modal fade" id="editPriceModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Price - <?php echo htmlspecialchars($item['name']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Current Hospital Price</label>
                                                            <input type="text" class="form-control" 
                                                                   value="₹<?php echo number_format($item['hospital_price'], 2); ?>" 
                                                                   disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">New Hospital Price (₹)</label>
                                                            <input type="number" name="new_price" class="form-control" 
                                                                   step="0.01" min="0" 
                                                                   value="<?php echo $item['hospital_price']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason for Change (Optional)</label>
                                                            <textarea name="change_reason" class="form-control" rows="2" 
                                                                      placeholder="Enter reason for price change..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_price" class="btn btn-primary">Update Price</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-syringe fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No vaccines in inventory</h5>
                        <p class="text-muted">This hospital hasn't added any vaccines to their inventory yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Price History -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Price Changes</h5>
            </div>
            <div class="card-body">
                <?php if (count($price_history) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Vaccine</th>
                                    <th>Old Price</th>
                                    <th>New Price</th>
                                    <th>Difference</th>
                                    <th>Changed By</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($price_history as $history): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($history['vaccine_name']); ?></td>
                                        <td>₹<?php echo number_format($history['old_price'], 2); ?></td>
                                        <td>₹<?php echo number_format($history['new_price'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $diff = $history['new_price'] - $history['old_price'];
                                            $class = $diff > 0 ? 'price-increase' : ($diff < 0 ? 'price-decrease' : 'price-same');
                                            echo '<span class="' . $class . '">' . ($diff > 0 ? '+' : '') . '₹' . number_format($diff, 2) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($history['changed_by']); ?></td>
                                        <td><small><?php echo htmlspecialchars($history['change_reason'] ?? 'N/A'); ?></small></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($history['changed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-info-circle text-muted me-2"></i>
                        <span class="text-muted">No price changes recorded yet</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .price-difference {
                font-size: 0.8em;
            }
            .price-increase {
                color: #dc3545;
            }
            .price-decrease {
                color: #198754;
            }
            .price-same {
                color: #6c757d;
            }
        </style>
<?php
$content = ob_get_clean();

// Include template
include 'includes/template.php';
?>
