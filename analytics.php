<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$page_title = "Analytics";


$bookingsQuery = "
  SELECT 
    YEAR(check_in_date) AS year,
    MONTH(check_in_date) AS month,
    COUNT(*) AS total_bookings,
    SUM(total_price) as revenue
  FROM bookings
  WHERE status IN ('Completed', 'Confirmed')
  GROUP BY year, month
  ORDER BY year ASC, month ASC
";
$bookingsResult = mysqli_query($conn, $bookingsQuery);

$yearlyData = [];
$availableYears = [];

if ($bookingsResult && mysqli_num_rows($bookingsResult) > 0) {
    while ($row = mysqli_fetch_assoc($bookingsResult)) {
        $year = $row['year'];
        $month = $row['month'];
        
        if (!isset($yearlyData[$year])) {
            $yearlyData[$year] = [
                'bookings' => array_fill(1, 12, 0),
                'revenue' => array_fill(1, 12, 0),
                'total_bookings' => 0,
                'total_revenue' => 0
            ];
            $availableYears[] = $year;
        }
        
        $yearlyData[$year]['bookings'][$month] = (int)$row['total_bookings'];
        $yearlyData[$year]['revenue'][$month] = (float)$row['revenue'];
        $yearlyData[$year]['total_bookings'] += (int)$row['total_bookings'];
        $yearlyData[$year]['total_revenue'] += (float)$row['revenue'];
    }
}

sort($availableYears);


$classQuery = "SELECT status, COUNT(*) AS total 
               FROM bookings 
               WHERE status IN ('Cancelled', 'Completed', 'Pending') 
               GROUP BY status";
$classResult = mysqli_query($conn, $classQuery);
$classLabels = [];
$classCounts = [];
while ($row = mysqli_fetch_assoc($classResult)) {
  $classLabels[] = $row['status'];
  $classCounts[] = $row['total'];
}


$clusterQuery = "
  SELECT CASE 
      WHEN guests = 1 THEN 'Solo Travelers'
      WHEN guests IN (2,3) THEN 'Couples/Small Groups'
      ELSE 'Family Travelers'
    END AS segment, COUNT(*) AS total
  FROM bookings 
  GROUP BY segment
";
$clusterResult = mysqli_query($conn, $clusterQuery);
$segmentLabels = [];
$segmentCounts = [];
while ($row = mysqli_fetch_assoc($clusterResult)) {
  $segmentLabels[] = $row['segment'];
  $segmentCounts[] = $row['total'];
}


$browserQuery = "
  SELECT browser, COUNT(*) as count 
  FROM analytics 
  GROUP BY browser 
  ORDER BY count DESC";
$browserResult = mysqli_query($conn, $browserQuery);
$browserLabels = [];
$browserData = [];
$browserTotal = 0;
$browserPercentages = [];

$totalQuery = "SELECT COUNT(*) as total FROM analytics";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalVisitors = $totalRow['total'];

while ($row = mysqli_fetch_assoc($browserResult)) {
  $browserLabels[] = $row['browser'];
  $browserData[] = $row['count'];
  $browserPercentages[] = round(($row['count'] / $totalVisitors) * 100, 1);
}


$osQuery = "
  SELECT os, COUNT(*) as count 
  FROM analytics 
  GROUP BY os 
  ORDER BY count DESC";
$osResult = mysqli_query($conn, $osQuery);
$osLabels = [];
$osData = [];
$osPercentages = [];

while ($row = mysqli_fetch_assoc($osResult)) {
  $osLabels[] = $row['os'];
  $osData[] = $row['count'];
  $osPercentages[] = round(($row['count'] / $totalVisitors) * 100, 1);
}

$locationQuery = "
  SELECT location, COUNT(*) as count 
  FROM analytics 
  GROUP BY location 
  ORDER BY count DESC";
$locationResult = mysqli_query($conn, $locationQuery);
$locationLabels = [];
$locationData = [];
$locationPercentages = [];

while ($row = mysqli_fetch_assoc($locationResult)) {
  $locationLabels[] = $row['location'];
  $locationData[] = $row['count'];
  $locationPercentages[] = round(($row['count'] / $totalVisitors) * 100, 1);
}

$processorQuery = "
  SELECT processor, COUNT(*) as count 
  FROM analytics 
  GROUP BY processor 
  ORDER BY count DESC";
