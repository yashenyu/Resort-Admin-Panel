<?php
include '../../db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$new_status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';

if (!$room_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

if (!in_array($new_status, ['Available', 'Booked', 'Maintenance'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$active_bookings = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE room_id = $room_id 
    AND status = 'Confirmed'
    AND check_out_date >= CURDATE()
");
$booking_count = mysqli_fetch_assoc($active_bookings)['count'];

$update = mysqli_query($conn, "
    UPDATE rooms 
    SET status = '$new_status'
    WHERE room_id = $room_id
");

if ($update) {
    $log_action = mysqli_real_escape_string($conn, "Changed room #$room_id status to $new_status" . 
                 ($booking_count > 0 ? " (with $booking_count active/upcoming bookings)" : ""));
    mysqli_query($conn, "INSERT INTO audit_logs (action, timestamp) VALUES ('$log_action', NOW())");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . mysqli_error($conn)]);
}
?> 