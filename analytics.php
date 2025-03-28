<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// Fetch data for charts and recent visits
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

// Prepare data for charts
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
?>

<!DOCTYPE html>
<html>
<head>
  <title>Analytics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Visitor Analytics</h2>

    <div class="row mt-4">
      <div class="col-md-6">
        <h5>Top Locations</h5>
        <canvas id="locationChart"></canvas>
      </div>
      <div class="col-md-6">
        <h5>Top Browsers</h5>
        <canvas id="browserChart"></canvas>
      </div>
    </div>

    <div class="row mt-5">
      <div class="col-md-6">
        <h5>Top Platforms (OS)</h5>
        <canvas id="platformChart"></canvas>
      </div>
      <div class="col-md-6">
        <h5>Visits Over Time</h5>
        <canvas id="visitsChart"></canvas>
      </div>
    </div>

    <div class="mt-5">
      <h5>Recent Visits</h5>
      <table class="table table-bordered table-sm">
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
    </div>
  </div>
</div>

<script>
// Chart.js Render
new Chart(document.getElementById('locationChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($locationLabels) ?>,
    datasets: [{
      data: <?= json_encode($locationCounts) ?>,
      backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1']
    }]
  }
});

new Chart(document.getElementById('browserChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($browserLabels) ?>,
    datasets: [{
      label: 'Visits',
      data: <?= json_encode($browserCounts) ?>,
      backgroundColor: '#0dcaf0'
    }]
  }
});

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
    indexAxis: 'y'
  }
});

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
  }
});
</script>
</body>
</html>
