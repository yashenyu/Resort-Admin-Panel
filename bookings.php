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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
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

    <div class="filters mb-4">
      <div class="row">
        <!-- Status Filter -->
        <div class="col-md-3">
          <label for="filterStatus" class="form-label">Status</label>
          <select id="filterStatus" class="form-select">
            <option value="">All</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Completed">Completed</option>
          </select>
        </div>

        <!-- Date Range Filter -->
        <div class="col-md-4">
          <label for="filterDateRange" class="form-label">Date Range</label>
          <div class="d-flex">
            <input type="date" id="filterStartDate" class="form-control me-2">
            <input type="date" id="filterEndDate" class="form-control">
          </div>
        </div>

        <!-- Room Type Filter -->
        <div class="col-md-3">
          <label for="filterRoomType" class="form-label">Room Type</label>
          <select id="filterRoomType" class="form-select">
            <option value="">All</option>
            <?php
            $roomTypes = mysqli_query($conn, "SELECT DISTINCT room_type FROM rooms");
            while ($room = mysqli_fetch_assoc($roomTypes)) {
              echo "<option value='{$room['room_type']}'>{$room['room_type']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Apply Filters Button -->
        <div class="col-md-2 d-flex align-items-end">
          <button id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
        </div>
      </div>
    </div>

    <table id="bookingsTable" class="table table-bordered table-hover">
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

    <script>
      $(document).ready(function() {
        // Initialize DataTable
        const table = $('#bookingsTable').DataTable();

        // Apply Filters Button
        $('#applyFilters').on('click', function() {
          const status = $('#filterStatus').val();
          const roomType = $('#filterRoomType').val();
          const startDate = $('#filterStartDate').val();
          const endDate = $('#filterEndDate').val();

          // Custom filtering logic
          $.fn.dataTable.ext.search = [];
          $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const bookingStatus = data[4]; // Status column
            const bookingRoomType = data[2]; // Room column
            const checkInDate = data[5]; // Check-in column

            // Filter by status
            if (status && bookingStatus !== status) {
              return false;
            }

            // Filter by room type
            if (roomType && !bookingRoomType.includes(roomType)) {
              return false;
            }

            // Filter by date range
            if (startDate || endDate) {
              const checkIn = new Date(checkInDate);
              const start = startDate ? new Date(startDate) : null;
              const end = endDate ? new Date(endDate) : null;

              if ((start && checkIn < start) || (end && checkIn > end)) {
                return false;
              }
            }

            return true;
          });

          // Redraw the table with the new filters
          table.draw();
        });

        // Reset Filters Button (Optional)
        $('#resetFilters').on('click', function() {
          $('#filterStatus').val('');
          $('#filterRoomType').val('');
          $('#filterStartDate').val('');
          $('#filterEndDate').val('');
          $.fn.dataTable.ext.search = []; // Clear all filters
          table.draw(); // Redraw the table
        });
      });
    </script>
  </div>
</div>
</body>
</html>
