<?php
require_once '../../db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]));
}

$action = $_POST['action'] ?? '';
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if (!$user_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]));
}

switch ($action) {
    case 'delete':
        // Check if user has any bookings
        $checkBookings = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id");
        $bookingsCount = mysqli_fetch_assoc($checkBookings)['count'];
        
        if ($bookingsCount > 0) {
            die(json_encode([
                'success' => false,
                'message' => 'Cannot delete user with existing bookings'
            ]));
        }
        
        // Delete user
        $deleteQuery = "DELETE FROM users WHERE user_id = $user_id";
        if (mysqli_query($conn, $deleteQuery)) {
            // Log the action
            $admin_id = $_SESSION['admin']['admin_id'];
            $log_query = "INSERT INTO audit_logs (admin_id, action, details) VALUES (
                $admin_id,
                'DELETE_USER',
                'Deleted user ID: $user_id'
            )";
            mysqli_query($conn, $log_query);
            
            die(json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]));
        } else {
            die(json_encode([
                'success' => false,
                'message' => 'Failed to delete user: ' . mysqli_error($conn)
            ]));
        }
        break;
        
    default:
        die(json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]));
} 