<?php
/**
 * Analytics Module Functions
 * 
 * Contains all functions related to analytics operations
 */

/**
 * Get visitor location data
 * 
 * @param int $limit Limit results, 0 for no limit
 * @return array Location data
 */
function get_location_data($limit = 5) {
    global $conn;
    
    $limit = (int)$limit;
    $limitClause = $limit > 0 ? "LIMIT $limit" : '';
    
    $query = "
        SELECT location, COUNT(*) AS count 
        FROM analytics
        GROUP BY location 
        ORDER BY count DESC
        $limitClause
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'labels' => [],
            'counts' => []
        ];
    }
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['location'] ?: 'Unknown';
        $counts[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts
    ];
}

/**
 * Get browser usage data
 * 
 * @return array Browser data
 */
function get_browser_data() {
    global $conn;
    
    $query = "
        SELECT browser, COUNT(*) AS count 
        FROM analytics
        GROUP BY browser 
        ORDER BY count DESC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'labels' => [],
            'counts' => []
        ];
    }
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['browser'] ?: 'Unknown';
        $counts[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts
    ];
}

/**
 * Get platform/OS usage data
 * 
 * @return array Platform data
 */
function get_platform_data() {
    global $conn;
    
    $query = "
        SELECT os, COUNT(*) AS count 
        FROM analytics
        GROUP BY os 
        ORDER BY count DESC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'labels' => [],
            'counts' => []
        ];
    }
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['os'] ?: 'Unknown';
        $counts[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts
    ];
}

/**
 * Get daily visits trend data
 * 
 * @param int $days Number of days to fetch
 * @return array Visits data
 */
function get_visits_trend($days = 30) {
    global $conn;
    
    $days = (int)$days;
    
    $query = "
        SELECT DATE(timestamp) as day, COUNT(*) as total 
        FROM analytics
        WHERE timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL $days DAY)
        GROUP BY day 
        ORDER BY day ASC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'dates' => [],
            'counts' => []
        ];
    }
    
    $dates = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['day'];
        $counts[] = (int)$row['total'];
    }
    
    return [
        'dates' => $dates,
        'counts' => $counts
    ];
}

/**
 * Get booking classification data for analytics
 * 
 * @return array Booking classification data
 */
function get_booking_classification() {
    global $conn;
    
    $query = "
        SELECT status, COUNT(*) as count
        FROM bookings
        GROUP BY status
        ORDER BY count DESC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'labels' => [],
            'counts' => []
        ];
    }
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['status'];
        $counts[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts
    ];
}

/**
 * Get guest segmentation data (number of guests per booking)
 * 
 * @return array Guest segmentation data
 */
function get_guest_segmentation() {
    global $conn;
    
    $query = "
        SELECT guests, COUNT(*) as count
        FROM bookings
        GROUP BY guests
        ORDER BY guests ASC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'labels' => [],
            'counts' => []
        ];
    }
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['guests'] . ' Guest' . ($row['guests'] > 1 ? 's' : '');
        $counts[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts
    ];
}

/**
 * Track a new visitor
 * 
 * @param string $location Visitor location
 * @param string $browser Browser used
 * @param string $os Operating system used
 * @param string $referrer Referrer URL
 * @return bool Whether tracking was successful
 */
function track_visitor($location, $browser, $os, $referrer = '') {
    global $conn;
    
    $location = sanitize($location);
    $browser = sanitize($browser);
    $os = sanitize($os);
    $referrer = sanitize($referrer);
    
    $query = "
        INSERT INTO analytics (location, browser, os, referrer)
        VALUES ('$location', '$browser', '$os', '$referrer')
    ";
    
    return mysqli_query($conn, $query) ? true : false;
}

/**
 * Get booking trends for regression analysis
 * 
 * @param int $months Number of months to analyze
 * @return array Booking trend data
 */
function get_booking_trends($months = 12) {
    global $conn;
    
    $months = (int)$months;
    
    $query = "
        SELECT 
            DATE_FORMAT(check_in_date, '%Y-%m') as month,
            COUNT(*) as bookings
        FROM 
            bookings
        WHERE 
            check_in_date >= DATE_SUB(CURRENT_DATE, INTERVAL $months MONTH)
        GROUP BY 
            DATE_FORMAT(check_in_date, '%Y-%m')
        ORDER BY 
            month ASC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'months' => [],
            'actual' => [],
            'forecast' => []
        ];
    }
    
    $months = [];
    $actual = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $months[] = $row['month'];
        $actual[] = (int)$row['bookings'];
    }
    
    // Simple linear regression for forecasting
    // This is a simplified implementation - in a real-world scenario,
    // you might want to use a more sophisticated forecasting algorithm
    $forecast = [];
    $n = count($actual);
    
    if ($n > 1) {
        $x = range(1, $n);
        $y = $actual;
        
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $meanX) * ($y[$i] - $meanY);
            $denominator += pow($x[$i] - $meanX, 2);
        }
        
        $slope = $denominator != 0 ? $numerator / $denominator : 0;
        $intercept = $meanY - $slope * $meanX;
        
        for ($i = 0; $i < $n; $i++) {
            $forecast[] = $intercept + $slope * $x[$i];
        }
        
        // Add 3 more months of forecast
        for ($i = $n + 1; $i <= $n + 3; $i++) {
            $newForecast = $intercept + $slope * $i;
            $forecast[] = max(0, $newForecast); // Ensure no negative forecasts
            $actual[] = null; // No actual data for future months
            
            // Calculate the next month
            $lastMonth = end($months);
            list($year, $month) = explode('-', $lastMonth);
            $month = (int)$month + 1;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $months[] = sprintf('%04d-%02d', $year, $month);
        }
    }
    
    return [
        'months' => $months,
        'actual' => $actual,
        'forecast' => $forecast
    ];
}
