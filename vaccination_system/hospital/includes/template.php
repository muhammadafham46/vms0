<?php
/**
 * Consolidated Template System for Hospital Panel
 * This file provides common functionality and structure for all hospital pages
 */

// Define constant to prevent direct access
define('HOSPITAL_TEMPLATE', true);

/**
 * Initialize hospital session and database connection
 * This should be called at the beginning of each hospital page
 */
function init_hospital_page() {
    require_once '../config/session.php';
    require_once '../config/database.php';
    require_once __DIR__ . '/feature_access.php'; // features array yahan se aayega
    redirectIfNotHospital();
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "
        SELECT h.*, hp.name AS plan_name, hp.price AS plan_price, hp.duration,
               hp.bookings_limit, hp.branches_limit, hp.staff_accounts_limit,
               hp.sms_reminders, hp.features, hp.is_active,

               
               -- Current month appointments
                    (SELECT COUNT(*) FROM appointments a 
                    WHERE a.hospital_id = h.id 
                    AND MONTH(a.appointment_date) = MONTH(CURRENT_DATE()) 
                    AND YEAR(a.appointment_date) = YEAR(CURRENT_DATE())
                    ) AS current_month_appointments,

               -- Branches
               (SELECT COUNT(*) FROM hospital_branches b WHERE b.hospital_id = h.id) AS branches,

               -- Staff accounts
               (SELECT COUNT(*) FROM hospital_staff s WHERE s.hospital_id = h.id) AS staff_accounts

        FROM hospitals h
        JOIN users u ON h.id = u.hospital_id
        LEFT JOIN hospital_plans hp ON h.current_plan_id = hp.id
        WHERE u.id = :user_id AND u.user_type = 'hospital'
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    // Stats
    $stats = [];
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM appointments WHERE hospital_id = :hid");
    $stmt->execute([':hid' => $hospital['id']]);
    $stats['total_appointments'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = :hid AND status='pending'");
    $stmt->execute([':hid' => $hospital['id']]);
    $stats['pending_appointments'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = :hid AND status='approved'");
    $stmt->execute([':hid' => $hospital['id']]);
    $stats['approved_appointments'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = :hid AND DATE(appointment_date)=CURDATE()");
    $stmt->execute([':hid' => $hospital['id']]);
    $stats['today_appointments'] = $stmt->fetchColumn();

    // Recent appointments
    $stmt = $db->prepare("SELECT a.*, c.name as child_name, u.full_name as parent_name, v.name as vaccine_name 
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN users u ON a.parent_id = u.id
        JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.hospital_id = :hid
        ORDER BY a.appointment_date DESC LIMIT 5
    ");
    $stmt->execute([':hid' => $hospital['id']]);
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Low stock vaccines
    $stmt = $db->prepare("SELECT hv.*, v.name, v.manufacturer 
        FROM hospital_vaccines hv
        JOIN vaccines v ON hv.vaccine_id = v.id
        WHERE hv.hospital_id = :hid AND hv.quantity <= 10
        ORDER BY hv.quantity ASC LIMIT 5
    ");
    $stmt->execute([':hid' => $hospital['id']]);
    $low_stock_vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'db' => $db,
        'hospital' => $hospital,
        'stats' => $stats,
        'recent_appointments' => $recent_appointments,
        'low_stock_vaccines' => $low_stock_vaccines
    ];
}



/**
 * Render the page header with common HTML structure
 */
function render_hospital_header($page_title = 'Hospital Panel') {
    global $hospital;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page_title); ?> - Vaccination System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .sidebar {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                height: 100vh;
                position: fixed;
                width: 250px;
                z-index: 1000;
                padding-top: 1rem;
            }
            .sidebar .nav-link {
                color: rgba(255, 255, 255, 0.8);
                padding: 0.8rem 1.5rem;
                border-left: 3px solid transparent;
                transition: all 0.2s;
            }
            .sidebar .nav-link:hover, 
            .sidebar .nav-link.active {
                color: white;
                background: rgba(255, 255, 255, 0.1);
                border-left-color: white;
            }
            .sidebar-divider {
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                margin: 1rem 0;
            }
            .main-content {
                margin-left: 250px;
                padding: 20px;
            }
            .stat-card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            .stat-card:hover {
                transform: translateY(-5px);
            }
        </style>
    </head>
    <body>
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid">
    <?php
}

/**
 * Render the page footer with common scripts
 */
function render_hospital_footer() {
    ?>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Display success/error messages from session
 */
function display_messages() {
    if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
    
    if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
}

/**
 * Restrict hospital from accessing features not in their plan
 */

function redirect_if_feature_not_accessible($feature_key, $hospital) {
    // Agar hospital ya plan ka data hi missing hai
    if (!$hospital || empty($hospital['features'])) {
        $_SESSION['error'] = "Access denied: Feature not available.";
        header("Location: dashboard.php");
        exit();
    }

    // Hospital plan features decode (maan ke chalte hain DB me JSON format hai)
    $plan_features = json_decode($hospital['features'], true);

    // Agar JSON invalid ya array nahi mila
    if (!is_array($plan_features)) {
        $_SESSION['error'] = "Access denied: Invalid plan features.";
        header("Location: dashboard.php");
        exit();
    }

    // Check if requested feature is allowed
    if (!in_array($feature_key, $plan_features)) {
        $_SESSION['error'] = "You don't have access to this feature.";
        header("Location: dashboard.php");
        exit();
    }
}


// Prevent direct access
if (!defined('HOSPITAL_TEMPLATE')) {
    die('Direct access not permitted');
}