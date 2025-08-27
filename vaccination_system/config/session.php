<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isParent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parent';
}

function isHospital() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'hospital';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function redirectIfNotParent() {
    if (!isParent()) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function redirectIfNotHospital() {
    if (!isHospital()) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
?>
