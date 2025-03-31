<?php
/**
 * Rooms Module Functions
 * 
 * Contains all functions related to room operations
 */

/**
 * Get all rooms with optional filtering
 * 
 * @param array $filters Optional filters (status, room_type)
 * @param int $limit Limit results, 0 for no limit
 * @param int $offset Offset for pagination
 * @return array Array of rooms
 */
function get_rooms($filters = [], $limit = 0, $offset = 0) {
    global $conn;
    
    // Build query
    $query = "
        SELECT r.*, 
               (SELECT COUNT(*) FROM bookings WHERE room_id = r.room_id AND status = 'Confirmed') AS active_bookings
        FROM rooms r
        WHERE 1=1
    ";
    
    // Add filters
    if (!empty($filters)) {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int)$filters['status'];
            $query .= " AND r.is_active = $status";
        }
        
        if (isset($filters['room_type']) && $filters['room_type']) {
            $room_type = sanitize($filters['room_type']);
            $query .= " AND r.room_type = '$room_type'";
        }
        
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                r.room_type LIKE '%$search%' OR
                r.room_number LIKE '%$search%' OR
                r.description LIKE '%$search%'
            )";
        }
    }
    
    // Order by
    $query .= " ORDER BY r.room_type, r.room_number";
    
    // Add limit if specified
    if ($limit > 0) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    // Fetch all rooms
    $rooms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }
    
    return $rooms;
}

/**
 * Count total rooms with given filters
 * 
 * @param array $filters Optional filters
 * @return int Total number of rooms
 */
function count_rooms($filters = []) {
    global $conn;
    
    // Build query
    $query = "
        SELECT COUNT(*) as total
        FROM rooms r
        WHERE 1=1
    ";
    
    // Add filters
    if (!empty($filters)) {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int)$filters['status'];
            $query .= " AND r.is_active = $status";
        }
        
        if (isset($filters['room_type']) && $filters['room_type']) {
            $room_type = sanitize($filters['room_type']);
            $query .= " AND r.room_type = '$room_type'";
        }
        
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                r.room_type LIKE '%$search%' OR
                r.room_number LIKE '%$search%' OR
                r.description LIKE '%$search%'
            )";
        }
    }
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

/**
 * Get a single room by ID
 * 
 * @param int $room_id Room ID
 * @return array|null Room data or null if not found
 */
function get_room($room_id) {
    global $conn;
    
    $room_id = (int)$room_id;
    
    $query = "
        SELECT r.*,
               (SELECT COUNT(*) FROM bookings WHERE room_id = r.room_id AND status = 'Confirmed') AS active_bookings
        FROM rooms r
        WHERE r.room_id = $room_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

/**
 * Toggle room status (active/inactive)
 * 
 * @param int $room_id Room ID
 * @return bool Whether update was successful
 */
function toggle_room_status($room_id) {
    global $conn;
    
    $room_id = (int)$room_id;
    
    // Get current status
    $room = get_room($room_id);
    if (!$room) {
        return false;
    }
    
    $new_status = $room['is_active'] ? 0 : 1;
    $status_text = $new_status ? 'Active' : 'Inactive';
    
    $query = "
        UPDATE rooms
        SET is_active = $new_status
        WHERE room_id = $room_id
    ";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['admin_id'], "Changed room #{$room['room_number']} status to $status_text");
        return true;
    }
    
    return false;
}

/**
 * Update room details
 * 
 * @param int $room_id Room ID
 * @param array $data Room data
 * @return bool Whether update was successful
 */
function update_room($room_id, $data) {
    global $conn;
    
    $room_id = (int)$room_id;
    
    // Sanitize inputs
    $room_type = sanitize($data['room_type']);
    $room_number = sanitize($data['room_number']);
    $description = sanitize($data['description']);
    $price_per_night = (float)$data['price_per_night'];
    $capacity = (int)$data['capacity'];
    $is_active = isset($data['is_active']) ? 1 : 0;
    
    // Validate room number uniqueness (except for current room)
    $query = "
        SELECT room_id FROM rooms
        WHERE room_number = '$room_number' AND room_id != $room_id
    ";
    
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        return false; // Room number already exists
    }
    
    $query = "
        UPDATE rooms
        SET room_type = '$room_type',
            room_number = '$room_number',
            description = '$description',
            price_per_night = $price_per_night,
            capacity = $capacity,
            is_active = $is_active
        WHERE room_id = $room_id
    ";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['admin_id'], "Updated room #$room_number details");
        return true;
    }
    
    return false;
}

