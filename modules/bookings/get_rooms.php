<?php
include '../../db_connect.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['type'])) {
    echo json_encode(['success' => false, 'message' => 'Room type is required']);
    exit;
}

$room_type = mysqli_real_escape_string($conn, $_GET['type']);

$query_string = "
    SELECT room_id, room_number, room_type
    FROM rooms 
    WHERE room_type = '$room_type'
    ORDER BY room_number
";
error_log("Executing query: " . $query_string);

$query = mysqli_query($conn, $query_string);

if (!$query) {
    error_log("MySQL Error: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

$rooms = [];
while ($room = mysqli_fetch_assoc($query)) {
    $rooms[] = [
        'room_id' => $room['room_id'],
        'room_number' => $room['room_number']
    ];
}

echo json_encode(['success' => true, 'rooms' => $rooms]);
?> 