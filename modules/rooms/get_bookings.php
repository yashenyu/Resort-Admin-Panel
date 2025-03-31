<?php
include '../../db_connect.php';
session_start();

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

// First get the room details
$room_query = "SELECT * FROM rooms WHERE room_id = $room_id";
$room_result = mysqli_query($conn, $room_query);

if (!$room_result || mysqli_num_rows($room_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit;
}

$room_details = mysqli_fetch_assoc($room_result);

// Then get future bookings for this room
$bookings_query = "
    SELECT b.booking_id, b.check_in_date, b.check_out_date,
           CONCAT(u.first_name, ' ', u.last_name) as guest_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.user_id
    WHERE b.room_id = $room_id 
    AND b.status = 'Confirmed'
    AND b.check_out_date >= CURDATE()
    ORDER BY b.check_in_date ASC";

$bookings_result = mysqli_query($conn, $bookings_query);
$bookings = [];

while ($booking = mysqli_fetch_assoc($bookings_result)) {
    $bookings[] = [
        'booking_id' => $booking['booking_id'],
        'check_in_date' => $booking['check_in_date'],
        'check_out_date' => $booking['check_out_date'],
        'guest_name' => $booking['guest_name']
    ];
}

echo json_encode([
    'success' => true,
    'room' => [
        'room_number' => $room_details['room_number'],
        'room_type' => $room_details['room_type'],
        'room_rate' => $room_details['room_rate'],
        'status' => $room_details['status']
    ],
    'bookings' => $bookings
]);
?> 