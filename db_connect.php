<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = "";
$database = "resort_db";

// Log connection attempt
error_log("Attempting to connect to database: $database");

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Connection failed: " . mysqli_connect_error());
}

error_log("Successfully connected to database: $database");

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>