$processorResult = mysqli_query($conn, $processorQuery);
$processorLabels = [];
$processorData = [];
$processorPercentages = [];

while ($row = mysqli_fetch_assoc($processorResult)) {
  $processorLabels[] = $row['processor'];
  $processorData[] = $row['count'];
  $processorPercentages[] = round(($row['count'] / $totalVisitors) * 100, 1);
}

$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/regression@2.0.1/dist/regression.min.js"></script>
';

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>Analytics</h2>
</div>

<!-- Main Content -->
<div class="analytics-container">
  <!-- Top Row - Revenue and Status -->
  <div class="analytics-top-row">
    <!-- Revenue Trend -->
    <div class="analytics-card">
      <div class="analytics-card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <h5 class="analytics-card-title">Monthly Revenue</h5>
          <div class="year-toggle" id="revenueYearToggle">
            <?php foreach ($availableYears as $index => $year): ?>
            <button class="year-btn <?php echo $index === count($availableYears)-1 ? 'active' : ''; ?>" 
                    data-year="<?php echo $year; ?>"><?php echo $year; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="chart-container revenue-chart">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <!-- Booking Classification -->
    <div class="analytics-card">
      <div class="analytics-card-header">
        <h5 class="analytics-card-title">Booking Status</h5>
      </div>
      <div class="chart-container status-chart">
        <canvas id="classificationChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Bottom Row - Booking Trends -->
  <div class="analytics-bottom-row">
    <div class="analytics-card">
      <div class="analytics-card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <h5 class="analytics-card-title">Seasonal Linear Regression</h5>
          <div class="year-toggle" id="yearToggle">
            <?php foreach ($availableYears as $index => $year): ?>
            <button class="year-btn <?php echo $index === count($availableYears)-1 ? 'active' : ''; ?>" 
                    data-year="<?php echo $year; ?>"><?php echo $year; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="chart-container trend-chart">
        <canvas id="trendChart"></canvas>
      </div>
      <div class="regression-info">
        <div id="regressionResults" class="regression-equation"></div>
        <div id="regressionInterpretation" class="regression-interpretation"></div>
      </div>
    </div>
  </div>

  <!-- System Analytics Row -->
  <div class="analytics-system-row">
    <div class="analytics-card">
      <div class="analytics-card-header">
        <h5 class="analytics-card-title">Browser Usage</h5>
        <div class="analytics-card-subtitle">Total Visitors: <?php echo $totalVisitors; ?></div>
      </div>
      <div class="chart-container browser-chart">
        <canvas id="browserChart"></canvas>
      </div>
    </div>

    <div class="analytics-card">
      <div class="analytics-card-header">
        <h5 class="analytics-card-title">Operating Systems</h5>
        <div class="analytics-card-subtitle">Total Visitors: <?php echo $totalVisitors; ?></div>
      </div>
      <div class="chart-container os-chart">
        <canvas id="osChart"></canvas>
      </div>
    </div>

    <div class="analytics-card">
      <div class="analytics-card-header">
        <h5 class="analytics-card-title">Geographic Distribution</h5>
        <div class="analytics-card-subtitle">Total Visitors: <?php echo $totalVisitors; ?></div>
      </div>
      <div class="chart-container location-chart">
        <canvas id="locationChart"></canvas>
      </div>
    </div>

    <div class="analytics-card">
      <div class="analytics-card-header">
        <h5 class="analytics-card-title">Processor Types</h5>
        <div class="analytics-card-subtitle">Total Visitors: <?php echo $totalVisitors; ?></div>
      </div>
      <div class="chart-container processor-chart">
        <canvas id="processorChart"></canvas>
      </div>
    </div>
  </div>
</div>

<style>
:root {
  --primary-color: #F78166;
  --primary-light: rgba(247, 129, 102, 0.1);
  --text-primary: #e0e0e0;
  --text-secondary: #8b949e;
  --border-color: rgba(255, 255, 255, 0.1);
  --card-bg: #1a1d21;
  --grid-color: rgba(201, 209, 217, 0.1);
  --success-color: #7EE787;
  --info-color: #58A6FF;
}

.analytics-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin: 24px 0;
  padding: 0 16px;
}

.analytics-top-row {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: 24px;
}

.analytics-bottom-row {
  width: 100%;
}

.page-title {
  padding: 32px 16px 16px;
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 0;
}

