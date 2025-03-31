<?php
/**
 * Initialization File
 * 
 * This file initializes the application by loading all required configurations,
 * establishing database connections, and setting up utility functions.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration files
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

// Include utility functions
require_once __DIR__ . '/../includes/functions/auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/validation.php';

// Authentication check (skip for login page)
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'logout.php'];

if (!in_array($current_page, $public_pages) && !is_authenticated()) {
    redirect('login.php');
}
