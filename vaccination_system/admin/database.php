<?php
require_once '../config/session.php';
require_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';
$tables = [];
$table_data = [];
$selected_table = '';

// Get list of all tables
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $message = 'Error fetching tables: ' . $e->getMessage();
    $message_type = 'danger';
}

// Handle table selection
if (isset($_GET['table']) && in_array($_GET['table'], $tables)) {
    $selected_table = $_GET['table'];
    
    try {
        // Get table structure
        $stmt = $db->query("DESCRIBE $selected_table");
        $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get table data with pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM $selected_table");
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $limit);
        
        $stmt = $db->query("SELECT * FROM $selected_table LIMIT $limit OFFSET $offset");
        $table_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $message = 'Error fetching table data: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle database backup
if (isset($_POST['backup'])) {
    try {
        $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backups directory if it doesn't exist
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Get all tables
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $output = "";
        foreach ($tables as $table) {
            // Table structure
            $output .= "--\n-- Table structure for table `$table`\n--\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $stmt = $db->query("SHOW CREATE TABLE $table");
            $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
            $output .= $create_table['Create Table'] . ";\n\n";
            
            // Table data
            $output .= "--\n-- Dumping data for table `$table`\n--\n";
            $stmt = $db->query("SELECT * FROM $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $output .= "INSERT INTO `$table` VALUES(";
                $values = array_map(function($value) use ($db) {
                    if ($value === null) return 'NULL';
                    return $db->quote($value);
                }, array_values($row));
                $output .= implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
        
        // Write to file
        file_put_contents($backup_file, $output);
        
        $message = 'Database backup created successfully: ' . basename($backup_file);
        $message_type = 'success';
        
    } catch (PDOException $e) {
        $message = 'Error creating backup: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle SQL query execution
if (isset($_POST['execute_sql']) && !empty($_POST['sql_query'])) {
    $sql_query = trim($_POST['sql_query']);
    
    try {
        if (stripos($sql_query, 'select') === 0) {
            // SELECT query
            $stmt = $db->query($sql_query);
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $affected_rows = $stmt->rowCount();
            
            $message = "Query executed successfully. $affected_rows rows returned.";
            $message_type = 'success';
            
        } else {
            // UPDATE, INSERT, DELETE, etc.
            $affected_rows = $db->exec($sql_query);
            $message = "Query executed successfully. $affected_rows rows affected.";
            $message_type = 'success';
        }
        
    } catch (PDOException $e) {
        $message = 'SQL Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle inline editing
if (isset($_POST['edit_row']) && isset($_POST['table_name']) && isset($_POST['primary_key'])) {
    $table_name = $_POST['table_name'];
    $primary_key = $_POST['primary_key'];
    $primary_value = $_POST['primary_value'];
    
    try {
        $update_fields = [];
        $params = [':primary_value' => $primary_value];
        
        foreach ($_POST as $field => $value) {
            if (strpos($field, 'field_') === 0 && $field !== 'primary_value') {
                $column_name = substr($field, 6);
                
                // Special handling for password fields
                if (strtolower($column_name) === 'password') {
                    // Skip password field if it's empty (don't update)
                    if (empty(trim($value))) {
                        continue;
                    }
                    // Hash the password before storing
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                
                $update_fields[] = "`$column_name` = :$column_name";
                $params[":$column_name"] = $value;
            }
        }
        
        if (!empty($update_fields)) {
            $sql = "UPDATE `$table_name` SET " . implode(', ', $update_fields) . 
                   " WHERE `$primary_key` = :primary_value";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $message = "Record updated successfully!";
            $message_type = 'success';
            
            // Refresh the page to show updated data
            header("Location: database.php?table=" . urlencode($table_name));
            exit;
        }
        
    } catch (PDOException $e) {
        $message = 'Error updating record: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle row deletion
if (isset($_POST['delete_row']) && isset($_POST['table_name']) && isset($_POST['primary_key'])) {
    $table_name = $_POST['table_name'];
    $primary_key = $_POST['primary_key'];
    $primary_value = $_POST['primary_value'];
    
    try {
        $sql = "DELETE FROM `$table_name` WHERE `$primary_key` = :primary_value";
        $stmt = $db->prepare($sql);
        $stmt->execute([':primary_value' => $primary_value]);
        
        $message = "Record deleted successfully!";
        $message_type = 'success';
        
        // Refresh the page to show updated data
        header("Location: database.php?table=" . urlencode($table_name));
        exit;
        
    } catch (PDOException $e) {
        $message = 'Error deleting record: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle adding new record
if (isset($_POST['add_row']) && isset($_POST['table_name'])) {
    $table_name = $_POST['table_name'];
    
    try {
        $insert_fields = [];
        $insert_values = [];
        $params = [];
        
        foreach ($_POST as $field => $value) {
            if (strpos($field, 'field_') === 0) {
                $column_name = substr($field, 6);
                
                // Special handling for password fields
                if (strtolower($column_name) === 'password' && !empty(trim($value))) {
                    // Hash the password before storing
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                
                // Skip empty values for auto-increment fields
                if (empty(trim($value)) && $table_structure) {
                    $is_auto_increment = false;
                    foreach ($table_structure as $column) {
                        if ($column['Field'] === $column_name && strpos($column['Extra'], 'auto_increment') !== false) {
                            $is_auto_increment = true;
                            break;
                        }
                    }
                    if ($is_auto_increment) {
                        continue;
                    }
                }
                
                $insert_fields[] = "`$column_name`";
                $insert_values[] = ":$column_name";
                $params[":$column_name"] = $value;
            }
        }
        
        if (!empty($insert_fields)) {
            $sql = "INSERT INTO `$table_name` (" . implode(', ', $insert_fields) . 
                   ") VALUES (" . implode(', ', $insert_values) . ")";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $message = "Record added successfully!";
            $message_type = 'success';
            
            // Refresh the page to show updated data
            header("Location: database.php?table=" . urlencode($table_name));
            exit;
        }
        
    } catch (PDOException $e) {
        $message = 'Error adding record: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Admin Dashboard</title>
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
        .sql-editor {
            font-family: 'Courier New', monospace;
            min-height: 150px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center py-4">
            <i class="fas fa-database fa-2x mb-2"></i>
            <h5>Vaccination System</h5>
            <small>Admin Panel</small>
        </div>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>           
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fas fa-database me-2"></i>Database Management</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <div class="dropdown">
                    <?php
                    // Get user's profile picture
                    $profile_picture_query = "SELECT profile_picture FROM users WHERE id = :user_id";
                    $profile_stmt = $db->prepare($profile_picture_query);
                    $profile_stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $profile_stmt->execute();
                    $user_profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <?php if (!empty($user_profile['profile_picture'])): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user_profile['profile_picture']); ?>" 
                                 class="rounded-circle me-2" 
                                 style="width: 32px; height: 32px; object-fit: cover;"
                                 alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user me-1"></i>
                        <?php endif; ?>
                        Profile
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Database Backup Section -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>Database Backup</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Create a complete backup of the database.</p>
                        <form method="POST">
                            <button type="submit" name="backup" class="btn btn-warning">
                                <i class="fas fa-download me-1"></i>Create Backup
                            </button>
                        </form>
                        <?php if (is_dir('../backups')): ?>
                            <div class="mt-3">
                                <h6>Recent Backups:</h6>
                                <?php
                                $backups = glob('../backups/backup_*.sql');
                                rsort($backups);
                                $recent_backups = array_slice($backups, 0, 5);
                                ?>
                                <ul class="list-group">
                                    <?php foreach ($recent_backups as $backup): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo basename($backup); ?>
                                            <span class="badge bg-secondary"><?php echo date('M j, Y', filemtime($backup)); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SQL Query Section -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>SQL Query</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">SQL Query:</label>
                                <textarea name="sql_query" class="form-control sql-editor" rows="5" placeholder="SELECT * FROM users;"></textarea>
                            </div>
                            <button type="submit" name="execute_sql" class="btn btn-info">
                                <i class="fas fa-play me-1"></i>Execute Query
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Tables Section -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Database Tables</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="list-group">
                            <?php foreach ($tables as $table): ?>
                                <a href="?table=<?php echo urlencode($table); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selected_table === $table ? 'active' : ''; ?>">
                                    <i class="fas fa-table me-2"></i><?php echo $table; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <?php if ($selected_table): ?>
                            <h4>Table: <?php echo $selected_table; ?></h4>
                            
                            <!-- Table Structure -->
                            <div class="mb-4">
                                <h5>Structure</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Type</th>
                                                <th>Null</th>
                                                <th>Key</th>
                                                <th>Default</th>
                                                <th>Extra</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_structure as $column): ?>
                                                <tr>
                                                    <td><?php echo $column['Field']; ?></td>
                                                    <td><?php echo $column['Type']; ?></td>
                                                    <td><?php echo $column['Null']; ?></td>
                                                    <td><?php echo $column['Key']; ?></td>
                                                    <td><?php echo $column['Default']; ?></td>
                                                    <td><?php echo $column['Extra']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Table Data -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Data (<?php echo $total_records; ?> records)</h5>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" data-bs-target="#addModal">
                                        <i class="fas fa-plus me-1"></i>Add New Record
                                    </button>
                                </div>
                                <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination pagination-sm">
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?table=<?php echo urlencode($selected_table); ?>&page=<?php echo $i; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <?php if (!empty($table_data)): ?>
                                                    <?php foreach (array_keys($table_data[0]) as $column): ?>
                                                        <th><?php echo $column; ?></th>
                                                    <?php endforeach; ?>
                                                    <th>Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_data as $row): ?>
                                                <tr>
                                                    <?php 
                                                    $primary_key_value = '';
                                                    $primary_key_name = '';
                                                    // Find primary key (assuming first column is usually primary key)
                                                    $first_column = true;
                                                    foreach ($row as $column => $value): 
                                                        if ($first_column) {
                                                            $primary_key_value = $value;
                                                            $primary_key_name = $column;
                                                            $first_column = false;
                                                        }
                                                        
                                                        // Special handling for password field - show actual password instead of hash
                                                        if (strtolower($column) === 'password' && !empty($value)) {
                                                            $value = '[HASHED_PASSWORD]';
                                                        }
                                                    ?>
                                                        <td><?php echo is_null($value) ? 'NULL' : htmlspecialchars(substr($value, 0, 100)); ?></td>
                                                    <?php endforeach; ?>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editModal"
                                                                    data-table="<?php echo $selected_table; ?>"
                                                                    data-primary-key="<?php echo $primary_key_name; ?>"
                                                                    data-primary-value="<?php echo $primary_key_value; ?>"
                                                                    onclick="loadEditForm(this)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="table_name" value="<?php echo $selected_table; ?>">
                                                                <input type="hidden" name="primary_key" value="<?php echo $primary_key_name; ?>">
                                                                <input type="hidden" name="primary_value" value="<?php echo $primary_key_value; ?>">
                                                                <button type="submit" name="delete_row" class="btn btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to delete this record?')">
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
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-database fa-3x mb-3"></i>
                                <p>Select a table from the left to view its structure and data.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addForm">
                    <div class="modal-body" id="addModalBody">
                        <?php if ($selected_table && isset($table_structure)): ?>
                            <input type="hidden" name="table_name" value="<?php echo $selected_table; ?>">
                            <?php foreach ($table_structure as $column): ?>
                                <?php 
                                // Skip auto-increment fields
                                if (strpos($column['Extra'], 'auto_increment') !== false) {
                                    continue;
                                }
                                
                                // Skip fields with default values that shouldn't be manually set
                                $skip_fields = ['created_at', 'updated_at', 'last_login'];
                                if (in_array($column['Field'], $skip_fields)) {
                                    continue;
                                }
                                ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $column['Field']; ?>:</label>
                                    <?php if (strtolower($column['Field']) === 'password'): ?>
                                        <input type="password" class="form-control" name="field_<?php echo $column['Field']; ?>" 
                                               placeholder="Enter password">
                                        <div class="form-text">Password will be hashed before storage.</div>
                                    <?php elseif (strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'varchar') !== false): ?>
                                        <input type="text" class="form-control" name="field_<?php echo $column['Field']; ?>" 
                                               placeholder="Enter <?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'int') !== false): ?>
                                        <input type="number" class="form-control" name="field_<?php echo $column['Field']; ?>" 
                                               placeholder="Enter <?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'date') !== false): ?>
                                        <input type="date" class="form-control" name="field_<?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'datetime') !== false || strpos($column['Type'], 'timestamp') !== false): ?>
                                        <input type="datetime-local" class="form-control" name="field_<?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'time') !== false): ?>
                                        <input type="time" class="form-control" name="field_<?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'decimal') !== false || strpos($column['Type'], 'float') !== false || strpos($column['Type'], 'double') !== false): ?>
                                        <input type="number" step="0.01" class="form-control" name="field_<?php echo $column['Field']; ?>" 
                                               placeholder="Enter <?php echo $column['Field']; ?>">
                                    <?php elseif (strpos($column['Type'], 'enum') !== false): ?>
                                        <?php
                                        // Extract enum values
                                        preg_match("/enum\('(.+)'\)/", $column['Type'], $matches);
                                        $enum_values = $matches[1] ? explode("','", $matches[1]) : [];
                                        ?>
                                        <select class="form-select" name="field_<?php echo $column['Field']; ?>">
                                            <option value="">Select <?php echo $column['Field']; ?></option>
                                            <?php foreach ($enum_values as $value): ?>
                                                <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control" name="field_<?php echo $column['Field']; ?>" 
                                               placeholder="Enter <?php echo $column['Field']; ?>">
                                    <?php endif; ?>
                                    
                                    <?php if ($column['Null'] === 'NO' && empty($column['Default'])): ?>
                                        <div class="form-text text-danger">This field is required.</div>
                                    <?php elseif (!empty($column['Default'])): ?>
                                        <div class="form-text">Default: <?php echo $column['Default']; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_row" class="btn btn-success">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body" id="editModalBody">
                        <!-- Form fields will be dynamically loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_row" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function loadEditForm(button) {
        const table = button.getAttribute('data-table');
        const primaryKey = button.getAttribute('data-primary-key');
        const primaryValue = button.getAttribute('data-primary-value');
        
        // Get the row data
        const row = button.closest('tr');
        const cells = row.querySelectorAll('td');
        const headers = row.closest('table').querySelectorAll('th');
        
        let formHTML = `
            <input type="hidden" name="table_name" value="${table}">
            <input type="hidden" name="primary_key" value="${primaryKey}">
            <input type="hidden" name="primary_value" value="${primaryValue}">
        `;
        
        // Create form fields for each column (except the last one which is actions)
        for (let i = 0; i < cells.length - 1; i++) {
            const columnName = headers[i].textContent;
            const cellValue = cells[i].textContent;
            
            // Special handling for password fields
            if (columnName.toLowerCase() === 'password') {
                formHTML += `
                    <div class="mb-3">
                        <label class="form-label">${columnName}:</label>
                        <input type="password" class="form-control" name="field_${columnName}" 
                               value="" placeholder="Leave blank to keep current password"
                               ${columnName === primaryKey ? 'readonly' : ''}>
                        <div class="form-text">Leave blank to keep the current hashed password unchanged.</div>
                    </div>
                `;
            } else {
                formHTML += `
                    <div class="mb-3">
                        <label class="form-label">${columnName}:</label>
                        <input type="text" class="form-control" name="field_${columnName}" 
                               value="${cellValue === 'NULL' ? '' : cellValue}" 
                               ${columnName === primaryKey ? 'readonly' : ''}>
                    </div>
                `;
            }
        }
        
        document.getElementById('editModalBody').innerHTML = formHTML;
    }
    
    // Auto-focus first input field when modal opens
    document.getElementById('editModal').addEventListener('shown.bs.modal', function () {
        const firstInput = this.querySelector('input[type="text"], input[type="password"]');
        if (firstInput) {
            firstInput.focus();
        }
    });
    </script>
</body>
</html>
