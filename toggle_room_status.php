<?php
include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
  die("Missing room ID.");
}

// Get current status
$query = "SELECT status FROM rooms WHERE room_id = $room_id";
$result = mysqli_query($conn, $query);
$room = mysqli_fetch_assoc($result);

if (!$room) {
  die("Room not found.");
}

$currentStatus = $room['status'];
$newStatus = $currentStatus === 'Available' ? 'Maintenance' : 'Available';

$update = "UPDATE rooms SET status = '$newStatus' WHERE room_id = $room_id";
mysqli_query($conn, $update);


header("Location: rooms.php?updated=1");
exit;
?>
