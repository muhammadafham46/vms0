<?php
session_start();

// Check if we need to clear only the role and redirect back to index.php
if (isset($_GET['clear_role'])) {
    unset($_SESSION['user_type']);
    header("Location: index.php");
    exit();
}

// Full logout - destroy session and redirect to login
session_destroy();
header("Location: login.php");
exit();
?>
