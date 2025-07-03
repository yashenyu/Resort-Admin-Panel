<?php
include '../../db_connect.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (mysqli_connect_errno()) {
    error_log("Failed to connect to MySQL: " . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$booking_id = intval($_GET['id']);

$query_string = "
    SELECT 
        b.*,
        r.room_type,
        r.room_number
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = $booking_id
";
error_log("Executing query: " . $query_string);

$query = mysqli_query($conn, $query_string);

if (!$query) {
    error_log("MySQL Error: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

if ($booking = mysqli_fetch_assoc($query)) {
    echo json_encode([
        'success' => true,
        'booking' => [
            'booking_id' => $booking['booking_id'],
            'check_in_date' => $booking['check_in_date'],
            'check_out_date' => $booking['check_out_date'],
            'room_id' => $booking['room_id'],
            'room_type' => $booking['room_type'],
            'room_number' => $booking['room_number'],
            'notes' => isset($booking['notes']) ? $booking['notes'] : ''
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?> 