<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$page_title = "Analytics";

// SECTION 1: Booking Trends & Regression Forecast
$trendQuery = "SELECT DATE_FORMAT(check_in_date, '%Y-%m') AS month, COUNT(*) AS totalBookings 
               FROM bookings 
               GROUP BY month 
               ORDER BY month ASC";
$trendResult = mysqli_query($conn, $trendQuery);

$bookingMonths = [];
$bookingCounts = [];

if ($trendResult) {
    while ($row = mysqli_fetch_assoc($trendResult)) {
        $bookingMonths[] = $row['month'];
        $bookingCounts[] = (int)$row['totalBookings'];
    }
}

// Fallback sample data
if (empty($bookingMonths)) {
    $bookingMonths = ["2024-01", "2024-02", "2024-03"];
    $bookingCounts = [5, 8, 12];
}

// Booking Classification
$classQuery = "SELECT status, COUNT(*) AS total 
               FROM bookings 
               WHERE status IN ('Cancelled', 'Completed') 
               GROUP BY status";
$classResult = mysqli_query($conn, $classQuery);
$classLabels = [];
$classCounts = [];
while ($row = mysqli_fetch_assoc($classResult)) {
  $classLabels[] = $row['status'];
  $classCounts[] = $row['total'];
}

// Guest Segmentation
$clusterQuery = "SELECT CASE 
                    WHEN guests = 1 THEN 'Solo Travelers'
                    WHEN guests IN (2,3) THEN 'Couples/Small Groups'
                    ELSE 'Family Travelers'
                 END AS segment, COUNT(*) AS total
                 FROM bookings 
                 GROUP BY segment";
$clusterResult = mysqli_query($conn, $clusterQuery);
$segmentLabels = [];
$segmentCounts = [];
while ($row = mysqli_fetch_assoc($clusterResult)) {
  $segmentLabels[] = $row['segment'];
  $segmentCounts[] = $row['total'];
}

// Add libraries
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0/dist/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/regression@2.0.1/dist/regression.min.js"></script>
';

include 'includes/header.php';
?>

<h2 class="mb-4">Analytics</h2>

<!-- Nav Tabs -->
<ul class="nav nav-tabs" id="analyticsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="trend-tab" data-bs-toggle="tab" data-bs-target="#trend" type="button" role="tab">
      Booking Trends
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="classification-tab" data-bs-toggle="tab" data-bs-target="#classification" type="button" role="tab">
      Booking Classification
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="segmentation-tab" data-bs-toggle="tab" data-bs-target="#segmentation" type="button" role="tab">
      Guest Segmentation
    </button>
  </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="analyticsTabContent">
  <!-- Booking Trends -->
  <div class="tab-pane fade show active" id="trend" role="tabpanel">
    <div class="chart-card">
      <div class="chart-card-header"><h5 class="chart-card-title">Booking Trends & Forecast</h5></div>
      <div class="chart-container" style="position: relative; height: 400px;">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Booking Classification -->
  <div class="tab-pane fade" id="classification" role="tabpanel">
    <div class="chart-card">
      <div class="chart-card-header"><h5 class="chart-card-title">Booking Outcomes</h5></div>
      <div class="chart-container">
        <canvas id="classificationChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Guest Segmentation -->
  <div class="tab-pane fade" id="segmentation" role="tabpanel">
    <div class="chart-card">
      <div class="chart-card-header"><h5 class="chart-card-title">Guest Segments</h5></div>
      <div class="chart-container">
        <canvas id="segmentationChart"></canvas>
      </div>
    </div>
  </div>
</div>

<style>
.chart-card {
  background: #1a1d21;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}
.chart-card-title {
  color: #e0e0e0;
  margin: 0;
}
.chart-container {
  background: #1a1d21;
  border-radius: 8px;
  padding: 10px;
}
.nav-tabs .nav-link.active {
  border-color: #F78166;
  color: #F78166;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const months = <?php echo json_encode($bookingMonths); ?>;
  const bookings = <?php echo json_encode($bookingCounts); ?>;

  let forecastMonths = [...months];
  let last = months[months.length - 1].split('-');
  let year = parseInt(last[0]);
  let month = parseInt(last[1]);

  // Add 3 forecast months
  for (let i = 0; i < 3; i++) {
    month++;
    if (month > 12) {
      month = 1;
      year++;
    }
    forecastMonths.push(`${year}-${month.toString().padStart(2, '0')}`);
  }

  // Linear regression
  let regressionData = months.map((_, i) => [i, bookings[i]]);
  let result = regression.linear(regressionData);
  let regressionLine = forecastMonths.map((_, i) => Math.round(result.predict(i)[1]));

  // Forecast values: null for actual, predictions for forecast
  let forecastValues = regressionLine.map((val, idx) => idx < months.length ? null : val);

  // Booking Trend Chart
  new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: forecastMonths,
      datasets: [
        {
          label: 'Actual Bookings',
          data: bookings,
          borderColor: '#F78166',
          backgroundColor: 'rgba(247,129,102,0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 4
        },
        {
          label: 'Trend Line',
          data: regressionLine,
          borderColor: '#7EE787',
          fill: false,
          tension: 0,
          borderDash: [5, 5],
          pointRadius: 0
        },
        {
          label: 'Forecast',
          data: forecastValues,
          borderColor: '#58A6FF',
          backgroundColor: 'rgba(88,166,255,0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { color: '#e0e0e0' }
        },
        annotation: {
          annotations: {
            line1: {
              type: 'line',
              xMin: months.length - 0.5,
              xMax: months.length - 0.5,
              borderColor: 'rgba(255,255,255,0.3)',
              borderDash: [6, 6],
              borderWidth: 2,
              label: {
                content: 'Forecast Start',
                enabled: true,
                position: 'top',
                backgroundColor: '#000',
                color: '#fff',
                padding: 4
              }
            }
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#c9d1d9' },
          grid: { color: 'rgba(201,209,217,0.1)' }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: '#c9d1d9',
            precision: 0,
            callback: value => Number.isInteger(value) ? value : ''
          },
          grid: { color: 'rgba(201,209,217,0.1)' }
        }
      }
    }
  });

  // Classification Chart
  new Chart(document.getElementById('classificationChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode($classLabels); ?>,
      datasets: [{
        data: <?php echo json_encode($classCounts); ?>,
        backgroundColor: ['#F78166', '#58A6FF'],
        borderColor: 'transparent'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#e0e0e0' }
        }
      }
    }
  });

  // Segmentation Chart
  new Chart(document.getElementById('segmentationChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($segmentLabels); ?>,
      datasets: [{
        data: <?php echo json_encode($segmentCounts); ?>,
        backgroundColor: ['#F78166', '#58A6FF', '#8B949E'],
        borderColor: 'transparent'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#e0e0e0' }
        }
      }
    }
  });
});
</script>

<?php include 'includes/footer.php'; ?>
