<?php
include 'db_connect.php'; 
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Bookings</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Your Dark Theme CSS -->
  <link href="css/modern-theme.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

  <style>
    /* Ensure the sidebar remains at a fixed width (adjust as needed) */
    /* .sidebar { width: 250px; }  <-- If you do this in navbar.php or your CSS, that's fine */

    /* Make sure .content starts after the sidebar (250px) and fills the rest */
    .content {
      margin-left: 250px !important; /* match your sidebar’s width */
      width: calc(100% - 250px) !important;
      min-height: 100vh; /* optional, to fill the screen vertically */
    }

    /* Force container-fluid to truly fill horizontally with no extra padding */
    .container-fluid {
      width: 100% !important;
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /* Adjust the heading and filter section spacing to your liking */
    .container-fluid .p-4 {
      padding: 1rem !important; /* reduce default padding */
    }

    /* DataTables spacing adjustments */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
      margin-bottom: 1rem !important;
    }
    .dataTables_wrapper .row {
      margin: 0.5rem 0 !important;
    }

    /* Reduce table wrapper padding if you want the table to stretch fully */
    .table-responsive {
      padding: 0.5rem !important;
    }

    /* Optional: make the table fill 100% of its container */
    table.dataTable {
      width: 100% !important; /* Force the table to use all available space */
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Your left sidebar (includes/navbar.php) -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Main content area -->
  <div class="content">
    <div class="container-fluid p-4">
      <h2 class="mb-4 text-light"><i class="fas fa-calendar-alt"></i> Manage Bookings</h2>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Booking updated successfully.</div>
      <?php endif; ?>

      <!-- Filters Section -->
      <div class="filters mb-4">
        <div class="row g-3">
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
              <input type="date" id="filterStartDate" class="form-control me-2" placeholder="Start Date">
              <input type="date" id="filterEndDate" class="form-control" placeholder="End Date">
            </div>
          </div>

          <!-- Apply Filters Button -->
          <div class="col-md-2 d-flex align-items-end">
            <button id="applyFilters" class="btn btn-primary w-100">
              <i class="fas fa-filter"></i> Apply
            </button>
          </div>

          <!-- Reset Filters Button -->
          <div class="col-md-2 d-flex align-items-end">
            <button id="resetFilters" class="btn btn-secondary w-100">
              <i class="fas fa-redo"></i> Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Bookings Table -->
      <div class="table-responsive">
        <table id="bookingsTable" class="table table-bordered table-hover table-dark align-middle">
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
                'Pending' => 'badge-secondary',
                'Confirmed' => 'badge-primary',
                'Cancelled' => 'badge-danger',
                'Completed' => 'badge-success',
                default => 'badge-light'
              };

              echo "<tr>
                      <td>{$row['booking_id']}</td>
                      <td>{$row['customer_name']}</td>
                      <td>{$row['room_type']} <br><small>#{$row['room_number']}</small></td>
                      <td>{$row['guests']}</td>
                      <td><span class='badge $statusClass'>{$row['status']}</span></td>
                      <td>{$row['check_in_date']}</td>
                      <td>{$row['check_out_date']}</td>
                      <td>₱" . number_format($row['total_price'], 2) . "</td>
                      <td style='white-space: nowrap;'>
                        <a href='edit_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-warning me-1'>
                          <i class='fas fa-edit'></i>
                        </a>
                        <a href='cancel_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-danger me-1'>
                          <i class='fas fa-times'></i>
                        </a>
                        <a href='complete_booking.php?id={$row['booking_id']}' class='btn btn-sm btn-success'>
                          <i class='fas fa-check'></i>
                        </a>
                      </td>
                    </tr>";
            }
            ?>
          </tbody>
        </table>
      </div> <!-- /.table-responsive -->
    </div> <!-- /.container-fluid -->
  </div> <!-- /.content -->
</div> <!-- /.d-flex -->

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize DataTable with autoWidth disabled to allow columns to expand
    const table = $('#bookingsTable').DataTable({
      responsive: true,
      autoWidth: false
    });

    // Apply Filters Button
    $('#applyFilters').on('click', function() {
      const status = $('#filterStatus').val();
      const startDate = $('#filterStartDate').val();
      const endDate = $('#filterEndDate').val();

      $.fn.dataTable.ext.search = [];

      // Custom filtering logic
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const bookingStatus = data[4];  // Status column
        const checkInDate = data[5];    // Check-in column

        // Filter by status
        if (status && bookingStatus !== status) {
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

      table.draw();
    });

    // Reset Filters Button
    $('#resetFilters').on('click', function() {
      $('#filterStatus').val('');
      $('#filterStartDate').val('');
      $('#filterEndDate').val('');

      $.fn.dataTable.ext.search = [];
      table.draw();
    });
  });
</script>
</body>
</html>
