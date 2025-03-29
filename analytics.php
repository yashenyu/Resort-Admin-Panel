<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// Fetch data for charts and recent visits from the analytics table
$locationData = mysqli_query($conn, "
  SELECT location, COUNT(*) AS count FROM analytics
  GROUP BY location ORDER BY count DESC LIMIT 5
");

$browserData = mysqli_query($conn, "
  SELECT browser, COUNT(*) AS count FROM analytics
  GROUP BY browser ORDER BY count DESC
");

$platformData = mysqli_query($conn, "
  SELECT os, COUNT(*) AS count FROM analytics
  GROUP BY os ORDER BY count DESC
");

$visitsData = mysqli_query($conn, "
  SELECT DATE(timestamp) as day, COUNT(*) as total FROM analytics
  GROUP BY day ORDER BY day ASC
");

$recent = mysqli_query($conn, "
  SELECT * FROM analytics
  ORDER BY timestamp DESC LIMIT 20
");

// Prepare data for analytics charts
$locationLabels = $locationCounts = [];
while ($row = mysqli_fetch_assoc($locationData)) {
  $locationLabels[] = $row['location'] ?: 'Unknown';
  $locationCounts[] = $row['count'];
}

$browserLabels = $browserCounts = [];
while ($row = mysqli_fetch_assoc($browserData)) {
  $browserLabels[] = $row['browser'] ?: 'Unknown';
  $browserCounts[] = $row['count'];
}

$platformLabels = $platformCounts = [];
while ($row = mysqli_fetch_assoc($platformData)) {
  $platformLabels[] = $row['os'] ?: 'Unknown';
  $platformCounts[] = $row['count'];
}

$visitDates = $visitCounts = [];
while ($row = mysqli_fetch_assoc($visitsData)) {
  $visitDates[] = $row['day'];
  $visitCounts[] = $row['total'];
}

// Fetch bookings per month for the time series regression analysis
$bookingsTrendData = mysqli_query($conn, "
  SELECT DATE_FORMAT(check_in_date, '%Y-%m') as month, COUNT(*) as totalBookings
  FROM bookings
  GROUP BY month
  ORDER BY month ASC
");

$bookingMonths = $bookingCounts = [];
while ($row = mysqli_fetch_assoc($bookingsTrendData)) {
  $bookingMonths[] = $row['month'];
  $bookingCounts[] = $row['totalBookings'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Analytics</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Main Dark Theme (if you have it) -->
  <link rel="stylesheet" href="css/modern-theme.css">
  <!-- Your new separate CSS for analytics -->
  <link rel="stylesheet" href="css/analytics.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Regression-js library for polynomial regression -->
  <script src="https://cdn.jsdelivr.net/npm/regression@2.0.1/dist/regression.min.js"></script>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="content">
    <div class="container-fluid p-4">
      <h2 class="mb-4 text-light">
        <i class="fas fa-chart-pie"></i> Visitor & Booking Analytics
      </h2>

      <!-- Nav Tabs -->
      <ul class="nav nav-tabs" id="analyticsTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#location" type="button">
            <i class="fas fa-map-marker-alt"></i> Top Locations
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#browser" type="button">
            <i class="fas fa-globe"></i> Top Browsers
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#platform" type="button">
            <i class="fas fa-desktop"></i> Top Platforms
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#visits" type="button">
            <i class="fas fa-chart-line"></i> Visits Over Time
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#trend" type="button">
            <i class="fas fa-chart-area"></i> Bookings Trend
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#recent" type="button">
            <i class="fas fa-clock"></i> Recent Visits
          </button>
        </li>
      </ul>

      <!-- Dark tab content container -->
      <div class="tab-content tab-content-dark" id="analyticsTabsContent">
        <!-- Top Locations -->
        <div class="tab-pane fade show active" id="location">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title mb-3">Top Locations</h5>
              <div class="chart-container">
                <canvas id="locationChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Browsers -->
        <div class="tab-pane fade" id="browser">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title mb-3">Top Browsers</h5>
              <div class="chart-container">
                <canvas id="browserChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Platforms -->
        <div class="tab-pane fade" id="platform">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title mb-3">Top Platforms (OS)</h5>
              <div class="chart-container">
                <canvas id="platformChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Visits Over Time -->
        <div class="tab-pane fade" id="visits">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title mb-3">Visits Over Time</h5>
              <div class="chart-container">
                <canvas id="visitsChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Bookings Trend (Time Series Regression) -->
        <div class="tab-pane fade" id="trend">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title mb-3">Bookings Trend (Time Series Regression)</h5>
              <div class="chart-container">
                <canvas id="regressionChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Visits -->
        <div class="tab-pane fade" id="recent">
          <div class="card mt-3 chart-card">
            <div class="card-body">
              <h5 class="card-title">Recent Visits</h5>
              <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered align-middle table-dark">
                  <thead class="table-light">
                    <tr>
                      <th>IP</th>
                      <th>Location</th>
                      <th>Browser</th>
                      <th>Platform (OS)</th>
                      <th>Processor</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                      <tr>
                        <td><?= $row['ip_address'] ?? 'N/A' ?></td>
                        <td><?= $row['location'] ?? 'N/A' ?></td>
                        <td><?= $row['browser'] ?? 'N/A' ?></td>
                        <td><?= $row['os'] ?? 'N/A' ?></td>
                        <td><?= $row['processor'] ?? 'N/A' ?></td>
                        <td><?= date("M d, Y - h:i A", strtotime($row['timestamp'])) ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div> <!-- /.table-responsive -->
            </div> <!-- /.card-body -->
          </div> <!-- /.card -->
        </div>
      </div> <!-- /.tab-content-dark -->
    </div> <!-- /.container-fluid -->
  </div> <!-- /.content -->
</div> <!-- /.d-flex -->

<script>
// Dark theme options for Chart.js
const chartOptions = {
  plugins: {
    legend: {
      labels: { color: '#e0e0e0' }
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
      ticks: { color: '#e0e0e0' },
      grid: { color: '#333' }
    },
    y: {
      ticks: { color: '#e0e0e0' },
      grid: { color: '#333' }
    }
  }
};

// 1. Top Locations Chart
new Chart(document.getElementById('locationChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($locationLabels) ?>,
    datasets: [{
      data: <?= json_encode($locationCounts) ?>,
      backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1']
    }]
  },
  options: chartOptions
});

// 2. Top Browsers Chart
new Chart(document.getElementById('browserChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($browserLabels) ?>,
    datasets: [{
      label: 'Visits',
      data: <?= json_encode($browserCounts) ?>,
      backgroundColor: '#0dcaf0'
    }]
  },
  options: chartOptions
});

// 3. Top Platforms Chart
new Chart(document.getElementById('platformChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($platformLabels) ?>,
    datasets: [{
      label: 'Visits',
      data: <?= json_encode($platformCounts) ?>,
      backgroundColor: '#fd7e14'
    }]
  },
  options: {
    ...chartOptions,
    indexAxis: 'y'
  }
});

// 4. Visits Over Time
new Chart(document.getElementById('visitsChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($visitDates) ?>,
    datasets: [{
      label: 'Visits per Day',
      data: <?= json_encode($visitCounts) ?>,
      borderColor: '#0d6efd',
      fill: true,
      backgroundColor: 'rgba(13, 110, 253, 0.1)'
    }]
  },
  options: chartOptions
});

// 5. Bookings Trend Regression
var bookingMonths = <?= json_encode($bookingMonths) ?>;
var bookingCounts = <?= json_encode($bookingCounts) ?>;

var dataPointsBookings = bookingCounts.map((count, i) => [i, count]);
var resultBookings = regression.polynomial(dataPointsBookings, { order: 2 });
var regressionYBookings = resultBookings.points.map(point => point[1]);

new Chart(document.getElementById('regressionChart'), {
  type: 'line',
  data: {
    labels: bookingMonths,
    datasets: [
      {
        label: 'Actual Bookings',
        data: bookingCounts,
        borderColor: '#20c997',
        backgroundColor: 'rgba(32, 201, 151, 0.1)',
        fill: true,
        pointRadius: 3
      },
      {
        label: 'Quadratic Regression',
        data: regressionYBookings,
        borderColor: '#f8f9fa',
        borderDash: [5, 5],
        fill: false,
        pointRadius: 0
      }
    ]
  },
  options: {
    ...chartOptions,
    plugins: {
      legend: { labels: { boxWidth: 12 } },
      tooltip: { mode: 'index', intersect: false }
    },
    scales: {
      x: { ticks: { autoSkip: true, maxTicksLimit: 14 } },
      y: { beginAtZero: true }
    }
  }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
