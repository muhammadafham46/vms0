<?php
session_start();

// Handle role selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_type'])) {
    $_SESSION['user_type'] = $_POST['user_type'];
    
    // Redirect based on user type
    switch ($_SESSION['user_type']) {
        case 'parent':
            header("Location: index_parent.php");
            exit();
        case 'hospital':
            header("Location: index_hospital.php");
            exit();        
        default:
            // Unknown user type, stay on this page
            break;
    }
}

// Clear role if coming from logout with clear_role parameter
if (isset($_GET['clear_role'])) {
    unset($_SESSION['user_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Vaccination Management System - Select Role</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .role-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        .role-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .btn-role {
            width: 100%;
            padding: 1rem;
            margin: 0.5rem 0;
            font-size: 1.1rem;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="role-card">
            <div class="mb-4">
                <i class="fas fa-syringe role-icon text-primary"></i>
                <h1 class="h2 fw-bold mb-2">Vaccination System</h1>
                <p class="text-muted">Please select your role to continue</p>
            </div>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <select name="user_type" class="form-select form-select-lg" required>
                        <option value="" disabled selected>-- Select Your Role --</option>
                        <option value="parent">Parent</option>
                        <option value="hospital">Hospital</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-arrow-right me-2"></i>Continue
                </button>
            </form>
            
            <div class="mt-4">
                <p class="text-muted small">Already have an account? 
                    <a href="login.php" class="text-decoration-none">Login here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
