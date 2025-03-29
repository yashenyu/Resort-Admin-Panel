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
  <title>Audit Logs</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Your Custom Dark Theme CSS -->
  <link href="css/modern-theme.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  
  <style>
    /* Ensure the main content starts after your sidebar (assumed 250px wide) */
    .content {
      margin-left: 250px !important;
      width: calc(100% - 250px) !important;
      min-height: 100vh;
    }
    /* Make container-fluid use full width with reduced padding */
    .container-fluid {
      width: 100% !important;
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .container-fluid.p-4 {
      padding: 1rem !important;
    }
    /* Wrap table in a responsive container */
    .table-responsive {
      padding: 0.5rem !important;
    }
    /* Force the table to span 100% of the container */
    table.dataTable {
      width: 100% !important;
    }
    /* Increase vertical spacing for DataTables search and length elements */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
      margin-top: 1.5rem !important;
      margin-bottom: 1.5rem !important;
    }
    /* Add gap between filter rows */
    .filters .row {
      row-gap: 1rem;
    }
    .filters label {
      margin-bottom: 0.5rem;
      display: block;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar (ensure your navbar.php outputs a sidebar of fixed width) -->
  <?php include 'includes/navbar.php'; ?>
  
  <!-- Main Content Area -->
  <div class="content">
    <div class="container-fluid p-4">
      <h2 class="mb-4 text-light"><i class="fas fa-file-alt"></i> Audit Logs</h2>
      
      <!-- Filters Section -->
      <div class="filters mb-4">
        <div class="row g-3">
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
      <div class="table-responsive">
        <table id="logsTable" class="table table-bordered table-hover table-dark align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Action</th>
              <th>Date &amp; Time</th>
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
      </div> <!-- /.table-responsive -->
      
    </div> <!-- /.container-fluid -->
  </div> <!-- /.content -->
</div> <!-- /.d-flex -->

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function() {
    // Initialize DataTable
    const table = $('#logsTable').DataTable({
      responsive: true,
      autoWidth: false
    });

    // Apply Filters Button: custom filtering logic
    $('#applyFilters').on('click', function() {
      const user = $('#filterUser').val();
      const action = $('#filterAction').val();
      const startDate = $('#filterStartDate').val();
      const endDate = $('#filterEndDate').val();

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
      table.draw();
    });
  });
</script>
</body>
</html>
