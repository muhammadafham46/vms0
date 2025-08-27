<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $report_type = $_GET['report'] ?? 'children';
    
    // Get all children with parent information for export
    $query = "SELECT c.*, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone
              FROM children c
              JOIN users u ON c.parent_id = u.id
              ORDER BY c.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $children_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($export_type === 'pdf') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="children_report_' . date('Y-m-d') . '.pdf"');
        
        // Simple PDF content
        echo "%PDF-1.4\n";
        echo "1 0 obj\n";
        echo "<< /Type /Catalog /Pages 2 0 R >>\n";
        echo "endobj\n";
        echo "2 0 obj\n";
        echo "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        echo "endobj\n";
        echo "3 0 obj\n";
        echo "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\n";
        echo "endobj\n";
        echo "4 0 obj\n";
        echo "<< /Length 100 >>\n";
        echo "stream\n";
        echo "BT\n";
        echo "/F1 12 Tf\n";
        echo "50 750 Td\n";
        echo "(Children Report) Tj\n";
        echo "50 730 Td\n";
        echo "(Generated on: " . date('Y-m-d H:i:s') . ") Tj\n";
        echo "50 710 Td\n";
        echo "(Total Children: " . count($children_export) . ") Tj\n";
        echo "ET\n";
        echo "endstream\n";
        echo "endobj\n";
        echo "xref\n";
        echo "0 5\n";
        echo "0000000000 65535 f \n";
        echo "0000000010 00000 n \n";
        echo "0000000053 00000 n \n";
        echo "0000000110 00000 n \n";
        echo "0000000203 00000 n \n";
        echo "trailer\n";
        echo "<< /Size 5 /Root 1 0 R >>\n";
        echo "startxref\n";
        echo "350\n";
        echo "%%EOF\n";
        exit;
        
    } elseif ($export_type === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="children_report_' . date('Y-m-d') . '.xls"');
        
        echo "<html>";
        echo "<head>";
        echo "<style>";
        echo "table { border-collapse: collapse; width: 100%; }";
        echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
        echo "th { background-color: #f2f2f2; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        echo "<h2>Children Report</h2>";
        echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>Total Children: " . count($children_export) . "</p>";
        
        echo "<table>";
        echo "<tr>";
        echo "<th>Child Name</th>";
        echo "<th>Date of Birth</th>";
        echo "<th>Age</th>";
        echo "<th>Gender</th>";
        echo "<th>Blood Group</th>";
        echo "<th>Parent Name</th>";
        echo "<th>Parent Email</th>";
        echo "<th>Parent Phone</th>";
        echo "<th>Registered On</th>";
        echo "</tr>";
        
        foreach ($children_export as $child) {
            $age = date_diff(date_create($child['date_of_birth']), date_create('today'))->y;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($child['name']) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($child['date_of_birth'])) . "</td>";
            echo "<td>" . $age . " years</td>";
            echo "<td>" . ucfirst($child['gender']) . "</td>";
            echo "<td>" . ($child['blood_group'] ?: 'Not specified') . "</td>";
            echo "<td>" . htmlspecialchars($child['parent_name']) . "</td>";
            echo "<td>" . $child['parent_email'] . "</td>";
            echo "<td>" . $child['parent_phone'] . "</td>";
            echo "<td>" . date('M d, Y', strtotime($child['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</body>";
        echo "</html>";
        exit;
    }
}

// Get all children with parent information for display
$query = "SELECT c.*, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone
          FROM children c
          JOIN users u ON c.parent_id = u.id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set template variables
$page_title = "All Children - Admin Dashboard";
$page_heading = "All Children Details";

// Start output buffering
ob_start();
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- <h2 class="fw-bold">All Children Details</h2> -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i>Export Reports
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?export=excel&report=children">
                        <i class="fas fa-file-excel me-2"></i>Excel Report
                    </a></li>
                    <li><a class="dropdown-item" href="?export=pdf&report=children">
                        <i class="fas fa-file-pdf me-2"></i>PDF Report
                    </a></li>
                </ul>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search by child name..." id="searchInput">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="genderFilter">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="filterTable()">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-child me-2"></i>Children List (<?php echo count($children); ?> records)</h5>
            </div>
            <div class="card-body">
                <?php if (count($children) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="childrenTable">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Date of Birth</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Parent Name</th>
                                    <th>Parent Contact</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): 
                                    $age = date_diff(date_create($child['date_of_birth']), date_create('today'))->y;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($child['name']); ?></strong>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($child['date_of_birth'])); ?></td>
                                        <td><?php echo $age; ?> years</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($child['gender']) {
                                                    case 'male': echo 'primary'; break;
                                                    case 'female': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($child['gender']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($child['blood_group']): ?>
                                                <span class="badge bg-info"><?php echo $child['blood_group']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($child['parent_name']); ?></td>
                                        <td>
                                            <div><?php echo $child['parent_phone']; ?></div>
                                            <small class="text-muted"><?php echo $child['parent_email']; ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info" title="View Details" onclick="viewChild(<?php echo $child['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" title="Vaccination History" onclick="viewVaccinationHistory(<?php echo $child['id']; ?>)">
                                                    <i class="fas fa-syringe"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary" title="Send Reminder" onclick="sendReminder(<?php echo $child['id']; ?>)">
                                                    <i class="fas fa-bell"></i>
                                                </button>
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
                        <p class="text-muted">Parents need to register their children first.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-male fa-2x text-primary mb-2"></i>
                        <h5>Male Children</h5>
                        <h3 class="text-primary">
                            <?php echo count(array_filter($children, fn($c) => $c['gender'] === 'male')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-female fa-2x text-danger mb-2"></i>
                        <h5>Female Children</h5>
                        <h3 class="text-danger">
                            <?php echo count(array_filter($children, fn($c) => $c['gender'] === 'female')); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-baby fa-2x text-success mb-2"></i>
                        <h5>Under 1 Year</h5>
                        <h3 class="text-success">
                            <?php echo count(array_filter($children, function($c) {
                                $age = date_diff(date_create($c['date_of_birth']), date_create('today'))->y;
                                return $age < 1;
                            })); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user fa-2x text-warning mb-2"></i>
                        <h5>1-5 Years</h5>
                        <h3 class="text-warning">
                            <?php echo count(array_filter($children, function($c) {
                                $age = date_diff(date_create($c['date_of_birth']), date_create('today'))->y;
                                return $age >= 1 && $age <= 5;
                            })); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Child Details Modal -->
        <div class="modal fade" id="childDetailsModal" tabindex="-1" aria-labelledby="childDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="childDetailsModalLabel">Child Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Child Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Name:</th>
                                        <td id="childName">-</td>
                                    </tr>
                                    <tr>
                                        <th>Date of Birth:</th>
                                        <td id="childDOB">-</td>
                                    </tr>
                                    <tr>
                                        <th>Gender:</th>
                                        <td id="childGender">-</td>
                                    </tr>
                                    <tr>
                                        <th>Blood Group:</th>
                                        <td id="childBloodGroup">-</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Parent Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Parent Name:</th>
                                        <td id="parentName">-</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td id="parentEmail">-</td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td id="parentPhone">-</td>
                                    </tr>
                                    <tr>
                                        <th>Registered On:</th>
                                        <td id="registeredDate">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
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
function filterTable() {
    const search = document.getElementById(\'searchInput\').value.toLowerCase();
    const gender = document.getElementById(\'genderFilter\').value;
    const date = document.getElementById(\'dateFilter\').value;
    
    const rows = document.querySelectorAll(\'#childrenTable tbody tr\');
    
    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const childGender = row.cells[3].textContent.toLowerCase();
        const dob = row.cells[1].textContent;
        
        const matchesSearch = name.includes(search);
        const matchesGender = !gender || childGender.includes(gender);
        const matchesDate = !date || dob.includes(new Date(date).toLocaleDateString(\'en-US\', { 
            month: \'short\', 
            day: \'2-digit\', 
            year: \'numeric\' 
        }).replace(\',\', \'\'));
        
        row.style.display = (matchesSearch && matchesGender && matchesDate) ? \'\' : \'none\';
    });
}

function viewChild(childId) {
    // Open child details in a new modal
    fetch(\'get_child_details.php?id=\' + childId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate modal with child details
                document.getElementById(\'childName\').textContent = data.child.name;
                document.getElementById(\'childDOB\').textContent = new Date(data.child.date_of_birth).toLocaleDateString();
                document.getElementById(\'childGender\').textContent = data.child.gender;
                document.getElementById(\'childBloodGroup\').textContent = data.child.blood_group || \'Not specified\';
                document.getElementById(\'parentName\').textContent = data.child.parent_name;
                document.getElementById(\'parentEmail\').textContent = data.child.parent_email;
                document.getElementById(\'parentPhone\').textContent = data.child.parent_phone;
                document.getElementById(\'registeredDate\').textContent = new Date(data.child.created_at).toLocaleDateString();
                
                // Show the modal
                new bootstrap.Modal(document.getElementById(\'childDetailsModal\')).show();
            } else {
                alert(\'Error loading child details: \' + data.message);
            }
        })
        .catch(error => {
            console.error(\'Error:\', error);
            alert(\'Error loading child details. Please try again.\');
        });
}

function viewVaccinationHistory(childId) {
    // Redirect to vaccination history page for this child
    window.location.href = \'vaccination_history.php?child_id=\' + childId;
}

function sendReminder(childId) {
    if (confirm(\'Send vaccination reminder to parent? This will notify the parent about upcoming or missed vaccinations.\')) {
        fetch(\'send_reminder.php\', {
            method: \'POST\',
            headers: {
                \'Content-Type\': \'application/json\',
            },
            body: JSON.stringify({ child_id: childId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(\'Reminder sent successfully!\');
            } else {
                alert(\'Error sending reminder: \' + data.message);
            }
        })
        .catch(error => {
            console.error(\'Error:\', error);
            alert(\'Error sending reminder. Please try again.\');
        });
    }
}

// Initialize search and filter
document.getElementById(\'searchInput\').addEventListener(\'input\', filterTable);
document.getElementById(\'genderFilter\').addEventListener(\'change\', filterTable);
document.getElementById(\'dateFilter\').addEventListener(\'change\', filterTable);
</script>';

include 'includes/template.php';
?>
