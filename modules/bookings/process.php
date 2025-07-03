<?php
include '../../db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$new_status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';

if (!$booking_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

if (!in_array($new_status, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$update = mysqli_query($conn, "
    UPDATE bookings 
    SET status = '$new_status',
        updated_at = NOW()
    WHERE booking_id = $booking_id
");

if ($update) {
    $log_action = mysqli_real_escape_string($conn, "Changed booking #$booking_id status to $new_status");
    mysqli_query($conn, "INSERT INTO audit_logs (action, timestamp) VALUES ('$log_action', NOW())");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . mysqli_error($conn)]);
}
?> 