/**
 * Add a new room
 * 
 * @param array $data Room data
 * @return int|bool New room ID or false on failure
 */
function add_room($data) {
    global $conn;
    
    // Sanitize inputs
    $room_type = sanitize($data['room_type']);
    $room_number = sanitize($data['room_number']);
    $description = sanitize($data['description']);
    $price_per_night = (float)$data['price_per_night'];
    $capacity = (int)$data['capacity'];
    $is_active = isset($data['is_active']) ? 1 : 0;
    
    // Validate room number uniqueness
    $query = "
        SELECT room_id FROM rooms
        WHERE room_number = '$room_number'
    ";
    
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        return false; // Room number already exists
    }
    
    $query = "
        INSERT INTO rooms (room_type, room_number, description, price_per_night, capacity, is_active)
        VALUES ('$room_type', '$room_number', '$description', $price_per_night, $capacity, $is_active)
    ";
    
    if (mysqli_query($conn, $query)) {
        $room_id = mysqli_insert_id($conn);
        log_activity($_SESSION['admin_id'], "Added new room #$room_number");
        return $room_id;
    }
    
    return false;
}

/**
 * Get room statistics
 * 
 * @return array Room statistics
 */
function get_room_stats() {
    global $conn;
    
    $stats = [
        'total_rooms' => 0,
        'active_rooms' => 0,
        'occupied_rooms' => 0,
        'average_price' => 0,
    ];
    
    // Total Rooms
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms");
    if ($result) {
        $stats['total_rooms'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    // Active Rooms
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms WHERE is_active = 1");
    if ($result) {
        $stats['active_rooms'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    // Occupied Rooms
    $result = mysqli_query($conn, "
        SELECT COUNT(DISTINCT room_id) AS occupied 
        FROM bookings 
        WHERE status = 'Confirmed'
    ");
    if ($result) {
        $stats['occupied_rooms'] = (int)mysqli_fetch_assoc($result)['occupied'];
    }
    
    // Average Price
    $result = mysqli_query($conn, "SELECT AVG(price_per_night) AS avg_price FROM rooms");
    if ($result) {
        $stats['average_price'] = round((float)mysqli_fetch_assoc($result)['avg_price'], 2);
    }
    
    return $stats;
}

/**
 * Get all room types
 * 
 * @return array Array of room types
 */
function get_room_types() {
    global $conn;
    
    $query = "SELECT DISTINCT room_type FROM rooms ORDER BY room_type";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $types = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $types[] = $row['room_type'];
    }
    
    return $types;
}

/**
 * Check if a room is available for the given dates
 * 
 * @param int $room_id Room ID
 * @param string $check_in Check-in date
 * @param string $check_out Check-out date
 * @param int $booking_id Current booking ID (optional, for updates)
 * @return bool Whether the room is available
 */
function is_room_available($room_id, $check_in, $check_out, $booking_id = null) {
    global $conn;
    
    $room_id = (int)$room_id;
    $check_in = sanitize($check_in);
    $check_out = sanitize($check_out);
    
    $query = "
        SELECT booking_id 
        FROM bookings 
        WHERE room_id = $room_id 
        AND status = 'Confirmed'
        AND (
            (check_in_date <= '$check_in' AND check_out_date >= '$check_in') OR
            (check_in_date <= '$check_out' AND check_out_date >= '$check_out') OR
            (check_in_date >= '$check_in' AND check_out_date <= '$check_out')
        )
    ";
    
    // Exclude current booking if updating
    if ($booking_id) {
        $booking_id = (int)$booking_id;
        $query .= " AND booking_id != $booking_id";
    }
    
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) === 0;
}
