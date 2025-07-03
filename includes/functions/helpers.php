<?php
/**
 * Helper Functions
 * 
 * Utility functions used throughout the application
 */

/**
 * Redirect to another page
 * 
 * @param string $location URL to redirect to
 * @param array $params Optional query parameters
 */
function redirect($location, $params = []) {
    $url = $location;
    
    // Add query parameters if any
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    header("Location: $url");
    exit;
}

/**
 * Sanitize user input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize($input) {
    global $conn;
    
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    if ($conn) {
        $input = mysqli_real_escape_string($conn, $input);
    }
    
    return $input;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Format to use (defaults to DISPLAY_DATE_FORMAT)
 * @return string Formatted date
 */
function format_date($date, $format = null) {
    if (empty($date)) {
        return '';
    }
    
    if (!$format) {
        $format = defined('DISPLAY_DATE_FORMAT') ? DISPLAY_DATE_FORMAT : 'M d, Y';
    }
    
    $date_obj = date_create($date);
    if (!$date_obj) {
        return $date;
    }
    
    return date_format($date_obj, $format);
}

/**
 * Format currency for display
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency symbol (defaults to DEFAULT_CURRENCY)
 * @return string Formatted currency
 */
function format_currency($amount, $currency = null) {
    if (!$currency) {
        $currency = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : '₱';
    }
    
    return $currency . number_format((float)$amount, 2, '.', ',');
}

/**
 * Get status badge class based on status
 * 
 * @param string $status Status text
 * @return string CSS class for the badge
 */
function get_status_class($status) {
    return match(strtolower($status)) {
        'pending' => 'badge-secondary',
        'confirmed' => 'badge-primary',
        'cancelled' => 'badge-danger',
        'completed' => 'badge-success',
        default => 'badge-light'
    };
}

/**
 * Get pagination HTML
 * 
 * @param int $total_records Total number of records
 * @param int $records_per_page Records per page
 * @param int $current_page Current page
 * @param string $url_pattern URL pattern with :page placeholder
 * @return string Pagination HTML
 */
function get_pagination($total_records, $records_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    if ($current_page > 1) {
        $prev_url = str_replace(':page', $current_page - 1, $url_pattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    }
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $page_url = str_replace(':page', $i, $url_pattern);
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    if ($current_page < $total_pages) {
        $next_url = str_replace(':page', $current_page + 1, $url_pattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $next_url . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Debug function to print variables in a readable format
 * 
 * @param mixed $var Variable to debug
 * @param bool $die Whether to die after output
 */
function debug($var, $die = false) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}
