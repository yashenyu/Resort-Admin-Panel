<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Bookings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    .badge-secondary { background-color: #6c757d; }
    .badge-primary { background-color: #0d6efd; }
    .badge-danger { background-color: #dc3545; }
    .badge-success { background-color: #198754; }
    .table-hover tbody tr:hover {
      background-color: #f8f9fa;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2 class="mb-4"><i class="fas fa-calendar-alt"></i> Bookings</h2>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Booking updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-danger">Booking has been cancelled.</div>
    <?php endif; ?>

    <?php if (isset($_GET['completed'])): ?>
        <div class="alert alert-success">Booking marked as completed.</div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
      <thead class="table-light">
        <tr>
          <th>Booking ID</th>
          <th>Name</th>
          <th>Room</th>
          <th>Guests</th>
          <th>Status</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Total</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>

      <?php
      $query = "
        SELECT b.*, r.room_type, r.room_number,
               CONCAT(u.first_name, ' ', u.last_name) AS customer_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN users u ON b.user_id = u.user_id
        ORDER BY b.created_at DESC
      ";

      $result = mysqli_query($conn, $query);

      while ($row = mysqli_fetch_assoc($result)) {
        $statusClass = match($row['status']) {
          'Pending' => 'secondary',
          'Confirmed' => 'primary',
          'Cancelled' => 'danger',
          'Completed' => 'success',
          default => 'light'
        };
        echo "<tr>
                <td>{$row['booking_id']}</td>
                <td>{$row['customer_name']}</td>
                <td>{$row['room_type']} <br><small>#{$row['room_number']}</small></td>
                <td>{$row['guests']}</td>
                <td><span class='badge badge-{$statusClass}'>{$row['status']}</span></td>
                <td>{$row['check_in_date']}</td>
                <td>{$row['check_out_date']}</td>
                <td>₱" . number_format($row['total_price'], 2) . "</td>
                <td>
                  <a href='edit_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                  <a href='cancel_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-danger'><i class='fas fa-times'></i></a>
                  <a href='complete_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-success'><i class='fas fa-check'></i></a>
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
