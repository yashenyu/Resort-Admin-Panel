<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
} ?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Rooms</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Room Management</h2>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">Room updated successfully.</div>
    <?php endif; ?>

    <!-- Filters Section -->
    <div class="filters mb-4">
      <div class="row">
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
    <table id="roomsTable" class="table table-bordered table-hover">
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

<script>
  $(document).ready(function() {
    // Initialize DataTable
    const table = $('#roomsTable').DataTable();

    // Apply Filters Button
    $('#applyFilters').on('click', function() {
      const roomType = $('#filterRoomType').val();
      const status = $('#filterStatus').val();
      const minRate = parseFloat($('#filterMinRate').val());
      const maxRate = parseFloat($('#filterMaxRate').val());

      // Custom filtering logic
      $.fn.dataTable.ext.search = [];
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const roomTypeData = data[1]; // Room Type column
        const statusData = data[2]; // Status column
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

      // Redraw the table with the new filters
      table.draw();
    });

    // Reset Filters Button (Optional)
    $('#resetFilters').on('click', function() {
      $('#filterRoomType').val('');
      $('#filterStatus').val('');
      $('#filterMinRate').val('');
      $('#filterMaxRate').val('');
      $.fn.dataTable.ext.search = []; // Clear all filters
      table.draw(); // Redraw the table
    });
  });
</script>
</body>
</html>
