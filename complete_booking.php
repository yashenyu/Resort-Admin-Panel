<?php
include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("Missing booking ID.");
}

// Update status to Completed
$update = "UPDATE bookings SET status = 'Completed' WHERE booking_id = $booking_id";
mysqli_query($conn, $update);

$user_q = mysqli_query($conn, "SELECT user_id FROM bookings WHERE booking_id = $booking_id");
$user_id = mysqli_fetch_assoc($user_q)['user_id'];

mysqli_query($conn, "
  INSERT INTO audit_logs (user_id, action)
  VALUES ($user_id, 'Marked booking #$booking_id as Completed')
");

header("Location: bookings.php?completed=1");
exit;
?>
