<?php
/**
 * Application Configuration
 * 
 * Centralized application settings
 */

// Application settings
define('APP_NAME', 'Resort Admin Panel');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/resort-admin');
define('APP_TIMEZONE', 'Asia/Shanghai'); // Set to your local timezone

// Default currency symbol and format
define('DEFAULT_CURRENCY', '₱');
define('DEFAULT_CURRENCY_CODE', 'PHP');

// Date formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');

// Error handling
define('DISPLAY_ERRORS', true);
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Set default timezone
date_default_timezone_set(APP_TIMEZONE);
