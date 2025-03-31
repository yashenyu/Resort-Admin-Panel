<?php
/**
 * Logs Module Functions
 * 
 * Contains all functions related to system logging and audit trails
 */

/**
 * Get all logs with optional filtering
 * 
 * @param array $filters Optional filters (user_id, action, date_from, date_to)
 * @param int $limit Limit results, 0 for no limit
 * @param int $offset Offset for pagination
 * @return array Array of logs
 */
function get_logs($filters = [], $limit = 0, $offset = 0) {
    global $conn;
    
    // Build query
    $query = "
        SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE 1=1
    ";
    
    // Add filters
    if (!empty($filters)) {
        if (isset($filters['user_id']) && $filters['user_id']) {
            $user_id = (int)$filters['user_id'];
            $query .= " AND l.user_id = $user_id";
        }
        
        if (isset($filters['action']) && $filters['action']) {
            $action = sanitize($filters['action']);
            $query .= " AND l.action LIKE '%$action%'";
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $date_from = sanitize($filters['date_from']);
            $query .= " AND DATE(l.timestamp) >= '$date_from'";
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $date_to = sanitize($filters['date_to']);
            $query .= " AND DATE(l.timestamp) <= '$date_to'";
        }
    }
    
    // Order by
    $query .= " ORDER BY l.timestamp DESC";
    
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
    
    // Fetch all logs
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * Count total logs with given filters
 * 
 * @param array $filters Optional filters
 * @return int Total number of logs
 */
function count_logs($filters = []) {
    global $conn;
    
    // Build query
    $query = "
        SELECT COUNT(*) as total
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE 1=1
    ";
    
    // Add filters
    if (!empty($filters)) {
        if (isset($filters['user_id']) && $filters['user_id']) {
            $user_id = (int)$filters['user_id'];
            $query .= " AND l.user_id = $user_id";
        }
        
        if (isset($filters['action']) && $filters['action']) {
            $action = sanitize($filters['action']);
            $query .= " AND l.action LIKE '%$action%'";
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $date_from = sanitize($filters['date_from']);
            $query .= " AND DATE(l.timestamp) >= '$date_from'";
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $date_to = sanitize($filters['date_to']);
            $query .= " AND DATE(l.timestamp) <= '$date_to'";
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
 * Add a new log entry
 * 
 * @param int $user_id User ID
 * @param string $action Action performed
 * @return bool Whether log was added successfully
 */
function add_log($user_id, $action) {
    global $conn;
    
    $user_id = (int)$user_id;
    $action = sanitize($action);
    
    $query = "
        INSERT INTO logs (user_id, action)
        VALUES ($user_id, '$action')
    ";
    
    return mysqli_query($conn, $query) ? true : false;
}

/**
 * Get unique actions for filtering
 * 
 * @return array Array of unique actions
 */
function get_unique_actions() {
    global $conn;
    
    $query = "
        SELECT DISTINCT action
        FROM logs
        ORDER BY action
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $actions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $actions[] = $row['action'];
    }
    
    return $actions;
}

/**
 * Get users who have logged actions
 * 
 * @return array Array of users with their IDs
 */
function get_log_users() {
    global $conn;
    
    $query = "
        SELECT DISTINCT l.user_id, CONCAT(u.first_name, ' ', u.last_name) as name
        FROM logs l
        JOIN users u ON l.user_id = u.user_id
        ORDER BY name
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = [
            'id' => $row['user_id'],
            'name' => $row['name']
        ];
    }
    
    return $users;
}

/**
 * Get recent activity logs
 * 
 * @param int $limit Number of logs to retrieve
 * @return array Recent logs
 */
function get_recent_activity($limit = 10) {
    global $conn;
    
    $limit = (int)$limit;
    
    $query = "
        SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        ORDER BY l.timestamp DESC
        LIMIT $limit
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}
