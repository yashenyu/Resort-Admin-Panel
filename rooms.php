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
  <title>Manage Rooms</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Your Custom Dark Theme CSS -->
  <link href="css/modern-theme.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">

  <style>
    /* Ensure the main content is positioned to the right of your fixed sidebar (250px) */
    .content {
      margin-left: 250px !important;
      width: calc(100% - 250px) !important;
      min-height: 100vh;
    }

    /* Make container-fluid truly full width and reduce its padding */
    .container-fluid {
      width: 100% !important;
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .container-fluid.p-4 {
      padding: 1rem !important; /* Adjust as needed */
    }

    /* DataTables adjustments */
    table.dataTable {
      width: 100% !important;
    }
    .table-responsive {
      padding: 0.5rem !important; /* Adjust as needed */
    }

    /* Increase vertical spacing around the DataTables search bar and "Show entries" dropdown */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
      margin-top: 1.5rem !important;
      margin-bottom: 1.5rem !important;
    }

    /* Filters layout */
    .filters .row {
      row-gap: 1rem; /* Gap between filter rows */
    }
    .filters label {
      margin-bottom: 0.5rem;
      display: block;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar (ensure your navbar.php outputs a fixed-width sidebar) -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Main Content Area -->
  <div class="content">
    <div class="container-fluid p-4">
      <h2 class="mb-4 text-light"><i class="fas fa-bed"></i> Manage Rooms</h2>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Room updated successfully.</div>
      <?php endif; ?>

      <!-- Filters Section -->
      <div class="filters mb-4">
        <div class="row g-3">
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

          <!-- Status Filter -->
          <div class="col-md-3">
            <label for="filterStatus" class="form-label">Status</label>
            <select id="filterStatus" class="form-select">
              <option value="">All</option>
              <option value="Available">Available</option>
              <option value="Booked">Booked</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <!-- Rate Range Filter -->
          <div class="col-md-4">
            <label for="filterRateRange" class="form-label">Rate Range (₱)</label>
            <div class="d-flex">
              <input type="number" id="filterMinRate" class="form-control me-2" placeholder="Min">
              <input type="number" id="filterMaxRate" class="form-control" placeholder="Max">
            </div>
          </div>

          <!-- Apply Filters Button -->
          <div class="col-md-2 d-flex align-items-end">
            <button id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
          </div>
        </div>
      </div>

      <!-- Rooms Table -->
      <div class="table-responsive">
        <table id="roomsTable" class="table table-bordered table-hover table-dark align-middle">
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
                'Available' => 'badge-success',
                'Booked' => 'badge-primary',
                'Maintenance' => 'badge-warning',
                default => 'badge-secondary'
              };
              echo "<tr>
                      <td>{$room['room_number']}</td>
                      <td>{$room['room_type']}</td>
                      <td><span class='badge $statusClass'>{$room['status']}</span></td>
                      <td>" . number_format($room['room_rate'], 2) . "</td>
                      <td style='white-space: nowrap;'>
                        <a href='edit_room.php?id={$room['room_id']}' class='btn btn-sm btn-warning'>
                          <i class='fas fa-edit'></i>
                        </a>
                        <a href='toggle_room_status.php?id={$room['room_id']}' class='btn btn-sm btn-secondary'>
                          Toggle Status
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
<script>
  $(document).ready(function() {
    const table = $('#roomsTable').DataTable({
      responsive: true,
      autoWidth: false
    });

    // Apply Filters Button
    $('#applyFilters').on('click', function() {
      const roomType = $('#filterRoomType').val();
      const status = $('#filterStatus').val();
      const minRate = parseFloat($('#filterMinRate').val());
      const maxRate = parseFloat($('#filterMaxRate').val());

      $.fn.dataTable.ext.search = [];
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const roomTypeData = data[1]; // Room Type column
        const statusData = data[2];   // Status column
        const rateData = parseFloat(data[3].replace(/,/g, '')); // Rate column

        // Filter by room type
        if (roomType && roomTypeData !== roomType) {
          return false;
        }
        // Filter by status
        if (status && statusData !== status) {
          return false;
        }
        // Filter by rate range
        if (!isNaN(minRate) && rateData < minRate) {
          return false;
        }
        if (!isNaN(maxRate) && rateData > maxRate) {
          return false;
        }
        return true;
      });

      table.draw();
    });
  });
</script>
</body>
</html>
