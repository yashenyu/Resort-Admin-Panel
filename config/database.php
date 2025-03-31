<?php
/**
 * Database Configuration
 * 
 * Centralized database connection settings
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'resort_db');

// Connection handling
function db_connect() {
    static $conn;
    
    if (!isset($conn)) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            die("Database Connection Failed: " . mysqli_connect_error());
        }
        
        // Set charset to ensure proper encoding
        mysqli_set_charset($conn, 'utf8mb4');
    }
    
    return $conn;
}

// Get database connection
$conn = db_connect();
