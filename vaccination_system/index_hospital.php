<?php
session_start();

// Redirect to role selection if no user_type is set
if (!isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

// Redirect to index.php if user_type is not hospital
if ($_SESSION['user_type'] !== 'hospital') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Portal - Vaccination System</title>
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
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2073&q=80');
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
        .benefit-item {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index_hospital.php">
                <i class="fas fa-hospital me-2"></i>
                <span class="fw-bold">Hospital Portal</span>
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
            <h1 class="display-3 fw-bold mb-4">Hospital Management Portal</h1>
            <p class="lead mb-4 fs-5">Streamline your vaccination services with our comprehensive management system</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="register.php?user_type=hospital" class="btn btn-primary btn-lg px-4 py-3">
                    <i class="fas fa-user-plus me-2"></i>Register Hospital
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Hospital Login
                </a>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Why Choose Our Hospital Portal?</h2>
                <p class="lead">Transform your vaccination services with advanced management tools</p>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h4>Patient Management</h4>
                        <p class="mb-0">Efficiently manage thousands of patient records</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                        <h4>Appointment System</h4>
                        <p class="mb-0">Streamlined booking and scheduling system</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-bar fa-2x mb-3"></i>
                        <h4>Analytics & Reports</h4>
                        <p class="mb-0">Comprehensive insights and performance metrics</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-cog fa-2x mb-3"></i>
                        <h4>Automation</h4>
                        <p class="mb-0">Automated reminders and inventory management</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Key Benefits for Hospitals</h2>
                <p class="lead">Enhance your vaccination services with our comprehensive platform</p>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-rocket fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Increased Efficiency</h4>
                        </div>
                        <p class="text-muted">Reduce administrative workload by 60% with automated appointment scheduling, patient reminders, and inventory management systems.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-chart-line fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Revenue Growth</h4>
                        </div>
                        <p class="text-muted">Increase patient throughput by 40% with optimized scheduling and reduce no-show rates with automated reminder systems.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-shield-alt fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Compliance & Safety</h4>
                        </div>
                        <p class="text-muted">Ensure complete vaccination compliance with automated tracking, expiry alerts, and comprehensive audit trails for all vaccines.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Staff Productivity</h4>
                        </div>
                        <p class="text-muted">Empower your medical staff with intuitive tools that reduce paperwork and allow them to focus on patient care rather than administration.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Cards -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Hospital Dashboard Features</h2>
                <p class="lead">Comprehensive tools to manage your vaccination center efficiently</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-syringe feature-icon"></i>
                        <h4 class="fw-bold mb-3">Vaccine Inventory</h4>
                        <p class="text-muted mb-4">Manage vaccine stock levels, track expiry dates, set low stock alerts, and maintain complete inventory records with batch tracking.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-calendar-check feature-icon"></i>
                        <h4 class="fw-bold mb-3">Appointment Management</h4>
                        <p class="text-muted mb-4">Schedule, reschedule, and manage vaccination appointments. View daily schedules, manage waitlists, and optimize staff allocation.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h4 class="fw-bold mb-3">Analytics & Reports</h4>
                        <p class="text-muted mb-4">Generate comprehensive reports on vaccination rates, revenue, patient demographics, and operational efficiency metrics.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-user-injured feature-icon"></i>
                        <h4 class="fw-bold mb-3">Patient Records</h4>
                        <p class="text-muted mb-4">Maintain complete electronic health records for all patients, including vaccination history, medical conditions, and consent forms.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-bell feature-icon"></i>
                        <h4 class="fw-bold mb-3">Automated Reminders</h4>
                        <p class="text-muted mb-4">Send automated SMS and email reminders for upcoming appointments, follow-up doses, and vaccination completion.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <i class="fas fa-cogs feature-icon"></i>
                        <h4 class="fw-bold mb-3">Staff Management</h4>
                        <p class="text-muted mb-4">Manage hospital staff accounts, assign roles and permissions, track working hours, and monitor performance metrics.</p>
                        <a href="login.php" class="btn btn-primary">Login to Access</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Subscription Plans</h2>
                <p class="lead">Choose the perfect plan for your healthcare facility</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-primary">Basic Plan</h4>
                            <h2 class="display-6 fw-bold">Rs. 5,000</h2>
                            <p class="text-muted">Monthly</p>
                        </div>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 100 appointments/month</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 1 branch location</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 3 staff accounts</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Basic reporting</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i> No SMS reminders</li>
                        </ul>
                        <a href="register.php?plan=basic" class="btn btn-outline-primary">Get Started</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-primary">Professional Plan</h4>
                            <h2 class="display-6 fw-bold">Rs. 15,000</h2>
                            <p class="text-muted">Monthly</p>
                        </div>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited appointments</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 3 branch locations</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 10 staff accounts</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced analytics</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 500 SMS/month</li>
                        </ul>
                        <a href="register.php?plan=professional" class="btn btn-primary">Get Started</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-primary">Enterprise Plan</h4>
                            <h2 class="display-6 fw-bold">Rs. 30,000</h2>
                            <p class="text-muted">Monthly</p>
                        </div>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited everything</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Multiple locations</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Custom branding</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Priority support</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> API access</li>
                        </ul>
                        <a href="register.php?plan=enterprise" class="btn btn-primary">Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Ready to Transform Your Hospital?</h2>
            <p class="lead mb-4">Join hundreds of healthcare providers using our platform to deliver exceptional vaccination services</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="register.php?user_type=hospital" class="btn btn-light btn-lg px-4 py-3">
                    <i class="fas fa-hospital me-2"></i>Register Your Hospital
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Access Your Portal
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
