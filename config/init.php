<?php
/**
 * Initialization File
 * 
 * This file initializes the application by loading all required configurations,
 * establishing database connections, and setting up utility functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

require_once __DIR__ . '/../includes/functions/auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/validation.php';

$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'logout.php'];

if (!in_array($current_page, $public_pages) && !is_authenticated()) {
    redirect('login.php');
}
