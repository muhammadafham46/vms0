<?php
// Installation script for Vaccination System
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update database configuration
        $config_content = '<?php
class Database {
    private $host = "' . $host . '";
    private $db_name = "' . $dbname . '";
    private $username = "' . $username . '";
    private $password = "' . $password . '";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>';
        
        file_put_contents('config/database.php', $config_content);
        
        // Import database schema
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        
        $success = "Installation completed successfully!";
    } catch (PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Vaccination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .install-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="install-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-syringe fa-3x text-primary mb-3"></i>
                        <h2 class="fw-bold">Vaccination System Installation</h2>
                        <p class="text-muted">Complete the installation by configuring your database</p>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                            <br><br>
                            <strong>Default Admin Login:</strong><br>
                            Username: <code>admin</code><br>
                            Password: <code>password</code>
                            <br><br>
                            <a href="index.php" class="btn btn-success w-100">
                                <i class="fas fa-rocket me-2"></i>Launch Application
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Database Host</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-server"></i></span>
                                    <input type="text" class="form-control" name="host" value="localhost" required>
                                </div>
                                <small class="form-text text-muted">Usually 'localhost'</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Database Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-database"></i></span>
                                    <input type="text" class="form-control" name="dbname" value="vaccination_system" required>
                                </div>
                                <small class="form-text text-muted">Create this database in your MySQL server first</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Database Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" value="root" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Database Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password">
                                </div>
                                <small class="form-text text-muted">Leave empty if no password</small>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Before proceeding:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Create a MySQL database named 'vaccination_system'</li>
                                    <li>Ensure PHP PDO MySQL extension is enabled</li>
                                    <li>Make sure the config directory is writable</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-cogs me-2"></i>Install System
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <h6 class="text-muted">System Requirements</h6>
                            <div class="row text-start">
                                <div class="col-6">
                                    <small>
                                        <i class="fas fa-check text-success me-1"></i> PHP 7.4+<br>
                                        <i class="fas fa-check text-success me-1"></i> MySQL 5.7+<br>
                                        <i class="fas fa-check text-success me-1"></i> PDO Extension
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small>
                                        <i class="fas fa-check text-success me-1"></i> JSON Support<br>
                                        <i class="fas fa-check text-success me-1"></i> GD Library<br>
                                        <i class="fas fa-check text-success me-1"></i> MBString
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
