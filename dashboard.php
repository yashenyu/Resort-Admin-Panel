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
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="css/modern-theme.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="content">
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

        // Bookings per Month for the current year
        $currentYear = date('Y');
        $bookingsPerMonth = mysqli_query($conn, "
            SELECT MONTH(check_in_date) AS month, COUNT(*) AS count
            FROM bookings
            WHERE status IN ('Confirmed', 'Completed') AND YEAR(check_in_date) = $currentYear
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
            $monthIndex = $row['month'] - 1;
            $counts[$monthIndex] = $row['count'];
        }

        $maxBookings = max($counts) + 5;

        // Popular Room Types
        $roomTypeData = mysqli_query($conn, "
            SELECT room_type, COUNT(*) AS count
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            WHERE b.status IN ('Confirmed', 'Completed')
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
          <div class="card card-kpi card-total-bookings">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-calendar-check"></i> Total Bookings</h5>
              <p class="card-text"><?= $totalBookings ?></p>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card card-kpi card-active-bookings">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-check-circle"></i> Active Bookings</h5>
              <p class="card-text"><?= $activeBookings ?></p>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card card-kpi card-total-revenue">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Total Revenue</h5>
              <p class="card-text">₱<?= number_format($totalRevenue, 2) ?></p>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card card-kpi card-occupancy-rate">
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
          <div class="chart-container" style="width: 100%; height: 480px;">
            <h5>Bookings per Month</h5>
            <canvas id="bookingsChart" style="width: 100%; height: 100%;"></canvas>
          </div>
        </div>
        <div class="col-md-6">
          <div class="chart-container" style="width: 100%; height: 480px;">
            <h5>Popular Room Types</h5>
            <canvas id="roomTypeChart" style="width: 100%; height: 100%;"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
  const bookingsData = {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: '# of Bookings',
      data: <?= json_encode($counts) ?>,
      backgroundColor: [
        '#03dac6', '#ff5722', '#ffc107', '#bb86fc', '#4caf50',
        '#03dac6', '#ff5722', '#ffc107', '#bb86fc', '#4caf50',
        '#03dac6', '#ff5722'
      ]
    }]
  };

  const roomTypeData = {
    labels: <?= json_encode($roomTypes) ?>,
    datasets: [{
      data: <?= json_encode($roomCounts) ?>,
      backgroundColor: [
        '#03dac6', // Teal
        '#ff5722', // Orange
        '#ffc107', // Yellow
        '#bb86fc', // Purple
        '#4caf50'  // Green
      ]
    }]
  };

  const darkThemeOptions = {
    plugins: {
      legend: {
        display: true,
        labels: {
          color: '#e0e0e0'
        }
      },
      tooltip: {
        backgroundColor: '#333',
        titleColor: '#e0e0e0',
        bodyColor: '#e0e0e0',
        borderColor: '#444',
        borderWidth: 1
      }
    },
    scales: {
      x: {
        ticks: {
          color: '#e0e0e0'
        },
        grid: {
          color: '#333'
        }
      },
      y: {
        ticks: {
          color: '#e0e0e0'
        },
        grid: {
          color: '#333'
        }
      }
    }
  };

  new Chart(document.getElementById('bookingsChart'), {
    type: 'bar',
    data: bookingsData,
    options: {
      ...darkThemeOptions,
      scales: {
        x: {
          title: {
            display: true,
            text: 'Months',
            color: '#e0e0e0'
          },
          ticks: {
            color: '#e0e0e0'
          },
          grid: {
            color: '#333'
          }
        },
        y: {
          beginAtZero: true,
          max: <?= $maxBookings ?>,
          ticks: {
            stepSize: 5,
            color: '#e0e0e0'
          },
          grid: {
            color: '#333'
          },
          title: {
            display: true,
            text: 'Number of Bookings',
            color: '#e0e0e0'
          }
        }
      }
    }
  });

  new Chart(document.getElementById('roomTypeChart'), {
    type: 'doughnut',
    data: roomTypeData,
    options: {
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#e0e0e0'
          }
        },
        datalabels: {
          color: '#ffffff',
          font: {
            size: 14
          },
          formatter: (value) => {
            return `${value}`;
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
</script>

</body>
</html>
