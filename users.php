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
  <title>Manage Users</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Your Custom Dark Theme CSS -->
  <link href="css/modern-theme.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">

  <style>
    /* Position the content to respect your sidebar (assumed 250px width) */
    .content {
      margin-left: 250px !important;
      width: calc(100% - 250px) !important;
      min-height: 100vh;
    }
    /* Make container-fluid fill width and reduce default padding */
    .container-fluid {
      width: 100% !important;
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .container-fluid.p-4 {
      padding: 1rem !important; /* Adjust as needed */
    }
    /* DataTables spacing adjustments */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
      margin-top: 1.5rem !important;
      margin-bottom: 1.5rem !important;
    }
    /* Wrap the table in .table-responsive for horizontal scroll on smaller screens */
    .table-responsive {
      padding: 0.5rem !important; /* Adjust as needed */
    }
    /* Ensure table uses full width */
    table.dataTable {
      width: 100% !important;
    }
    /* Filters layout: add gap between rows */
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
  <!-- Sidebar (navbar.php should define a fixed 250px width) -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Main Content Area -->
  <div class="content">
    <div class="container-fluid p-4">
      <h2 class="mb-4 text-light"><i class="fas fa-users"></i> Registered Users</h2>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">User updated successfully.</div>
      <?php endif; ?>

      <!-- Filters Section -->
      <div class="filters mb-4">
        <div class="row g-3">
          <!-- Gender Filter -->
          <div class="col-md-3">
            <label for="filterGender" class="form-label">Gender</label>
            <select id="filterGender" class="form-select">
              <option value="">All</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <!-- Registration Date Range Filter -->
          <div class="col-md-4">
            <label for="filterDateRange" class="form-label">Registration Date Range</label>
            <div class="d-flex">
              <input type="date" id="filterStartDate" class="form-control me-2">
              <input type="date" id="filterEndDate" class="form-control">
            </div>
          </div>

          <!-- Search by Name or Email -->
          <div class="col-md-3">
            <label for="filterSearch" class="form-label">Search (Name/Email)</label>
            <input type="text" id="filterSearch" class="form-control" placeholder="Enter name or email">
          </div>

          <!-- Apply Filters Button -->
          <div class="col-md-2 d-flex align-items-end">
            <button id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="table-responsive">
        <table id="usersTable" class="table table-bordered table-hover table-dark align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Gender</th>
              <th>Contact</th>
              <th>Registered On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $query = "SELECT * FROM users ORDER BY created_at DESC";
            $result = mysqli_query($conn, $query);

            while ($user = mysqli_fetch_assoc($result)) {
              $fullName = $user['first_name'] . ' ' . $user['last_name'];
              $created = date("M d, Y", strtotime($user['created_at']));

              echo "<tr>
                      <td>{$user['user_id']}</td>
                      <td>$fullName</td>
                      <td>{$user['email']}</td>
                      <td>{$user['gender']}</td>
                      <td>{$user['contact_number']}</td>
                      <td>$created</td>
                      <td style='white-space: nowrap;'>
                        <a href='edit_user.php?id={$user['user_id']}' class='btn btn-sm btn-warning me-1'>
                          <i class='fas fa-edit'></i>
                        </a>
                        <a href='deactivate_user.php?id={$user['user_id']}' class='btn btn-sm btn-danger'>
                          Deactivate
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
    // Initialize DataTable
    const table = $('#usersTable').DataTable({
      responsive: true,
      autoWidth: false
    });

    // Apply Filters Button
    $('#applyFilters').on('click', function() {
      const gender = $('#filterGender').val();
      const startDate = $('#filterStartDate').val();
      const endDate = $('#filterEndDate').val();
      const search = $('#filterSearch').val().toLowerCase();

      // Clear any previous filters
      $.fn.dataTable.ext.search = [];

      // Custom filtering logic
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const userGender = data[3]; // Gender column
        const registeredDate = data[5]; // Registered On column
        const nameOrEmail = (data[1] + ' ' + data[2]).toLowerCase(); // Name + Email

        // Filter by gender
        if (gender && userGender !== gender) {
          return false;
        }

        // Filter by registration date range
        if (startDate || endDate) {
          const registered = new Date(registeredDate);
          const start = startDate ? new Date(startDate) : null;
          const end = endDate ? new Date(endDate) : null;

          if ((start && registered < start) || (end && registered > end)) {
            return false;
          }
        }

        // Filter by search (name or email)
        if (search && !nameOrEmail.includes(search)) {
          return false;
        }

        return true;
      });

      // Redraw the table with the new filters
      table.draw();
    });
  });
</script>
</body>
</html>
