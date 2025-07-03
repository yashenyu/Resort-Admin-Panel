<?php
/**
 * Bookings Module Functions
 * 
 * Contains all functions related to booking operations
 */

/**
 * Get all bookings with optional filtering
 * 
 * @param array $filters Optional filters (status, date_from, date_to)
 * @param int $limit Limit results, 0 for no limit
 * @param int $offset Offset for pagination
 * @return array Array of bookings
 */
function get_bookings($filters = [], $limit = 0, $offset = 0) {
    global $conn;
    
    $query = "
        SELECT b.*, r.room_type, r.room_number,
               CONCAT(u.first_name, ' ', u.last_name) AS customer_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN users u ON b.user_id = u.user_id
        WHERE 1=1
    ";
    
    if (!empty($filters)) {
        if (isset($filters['status']) && $filters['status']) {
            $status = sanitize($filters['status']);
            $query .= " AND b.status = '$status'";
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $date_from = sanitize($filters['date_from']);
            $query .= " AND b.check_in_date >= '$date_from'";
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $date_to = sanitize($filters['date_to']);
            $query .= " AND b.check_in_date <= '$date_to'";
        }
        
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                r.room_type LIKE '%$search%' OR
                r.room_number LIKE '%$search%' OR
                b.booking_id LIKE '%$search%'
            )";
        }
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    if ($limit > 0) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $bookings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

/**
 * Count total bookings with given filters
 * 
 * @param array $filters Optional filters
 * @return int Total number of bookings
 */
function count_bookings($filters = []) {
    global $conn;
    
    $query = "
        SELECT COUNT(*) as total
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN users u ON b.user_id = u.user_id
        WHERE 1=1
    ";
    
    if (!empty($filters)) {
        if (isset($filters['status']) && $filters['status']) {
            $status = sanitize($filters['status']);
            $query .= " AND b.status = '$status'";
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $date_from = sanitize($filters['date_from']);
            $query .= " AND b.check_in_date >= '$date_from'";
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $date_to = sanitize($filters['date_to']);
            $query .= " AND b.check_in_date <= '$date_to'";
        }
        
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                r.room_type LIKE '%$search%' OR
                r.room_number LIKE '%$search%' OR
                b.booking_id LIKE '%$search%'
            )";
        }
    }
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

/**
 * Get a single booking by ID
 * 
 * @param int $booking_id Booking ID
 * @return array|null Booking data or null if not found
 */
function get_booking($booking_id) {
    global $conn;
    
    $booking_id = (int)$booking_id;
    
    $query = "
        SELECT b.*, r.room_type, r.room_number, r.price_per_night,
               CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
               u.email, u.phone
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = $booking_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

/**
 * Update booking status
 * 
 * @param int $booking_id Booking ID
 * @param string $status New status
 * @return bool Whether update was successful
 */
function update_booking_status($booking_id, $status) {
    global $conn;
    
    $booking_id = (int)$booking_id;
    $status = sanitize($status);
    
    $query = "
        UPDATE bookings
        SET status = '$status'
        WHERE booking_id = $booking_id
    ";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['admin_id'], "Updated booking #$booking_id status to $status");
        return true;
    }
    
    return false;
}

/**
 * Update booking details
 * 
 * @param int $booking_id Booking ID
 * @param array $data Booking data
 * @return bool Whether update was successful
 */
function update_booking($booking_id, $data) {
    global $conn;
    
    $booking_id = (int)$booking_id;
    
    $status = sanitize($data['status']);
    $check_in = sanitize($data['check_in_date']);
    $check_out = sanitize($data['check_out_date']);
    $guests = (int)$data['guests'];
    
    $booking = get_booking($booking_id);
    if (!$booking) {
        return false;
    }
    
    $room_id = $booking['room_id'];
    $errors = validate_booking_dates($check_in, $check_out, $room_id, $booking_id);
    
    if (!empty($errors)) {
        return false;
    }
    
    $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    
    $total_price = $booking['price_per_night'] * $nights;
    
    $query = "
        UPDATE bookings
        SET status = '$status',
            check_in_date = '$check_in',
            check_out_date = '$check_out',
            guests = $guests,
            total_price = $total_price
        WHERE booking_id = $booking_id
    ";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['admin_id'], "Updated booking #$booking_id details");
        return true;
    }
    
    return false;
}

/**
 * Cancel a booking
 * 
 * @param int $booking_id Booking ID
 * @return bool Whether cancellation was successful
 */
function cancel_booking($booking_id) {
    return update_booking_status($booking_id, 'Cancelled');
}

/**
 * Complete a booking
 * 
 * @param int $booking_id Booking ID
 * @return bool Whether completion was successful
 */
function complete_booking($booking_id) {
    return update_booking_status($booking_id, 'Completed');
}

/**
 * Get booking statistics
 * 
 * @return array Booking statistics
 */
function get_booking_stats() {
    global $conn;
    
    $stats = [
        'total_bookings' => 0,
        'active_bookings' => 0,
        'total_revenue' => 0,
        'occupancy_rate' => 0,
    ];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings");
    if ($result) {
        $stats['total_bookings'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Confirmed'");
    if ($result) {
        $stats['active_bookings'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    $result = mysqli_query($conn, "SELECT SUM(total_price) AS revenue FROM bookings WHERE status IN ('Confirmed', 'Completed')");
    if ($result) {
        $stats['total_revenue'] = (float)mysqli_fetch_assoc($result)['revenue'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms");
    if ($result) {
        $total_rooms = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT room_id) AS occupied FROM bookings WHERE status = 'Confirmed'");
    if ($result) {
        $occupied_rooms = (int)mysqli_fetch_assoc($result)['occupied'];
    }
    
    $stats['occupancy_rate'] = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100, 2) : 0;
    $stats['occupied_rooms'] = $occupied_rooms ?? 0;
    $stats['total_rooms'] = $total_rooms ?? 0;
    
    return $stats;
}

/**
 * Get monthly booking counts for the current year
 * 
 * @return array Monthly booking counts
 */
function get_monthly_bookings() {
    global $conn;
    
    $currentYear = date('Y');
    $result = mysqli_query($conn, "
        SELECT MONTH(check_in_date) AS month, COUNT(*) AS count
        FROM bookings
        WHERE status IN ('Confirmed', 'Completed') AND YEAR(check_in_date) = $currentYear
        GROUP BY MONTH(check_in_date)
    ");
    
    if (!$result) {
        return array_fill(0, 12, 0);
    }
    
    // Initialize all 12 months with 0 bookings
    $counts = array_fill(0, 12, 0);
    
    // Populate counts with actual data
    while ($row = mysqli_fetch_assoc($result)) {
        $monthIndex = $row['month'] - 1;
        $counts[$monthIndex] = (int)$row['count'];
    }
    
    return $counts;
}

/**
 * Get popular room types
 * 
 * @param int $limit Limit results, 0 for no limit
 * @return array Room type statistics
 */
function get_popular_room_types($limit = 5) {
    global $conn;
    
    $limit = (int)$limit;
    $limitClause = $limit > 0 ? "LIMIT $limit" : '';
    
    $result = mysqli_query($conn, "
        SELECT room_type, COUNT(*) AS count
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.status IN ('Confirmed', 'Completed')
        GROUP BY room_type
        ORDER BY count DESC
        $limitClause
    ");
    
    if (!$result) {
        return [];
    }
    
    $room_types = [];
    $room_counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $room_types[] = $row['room_type'];
        $room_counts[] = (int)$row['count'];
    }
    
    return [
        'types' => $room_types,
        'counts' => $room_counts
    ];
}
