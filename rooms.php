<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Rooms</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Room Management</h2>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">Room updated successfully.</div>
    <?php endif; ?>

    <table class="table table-bordered mt-4">
      <thead class="table-light">
        <tr>
          <th>Room #</th>
          <th>Type</th>
          <th>Status</th>
          <th>Rate (₱)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM rooms ORDER BY room_number ASC";
        $result = mysqli_query($conn, $query);

        while ($room = mysqli_fetch_assoc($result)) {
          $statusClass = match($room['status']) {
            'Available' => 'success',
            'Booked' => 'primary',
            'Maintenance' => 'warning',
            default => 'secondary'
          };

          echo "<tr>
                  <td>{$room['room_number']}</td>
                  <td>{$room['room_type']}</td>
                  <td><span class='badge bg-$statusClass'>{$room['status']}</span></td>
                  <td>" . number_format($room['room_rate'], 2) . "</td>
                  <td>
                    <a href='edit_room.php?id={$room['room_id']}' class='btn btn-sm btn-warning'>Edit</a>
                    <a href='toggle_room_status.php?id={$room['room_id']}' class='btn btn-sm btn-secondary'>Toggle Status</a>
                  </td>
                </tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
