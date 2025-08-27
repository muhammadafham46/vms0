<?php
session_start();

// Redirect to role selection if no user_type is set
if (!isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

// Redirect to index.php if user_type is not parent
if ($_SESSION['user_type'] !== 'parent') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal - Vaccination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: none;
        }
        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            height: 70vh;
            display: flex;
            align-items: center;
            color: white;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.8rem 2rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index_parent.php">
                <i class="fas fa-syringe me-2"></i>
                <span class="fw-bold">Parent Portal</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a href="index.php" class="nav-link me-3">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a href="logout.php?clear_role=true" class="btn btn-outline-light">
                    <i class="fas fa-exchange-alt me-2"></i>Change Role
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-4">Welcome to Parent Portal</h1>
            <p class="lead mb-4 fs-5">Comprehensive vaccination management for your child's health and safety</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="register.php?user_type=parent" class="btn btn-primary btn-lg px-4 py-3">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Why Choose Our Parent Portal?</h2>
                <p class="lead">Everything you need to manage your child's vaccination journey</p>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-shield-alt fa-2x mb-3"></i>
                        <h4>Vaccine Safety</h4>
                        <p class="mb-0">Track all vaccinations with detailed records</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-bell fa-2x mb-3"></i>
                        <h4>Smart Reminders</h4>
                        <p class="mb-0">Never miss an important vaccination date</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-calendar-check fa-2x mb-3"></i>
                        <h4>Easy Booking</h4>
                        <p class="mb-0">Schedule appointments with trusted hospitals</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h4>Progress Tracking</h4>
                        <p class="mb-0">Monitor your child's vaccination progress</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Cards -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Parent Dashboard Features</h2>
                <p class="lead">Access all tools to manage your child's health</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-child feature-icon"></i>
                        <h4 class="fw-bold mb-3">Child Profiles</h4>
                        <p class="text-muted mb-4">Create and manage profiles for all your children with complete medical information including birth details, allergies, and special requirements.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-calendar-alt feature-icon"></i>
                        <h4 class="fw-bold mb-3">Appointments</h4>
                        <p class="text-muted mb-4">Schedule, reschedule, or cancel vaccination appointments. View upcoming appointments and get directions to hospitals.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-bell feature-icon"></i>
                        <h4 class="fw-bold mb-3">Vaccination Reminders</h4>
                        <p class="text-muted mb-4">Receive timely notifications for upcoming vaccinations via email and SMS. Never miss an important vaccination date again.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-file-medical feature-icon"></i>
                        <h4 class="fw-bold mb-3">Vaccination Records</h4>
                        <p class="text-muted mb-4">Access complete vaccination history for each child. Download vaccination certificates and reports for school admissions.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-hospital feature-icon"></i>
                        <h4 class="fw-bold mb-3">Find Hospitals</h4>
                        <p class="text-muted mb-4">Discover nearby hospitals with available vaccination slots. Compare services and read reviews from other parents.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-question-circle feature-icon"></i>
                        <h4 class="fw-bold mb-3">Support & Help</h4>
                        <p class="text-muted mb-4">Get assistance with any questions about vaccinations, appointment booking, or using the parent portal features.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join thousands of parents who trust our system for their child's vaccination needs</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="register.php?user_type=parent" class="btn btn-light btn-lg px-4 py-3">
                    <i class="fas fa-user-plus me-2"></i>Sign Up Now
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 Child Vaccination Management System. All rights reserved.</p>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
