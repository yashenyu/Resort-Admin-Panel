<?php include 'db_connect.php';  session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #f8f9fa, #e9ecef);
    }
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .card-title {
      font-size: 1.2rem;
      font-weight: bold;
    }
    .card-text {
      font-size: 1.5rem;
    }
    .chart-container {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2 class="mb-4"><i class="fas fa-chart-line"></i> Dashboard</h2>
    <div class="row g-4">

      <?php
      // Total Bookings
      $totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings"))['total'];

      // Active Bookings
      $activeBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Confirmed'"))['total'];

      // Total Revenue
      $totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) AS revenue FROM bookings WHERE status IN ('Confirmed', 'Completed')"))['revenue'];

      // Occupancy Rate
      $totalRooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms"))['total'];
      $occupiedRooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT room_id) AS occupied FROM bookings WHERE status = 'Confirmed'"))['occupied'];
      $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

      // Bookings per Month
      $bookingsPerMonth = mysqli_query($conn, "
          SELECT MONTH(check_in_date) AS month, COUNT(*) AS count
          FROM bookings
          WHERE status = 'Confirmed'
          GROUP BY MONTH(check_in_date)
      ");

      // Initialize all 12 months with 0 bookings
      $months = [
          'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ];
      $counts = array_fill(0, 12, 0);

      // Populate counts with actual data
      while ($row = mysqli_fetch_assoc($bookingsPerMonth)) {
          $monthIndex = $row['month'] - 1; // Convert 1-based month to 0-based index
          $counts[$monthIndex] = $row['count'];
      }

      // Popular Room Types
      $roomTypeData = mysqli_query($conn, "
          SELECT room_type, COUNT(*) AS count
          FROM bookings b
          JOIN rooms r ON b.room_id = r.room_id
          WHERE b.status = 'Confirmed'
          GROUP BY room_type
      ");

      $roomTypes = [];
      $roomCounts = [];
      while ($row = mysqli_fetch_assoc($roomTypeData)) {
          $roomTypes[] = $row['room_type'];
          $roomCounts[] = $row['count'];
      }
      ?>

      <!-- KPI Cards -->
      <div class="col-md-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <h5 class="card-title"><i class="fas fa-calendar-check"></i> Total Bookings</h5>
            <p class="card-text"><?= $totalBookings ?></p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <h5 class="card-title"><i class="fas fa-check-circle"></i> Active Bookings</h5>
            <p class="card-text"><?= $activeBookings ?></p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card text-white bg-info">
          <div class="card-body">
            <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Total Revenue</h5>
            <p class="card-text">₱<?= number_format($totalRevenue, 2) ?></p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card text-white bg-warning">
          <div class="card-body">
            <h5 class="card-title"><i class="fas fa-bed"></i> Occupancy Rate</h5>
            <p class="card-text"><?= $occupancyRate ?>%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row mt-4">
      <div class="col-md-6">
        <div class="chart-container">
          <h5>Bookings per Month</h5>
          <canvas id="bookingsChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-container">
          <h5>Popular Room Types</h5>
          <canvas id="roomTypeChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const bookingsData = {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: '# of Bookings',
            data: <?= json_encode($counts) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)'
        }]
    };

    const roomTypeData = {
        labels: <?= json_encode($roomTypes) ?>,
        datasets: [{
            data: <?= json_encode($roomCounts) ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1']
        }]
    };
</script>
<script src="assets/dashboard-charts.js"></script>
</body>
</html>