.page-title h2 {
  color: var(--text-primary);
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
  letter-spacing: -0.5px;
}

.analytics-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.analytics-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.analytics-card-header {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-color);
}

.analytics-card-title {
  color: var(--text-primary);
  margin: 0;
  font-size: 1.1rem;
  font-weight: 500;
  letter-spacing: -0.25px;
}

.chart-container {
  background: var(--card-bg);
  border-radius: 8px;
  width: 100%;
  padding: 16px;
}

.trend-chart {
  height: 450px;
  position: relative;
}

.revenue-chart {
  height: 300px;
}

.status-chart {
  height: 300px;
}

.year-toggle {
  display: flex;
  gap: 4px;
  margin-top: 8px;
}

.year-btn {
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: 6px;
  padding: 6px 12px;
  color: var(--text-secondary);
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 70px;
}

.year-btn:hover:not(.active) {
  background-color: var(--primary-light);
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.year-btn.active {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: #fff;
  font-weight: 500;
}

.regression-info {
  margin-top: 24px;
  padding: 16px;
  border-top: 1px solid var(--border-color);
  text-align: center;
}

.regression-equation {
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 8px;
  letter-spacing: -0.25px;
}

.regression-interpretation {
  color: var(--text-secondary);
  font-size: 0.875rem;
  line-height: 1.5;
}

@media (max-width: 1200px) {
  .analytics-top-row {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  
  .analytics-container {
    padding: 0 12px;
  }
  
  .trend-chart {
    height: 400px;
  }
  
  .revenue-chart,
  .status-chart {
    height: 250px;
  }
  
  .analytics-card {
    padding: 16px;
  }
}

@media (max-width: 768px) {
  .page-title {
    padding: 24px 12px 12px;
  }
  
  .page-title h2 {
    font-size: 1.5rem;
  }
  
  .analytics-card-header {
    padding-bottom: 12px;
    margin-bottom: 16px;
  }
  
  .year-toggle {
    flex-wrap: wrap;
    gap: 6px;
  }
  
  .year-btn {
    padding: 4px 8px;
    font-size: 0.8125rem;
    min-width: 60px;
  }
  
  .trend-chart {
    height: 300px;
    padding: 12px;
  }
  
  .regression-info {
    padding: 12px;
    margin-top: 16px;
  }
}

.analytics-system-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-top: 24px;
}

.browser-chart,
.os-chart,
.location-chart,
.processor-chart {
  height: 250px;
}

@media (max-width: 1200px) {
  .analytics-system-row {
    grid-template-columns: 1fr;
  }
}

.analytics-card-subtitle {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin-top: 4px;
}

.chart-percentage {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const yearlyData = <?php echo json_encode($yearlyData); ?>;
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  
  let trendChart = null;
  let revenueChart = null;
  
  function createRegressionData(data) {
    return data.map((y, x) => [x, y]);
  }
  
  function calculateRegression(data) {
    const regressionData = createRegressionData(data);
    const result = regression.linear(regressionData);
    const slope = result.equation[0];
    const intercept = result.equation[1];
    const r2 = result.r2;
    
    const regressionPoints = result.points.map(p => Math.round(p[1]));
    
    return {
      slope,
      intercept,
      r2,
      regressionPoints
    };
  }
  
  function updateTrendChart(year) {
    const bookings = yearlyData[year].bookings;
    const bookingsArray = Object.values(bookings);
    const regression = calculateRegression(bookingsArray);
    
    if (trendChart) {
      trendChart.destroy();
    }
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: monthNames,
        datasets: [
          {
            label: 'Monthly Bookings',
            data: bookingsArray,
            borderColor: '#F78166',
            backgroundColor: 'rgba(247,129,102,0.1)',
            tension: 0.3,
            pointRadius: 6,
            pointHoverRadius: 8,
            fill: true
          },
          {
            label: 'Trend Line',
            data: regression.regressionPoints,
            borderColor: '#7EE787',
            borderDash: [5, 5],
            tension: 0,
            pointRadius: 0,
            fill: false
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        scales: {
          x: {
            grid: {
              color: 'rgba(201,209,217,0.1)',
              drawBorder: false
            },
            ticks: {
              color: '#8b949e',
              font: { size: 12, weight: '500' }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(201,209,217,0.1)',
              drawBorder: false
            },
            ticks: {
              color: '#8b949e',
              font: { size: 12, weight: '500' },
              padding: 8
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: {
              color: '#e0e0e0',
              font: { size: 12, weight: '500' },
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: 'rgba(26, 29, 33, 0.9)',
            titleColor: '#e0e0e0',
            bodyColor: '#8b949e',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 14, weight: '600' },
            bodyFont: { size: 13 },
            displayColors: false
          }
        }
      }
    });
    
    const eqn = `y = ${regression.slope.toFixed(2)}x + ${regression.intercept.toFixed(2)} (R² = ${regression.r2.toFixed(3)})`;
    document.getElementById('regressionResults').innerHTML = `<strong>Regression Equation:</strong> ${eqn}`;
    
    let interpretation = "<strong>Interpretation:</strong> ";
    interpretation += `The trend shows an average ${regression.slope >= 0 ? "increase" : "decrease"} of ${Math.abs(regression.slope).toFixed(1)} bookings per month in ${year}. `;
    interpretation += `The model explains ${(regression.r2 * 100).toFixed(1)}% of the variation in booking numbers.`;
    document.getElementById('regressionInterpretation').innerHTML = interpretation;
  }
  
  function updateRevenueChart(year) {
    const revenue = yearlyData[year].revenue;
    const revenueArray = Object.values(revenue);
    
    if (revenueChart) {
      revenueChart.destroy();
    }
    
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(revCtx, {
      type: 'bar',
      data: {
        labels: monthNames,
        datasets: [{
          label: 'Monthly Revenue',
          data: revenueArray,
          backgroundColor: 'rgba(247,129,102,0.2)',
          borderColor: '#F78166',
          borderWidth: 1,
          borderRadius: 4,
          hoverBackgroundColor: 'rgba(247,129,102,0.3)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#8b949e',
              font: { size: 11, weight: '500' }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(201,209,217,0.1)',
              drawBorder: false
            },
            ticks: { 
              color: '#8b949e',
              callback: function(value) {
                return '₱' + value.toLocaleString();
              },
              font: { size: 11, weight: '500' },
              padding: 8
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: {
              color: '#e0e0e0',
              font: { size: 12, weight: '500' },
              padding: 20,
              usePointStyle: true,
              pointStyle: 'rect'
            }
          },
          tooltip: {
            backgroundColor: 'rgba(26, 29, 33, 0.9)',
            titleColor: '#e0e0e0',
            bodyColor: '#8b949e',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 14, weight: '600' },
            bodyFont: { size: 13 },
            displayColors: false,
            callbacks: {
              label: function(context) {
                return '₱' + context.parsed.y.toLocaleString();
              }
            }
          }
        }
      }
    });
  }
  
  const latestYear = <?php echo end($availableYears); ?>;
  updateTrendChart(latestYear);
  updateRevenueChart(latestYear);
  
  function handleYearButtonClick(button, toggleId, updateFunction) {
    const buttons = document.querySelectorAll(`#${toggleId} .year-btn`);
    buttons.forEach(btn => {
      btn.classList.remove('active');
      btn.style.transform = '';
    });
    button.classList.add('active');
    button.style.transform = 'scale(0.95)';
    setTimeout(() => button.style.transform = '', 150);
    updateFunction(button.dataset.year);
  }
  
  document.querySelectorAll('#yearToggle .year-btn').forEach(button => {
    button.addEventListener('click', function() {
      handleYearButtonClick(this, 'yearToggle', updateTrendChart);
    });
  });
  
  document.querySelectorAll('#revenueYearToggle .year-btn').forEach(button => {
    button.addEventListener('click', function() {
      handleYearButtonClick(this, 'revenueYearToggle', updateRevenueChart);
    });
  });
  
  const classCtx = document.getElementById('classificationChart').getContext('2d');
  new Chart(classCtx, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode($classLabels); ?>,
      datasets: [{
        data: <?php echo json_encode($classCounts); ?>,
        backgroundColor: ['#F78166', '#58A6FF', '#8B949E'],
        borderColor: 'transparent',
        borderRadius: 4,
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: { 
          position: 'bottom',
          labels: { 
            color: '#e0e0e0',
            font: { size: 11, weight: '500' },
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(26, 29, 33, 0.9)',
          titleColor: '#e0e0e0',
          bodyColor: '#8b949e',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          titleFont: { size: 14, weight: '600' },
          bodyFont: { size: 13 }
        }
      }
    }
  });

  const browserCtx = document.getElementById('browserChart').getContext('2d');
  new Chart(browserCtx, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_map(function($label, $percentage) {
        return $label . ' (' . $percentage . '%)';
      }, $browserLabels, $browserPercentages)); ?>,
      datasets: [{
        data: <?php echo json_encode($browserData); ?>,
        backgroundColor: ['#F78166', '#58A6FF', '#8B949E', '#238636', '#F6F8FA'],
        borderColor: 'transparent',
        borderRadius: 4,
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: { 
          position: 'bottom',
          labels: { 
            color: '#e0e0e0',
            font: { size: 11, weight: '500' },
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(26, 29, 33, 0.9)',
          titleColor: '#e0e0e0',
          bodyColor: '#8b949e',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          titleFont: { size: 14, weight: '600' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              const value = context.raw;
              const percentage = <?php echo json_encode($browserPercentages); ?>[context.dataIndex];
              return `${value} visitors (${percentage}%)`;
            }
          }
        }
      }
    }
  });

  const osCtx = document.getElementById('osChart').getContext('2d');
  new Chart(osCtx, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_map(function($label, $percentage) {
        return $label . ' (' . $percentage . '%)';
      }, $osLabels, $osPercentages)); ?>,
      datasets: [{
        data: <?php echo json_encode($osData); ?>,
        backgroundColor: ['#F78166', '#58A6FF', '#8B949E', '#238636', '#F6F8FA'],
        borderColor: 'transparent',
        borderRadius: 4,
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: { 
          position: 'bottom',
          labels: { 
            color: '#e0e0e0',
            font: { size: 11, weight: '500' },
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(26, 29, 33, 0.9)',
          titleColor: '#e0e0e0',
          bodyColor: '#8b949e',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          titleFont: { size: 14, weight: '600' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              const value = context.raw;
              const percentage = <?php echo json_encode($osPercentages); ?>[context.dataIndex];
              return `${value} visitors (${percentage}%)`;
            }
          }
        }
      }
    }
  });

  const locationCtx = document.getElementById('locationChart').getContext('2d');
  new Chart(locationCtx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($locationLabels); ?>,
      datasets: [{
        label: 'Visitors by Location',
        data: <?php echo json_encode($locationData); ?>,
        backgroundColor: '#F78166',
        borderColor: 'transparent',
        borderRadius: 4,
        barPercentage: 0.6,
        categoryPercentage: 0.8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',  
      plugins: {
        legend: { 
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(26, 29, 33, 0.9)',
          titleColor: '#e0e0e0',
          bodyColor: '#8b949e',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          titleFont: { size: 14, weight: '600' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              const value = context.raw;
              const percentage = <?php echo json_encode($locationPercentages); ?>[context.dataIndex];
              return `${value} visitors (${percentage}%)`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: 'rgba(201,209,217,0.1)',
            drawBorder: false
          },
          ticks: {
            color: '#8b949e',
            font: { size: 11, weight: '500' }
          }
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            color: '#e0e0e0',
            font: { size: 11, weight: '500' }
          }
        }
      }
    }
  });

  const processorCtx = document.getElementById('processorChart').getContext('2d');
  new Chart(processorCtx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($processorLabels); ?>,
      datasets: [{
        label: 'Visitors by Processor',
        data: <?php echo json_encode($processorData); ?>,
        backgroundColor: '#58A6FF',
        borderColor: 'transparent',
        borderRadius: 4,
        barPercentage: 0.6,
        categoryPercentage: 0.8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(26, 29, 33, 0.9)',
          titleColor: '#e0e0e0',
          bodyColor: '#8b949e',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          titleFont: { size: 14, weight: '600' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              const value = context.raw;
              const percentage = <?php echo json_encode($processorPercentages); ?>[context.dataIndex];
              return `${value} visitors (${percentage}%)`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: 'rgba(201,209,217,0.1)',
            drawBorder: false
          },
          ticks: {
            color: '#8b949e',
            font: { size: 11, weight: '500' }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(201,209,217,0.1)',
            drawBorder: false
          },
          ticks: {
            color: '#8b949e',
            font: { size: 11, weight: '500' }
          }
        }
      }
    }
  });
});
</script>

<?php include 'includes/footer.php'; ?>

