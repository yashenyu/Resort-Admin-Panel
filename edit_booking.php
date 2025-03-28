<?php
include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("No booking ID provided.");
}

// Fetch booking
$query = "
  SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, r.room_number
  FROM bookings b
  JOIN users u ON b.user_id = u.user_id
  JOIN rooms r ON b.room_id = r.room_id
  WHERE b.booking_id = $booking_id
";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    die("Booking not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];
    $guests = $_POST['guests'];

    $update = "
      UPDATE bookings
      SET status = '$status',
          check_in_date = '$check_in',
          check_out_date = '$check_out',
          guests = $guests
      WHERE booking_id = $booking_id
    ";

    if (mysqli_query($conn, $update)) {
        mysqli_query($conn, "
          INSERT INTO logs (user_id, action)
          VALUES ({$booking['user_id']}, 'Edited booking #{$booking_id}')
        ");

        header("Location: bookings.php?updated=1");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Edit Booking #<?= $booking['booking_id'] ?> (<?= $booking['customer_name'] ?>)</h2>
  <form method="POST" class="mt-4">

    <div class="mb-3">
      <label class="form-label">Room</label>
      <input type="text" class="form-control" value="#<?= $booking['room_number'] ?>" disabled>
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php
        $statuses = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
        foreach ($statuses as $status) {
          $selected = $booking['status'] === $status ? 'selected' : '';
          echo "<option value='$status' $selected>$status</option>";
        }
        ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Check-in Date</label>
      <input type="date" name="check_in_date" class="form-control" value="<?= $booking['check_in_date'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Check-out Date</label>
      <input type="date" name="check_out_date" class="form-control" value="<?= $booking['check_out_date'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Guests</label>
      <input type="number" name="guests" class="form-control" value="<?= $booking['guests'] ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="bookings.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
