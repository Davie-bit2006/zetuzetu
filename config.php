
<?php

// ==========================================
// ZETUZETU MARKET - CONFIGURATION
// ==========================================

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// DATABASE CONFIGURATION
// ==========================================

$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "zetumarket";
$port = 3307;


// ==========================================
// DATABASE CONNECTION
// ==========================================

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

// Set character encoding
mysqli_set_charset($conn, "utf8mb4");


// ==========================================
// AUTHENTICATION FUNCTIONS
// ==========================================

// Check if admin is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in'])
        && $_SESSION['admin_logged_in'] === true;
}


// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

?>
