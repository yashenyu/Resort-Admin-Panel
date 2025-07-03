<?php
/**
 * Users Module Functions
 * 
 * Contains all functions related to user operations
 */

/**
 * Get all users with optional filtering
 * 
 * @param array $filters Optional filters
 * @param int $limit Limit results, 0 for no limit
 * @param int $offset Offset for pagination
 * @return array Array of users
 */
function get_users($filters = [], $limit = 0, $offset = 0) {
    global $conn;
    
    $query = "
        SELECT u.*,
               (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) AS total_bookings
        FROM users u
        WHERE 1=1
    ";
    
    if (!empty($filters)) {
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                u.email LIKE '%$search%' OR
                u.phone LIKE '%$search%'
            )";
        }
    }
    
    $query .= " ORDER BY u.last_name, u.first_name";
    
    if ($limit > 0) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Count total users with given filters
 * 
 * @param array $filters Optional filters
 * @return int Total number of users
 */
function count_users($filters = []) {
    global $conn;
    
    $query = "
        SELECT COUNT(*) as total
        FROM users u
        WHERE 1=1
    ";
    
    if (!empty($filters)) {
        if (isset($filters['search']) && $filters['search']) {
            $search = sanitize($filters['search']);
            $query .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                u.email LIKE '%$search%' OR
                u.phone LIKE '%$search%'
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
 * Get a single user by ID
 * 
 * @param int $user_id User ID
 * @return array|null User data or null if not found
 */
function get_user($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    $query = "
        SELECT u.*,
               (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) AS total_bookings
        FROM users u
        WHERE u.user_id = $user_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

/**
 * Get user bookings
 * 
 * @param int $user_id User ID
 * @return array Array of user bookings
 */
function get_user_bookings($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    $query = "
        SELECT b.*, r.room_type, r.room_number
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = $user_id
        ORDER BY b.created_at DESC
    ";
    
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
 * Update user details
 * 
 * @param int $user_id User ID
 * @param array $data User data
 * @return bool Whether update was successful
 */
function update_user($user_id, $data) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    $first_name = sanitize($data['first_name']);
    $last_name = sanitize($data['last_name']);
    $email = sanitize($data['email']);
    $phone = sanitize($data['phone']);
    
    $query = "
        SELECT user_id FROM users
        WHERE email = '$email' AND user_id != $user_id
    ";
    
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        return false;
    }
    
    $query = "
        UPDATE users
        SET first_name = '$first_name',
            last_name = '$last_name',
            email = '$email',
            phone = '$phone'
        WHERE user_id = $user_id
    ";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['admin_id'], "Updated user #$user_id details");
        return true;
    }
    
    return false;
}

/**
 * Add a new user
 * 
 * @param array $data User data
 * @return int|bool New user ID or false on failure
 */
function add_user($data) {
    global $conn;
    
    $first_name = sanitize($data['first_name']);
    $last_name = sanitize($data['last_name']);
    $email = sanitize($data['email']);
    $phone = sanitize($data['phone']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $query = "
        SELECT user_id FROM users
        WHERE email = '$email'
    ";
    
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        return false;
    }
    
    $query = "
        INSERT INTO users (first_name, last_name, email, phone, password_hash)
        VALUES ('$first_name', '$last_name', '$email', '$phone', '$password')
    ";
    
    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);
        log_activity($_SESSION['admin_id'], "Added new user: $first_name $last_name");
        return $user_id;
    }
    
    return false;
}

/**
 * Get user statistics
 * 
 * @return array User statistics
 */
function get_user_stats() {
    global $conn;
    
    $stats = [
        'total_users' => 0,
        'new_users_month' => 0,
        'active_users' => 0,
    ];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
    if ($result) {
        $stats['total_users'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    $current_month = date('Y-m-01');
    $result = mysqli_query($conn, "
        SELECT COUNT(*) AS total 
        FROM users 
        WHERE created_at >= '$current_month'
    ");
    if ($result) {
        $stats['new_users_month'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    $result = mysqli_query($conn, "
        SELECT COUNT(DISTINCT user_id) AS total 
        FROM bookings 
        WHERE status = 'Confirmed'
    ");
    if ($result) {
        $stats['active_users'] = (int)mysqli_fetch_assoc($result)['total'];
    }
    
    return $stats;
}
