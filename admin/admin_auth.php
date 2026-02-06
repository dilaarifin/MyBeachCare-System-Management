<?php
// Admin Authentication Middleware
// Include this file at the top of all admin pages to restrict access

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to access the admin dashboard.";
    header("Location: ../login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. You do not have permission to access the admin dashboard.";
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once "../includes/db_conn.php";

// User is authenticated and authorized
// User is authenticated and authorized
$admin_id = $_SESSION['user_id'];

// Fetch latest user data to keep session in sync
try {
    $stmt = $conn->prepare("SELECT full_name, email, profile_image FROM users WHERE id = :id");
    $stmt->bindParam(':id', $admin_id);
    $stmt->execute();
    
    if ($user_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Update session with fresh data
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['profile_image'] = $user_data['profile_image'];
        
        $admin_name = $user_data['full_name'];
        $admin_email = $user_data['email'];
        $admin_image = $user_data['profile_image'];
    } else {
        // Fallback to session if fetch fails (shouldn't happen for valid user)
        $admin_name = $_SESSION['full_name'] ?? 'Admin';
        $admin_email = $_SESSION['email'] ?? '';
        $admin_image = $_SESSION['profile_image'] ?? null;
    }
} catch (PDOException $e) {
    // Fallback on error
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
    $admin_email = $_SESSION['email'] ?? '';
    $admin_image = $_SESSION['profile_image'] ?? null;
}
?>
