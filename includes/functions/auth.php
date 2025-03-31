<?php
/**
 * Authentication Functions
 * 
 * Contains functions for user authentication and authorization
 */

/**
 * Check if user is authenticated
 * 
 * @return bool Whether the user is logged in
 */
function is_authenticated() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

/**
 * Authenticate user
 * 
 * @param string $username Username to check
 * @param string $password Password to verify
 * @return bool Whether authentication was successful
 */
function authenticate($username, $password) {
    global $conn;
    
    $query = "SELECT * FROM admin WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $admin['password_hash'])) {
            // Set session variables
            $_SESSION['admin'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Log successful login
            log_activity($admin['admin_id'], 'Logged in');
            
            return true;
        }
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logout() {
    // Log the logout if user is authenticated
    if (is_authenticated() && isset($_SESSION['admin_id'])) {
        log_activity($_SESSION['admin_id'], 'Logged out');
    }
    
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Ensure session data is completely removed
    $_SESSION = array();
    
    // If using cookies, clear them too
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

/**
 * Log user activity
 * 
 * @param int $user_id User ID
 * @param string $action Action performed
 */
function log_activity($user_id, $action) {
    global $conn;
    
    $user_id = (int) $user_id;
    $action = mysqli_real_escape_string($conn, $action);
    $query = "INSERT INTO logs (user_id, action) VALUES ($user_id, '$action')";
    
    mysqli_query($conn, $query);
}
