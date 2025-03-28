<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
} ?>
<!DOCTYPE html>
<html>
<head>
  <title>Audit Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Audit Logs</h2>

    <!-- Filters Section -->
    <div class="filters mb-4">
      <div class="row">
        <!-- User Filter -->
        <div class="col-md-3">
          <label for="filterUser" class="form-label">User</label>
          <select id="filterUser" class="form-select">
            <option value="">All</option>
            <?php
            $users = mysqli_query($conn, "SELECT DISTINCT CONCAT(first_name, ' ', last_name) AS user_name FROM users");
            while ($user = mysqli_fetch_assoc($users)) {
              echo "<option value='{$user['user_name']}'>{$user['user_name']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Action Filter -->
        <div class="col-md-3">
          <label for="filterAction" class="form-label">Action</label>
          <select id="filterAction" class="form-select">
            <option value="">All</option>
            <?php
            $actions = mysqli_query($conn, "SELECT DISTINCT action FROM audit_logs");
            while ($action = mysqli_fetch_assoc($actions)) {
              echo "<option value='{$action['action']}'>{$action['action']}</option>";
            }
            ?>
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

        <!-- Apply Filters Button -->
        <div class="col-md-2 d-flex align-items-end">
          <button id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <table id="logsTable" class="table table-striped table-bordered mt-4">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Action</th>
          <th>Date & Time</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "
          SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
          FROM audit_logs a
          JOIN users u ON a.user_id = u.user_id
          ORDER BY a.timestamp DESC
          LIMIT 100
        ";

        $result = mysqli_query($conn, $query);
        $count = 1;

        while ($row = mysqli_fetch_assoc($result)) {
          echo "<tr>
                  <td>{$count}</td>
                  <td>{$row['user_name']}</td>
                  <td>{$row['action']}</td>
                  <td>" . date("F j, Y - g:i A", strtotime($row['timestamp'])) . "</td>
                </tr>";
          $count++;
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Initialize DataTable
    const table = $('#logsTable').DataTable();

    // Apply Filters Button
    $('#applyFilters').on('click', function() {
      const user = $('#filterUser').val();
      const action = $('#filterAction').val();
      const startDate = $('#filterStartDate').val();
      const endDate = $('#filterEndDate').val();

      // Custom filtering logic
      $.fn.dataTable.ext.search = [];
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const logUser = data[1]; // User column
        const logAction = data[2]; // Action column
        const logDate = data[3]; // Date & Time column

        // Filter by user
        if (user && logUser !== user) {
          return false;
        }

        // Filter by action
        if (action && logAction !== action) {
          return false;
        }

        // Filter by date range
        if (startDate || endDate) {
          const logTimestamp = new Date(logDate);
          const start = startDate ? new Date(startDate) : null;
          const end = endDate ? new Date(endDate) : null;

          if ((start && logTimestamp < start) || (end && logTimestamp > end)) {
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
      $('#filterUser').val('');
      $('#filterAction').val('');
      $('#filterStartDate').val('');
      $('#filterEndDate').val('');
      $.fn.dataTable.ext.search = []; // Clear all filters
      table.draw(); // Redraw the table
    });
  });
</script>
</body>
</html>
