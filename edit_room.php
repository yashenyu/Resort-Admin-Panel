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

// Get room info
$query = "SELECT * FROM rooms WHERE room_id = $room_id";
$result = mysqli_query($conn, $query);
$room = mysqli_fetch_assoc($result);

if (!$room) {
  die("Room not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $room_number = $_POST['room_number'];
  $room_type = $_POST['room_type'];
  $status = $_POST['status'];
  $rate = $_POST['room_rate'];

  $update = "
    UPDATE rooms SET
      room_number = '$room_number',
      room_type = '$room_type',
      status = '$status',
      room_rate = '$rate'
    WHERE room_id = $room_id
  ";

  if (mysqli_query($conn, $update)) {
    header("Location: rooms.php?updated=1");
    exit;
  } else {
    echo "Error: " . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Room</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Edit Room #<?= $room['room_number'] ?></h2>
  <form method="POST" class="mt-4">

    <div class="mb-3">
      <label class="form-label">Room Number</label>
      <input type="text" name="room_number" class="form-control" value="<?= $room['room_number'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Room Type</label>
      <select name="room_type" class="form-select">
        <?php
        $types = ['Ocean View Suite', 'Garden View Room', 'Family Room', 'Deluxe Suite'];
        foreach ($types as $type) {
          $selected = $room['room_type'] === $type ? 'selected' : '';
          echo "<option value='$type' $selected>$type</option>";
        }
        ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php
        $statuses = ['Available', 'Booked', 'Maintenance'];
        foreach ($statuses as $status) {
          $selected = $room['status'] === $status ? 'selected' : '';
          echo "<option value='$status' $selected>$status</option>";
        }
        ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Room Rate (₱)</label>
      <input type="number" step="0.01" name="room_rate" class="form-control" value="<?= $room['room_rate'] ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="rooms.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
