<?php
session_start();

// Check if user is authenticated and is admin
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get product ID from URL parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID, redirect back
if($id <= 0) {
    header('Location: productlist.php');
    exit;
}

// Database connection
$serverName = "DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

// Prepare and execute DELETE query using parameterized query
$sql = "DELETE FROM STRBARAKSMENU WHERE PRODUCTID = ?";
$params = array($id);
$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    // If deletion failed, you might want to log the error
    $errors = sqlsrv_errors();
    error_log("Delete product failed: " . print_r($errors, true));
    
    // Optionally, you could set an error message in session
    $_SESSION['error'] = "Failed to delete product. Please try again.";
} else {
    // Check if any rows were affected
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if($rowsAffected === false) {
        $_SESSION['error'] = "Error checking deletion status.";
    } elseif($rowsAffected == 0) {
        $_SESSION['error'] = "Product not found or already deleted.";
    } else {
        $_SESSION['success'] = "Product deleted successfully!";
    }
}

// Close connection
sqlsrv_close($conn);

// Redirect back to product list
header('Location: productlist.php');
exit;