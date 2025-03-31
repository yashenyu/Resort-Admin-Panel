<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$page_title = "Dashboard";

// Time period filter
$time_period = isset($_GET['period']) ? $_GET['period'] : 'total';
$date_condition = '';
switch($time_period) {
    case 'month':
        $date_condition = "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        break;
    default:
        $date_condition = "1=1"; // total (all time)
}

// Total Bookings with status breakdown
$bookingStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM bookings
    WHERE $date_condition
"));
$totalBookings = $bookingStats['total'];
$activeBookings = $bookingStats['active'];
$completedBookings = $bookingStats['completed'];
$cancelledBookings = $bookingStats['cancelled'];

// Total Revenue with monthly comparison
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

$revenueStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN status IN ('Confirmed', 'Completed') THEN total_price ELSE 0 END) as total_revenue,
        SUM(CASE 
            WHEN status IN ('Confirmed', 'Completed') 
            AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' 
            THEN total_price ELSE 0 END
        ) as current_period_revenue,
        SUM(CASE 
            WHEN status IN ('Confirmed', 'Completed') 
            AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' 
            THEN total_price ELSE 0 END
        ) as last_period_revenue
    FROM bookings
    WHERE $date_condition
"));
$totalRevenue = $revenueStats['total_revenue'] ?? 0;
$currentMonthRevenue = $revenueStats['current_period_revenue'] ?? 0;
$lastMonthRevenue = $revenueStats['last_period_revenue'] ?? 0;
$revenueGrowth = $lastMonthRevenue > 0 ? 
    round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 0;

// Occupancy Rate with detailed room status
$roomStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE 
            WHEN EXISTS (
                SELECT 1 FROM bookings 
                WHERE room_id = rooms.room_id 
                AND status = 'Confirmed'
                AND CURDATE() BETWEEN check_in_date AND check_out_date
            ) THEN 1 ELSE 0 END
        ) as occupied_rooms
    FROM rooms
"));
$totalRooms = $roomStats['total_rooms'];
$occupiedRooms = $roomStats['occupied_rooms'];
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

// Bookings per Month for multiple years
$currentYear = date('Y');
$bookingTrendsQuery = "
    SELECT 
        MONTH(check_in_date) as month,
        YEAR(check_in_date) as year,
        COUNT(*) as count
    FROM bookings
    WHERE status IN ('Confirmed', 'Completed') 
    GROUP BY YEAR(check_in_date), MONTH(check_in_date)
    ORDER BY year DESC, month ASC";

echo "<!-- Debug: Query being executed: $bookingTrendsQuery -->";

$bookingTrends = mysqli_query($conn, $bookingTrendsQuery);

if (!$bookingTrends) {
    echo "<!-- Debug: MySQL Error: " . mysqli_error($conn) . " -->";
    $yearlyData = [];
} else {
    // Initialize arrays for storing the data
    $yearlyData = [];
    $years = [];
    
    // First pass: collect all years
    while ($row = mysqli_fetch_assoc($bookingTrends)) {
        $year = $row['year'];
        if (!in_array($year, $years)) {
            $years[] = $year;
        }
    }
    
    // Sort years in descending order
    rsort($years);
    
    // Take only the last 5 years if we have more
    $years = array_slice($years, 0, 5);
    
    // Initialize data for each year
    foreach ($years as $year) {
        $yearlyData[$year] = array_fill(0, 12, 0);
    }
    
    // Reset pointer to beginning of result set
    mysqli_data_seek($bookingTrends, 0);
    
    // Second pass: populate the data
    while ($row = mysqli_fetch_assoc($bookingTrends)) {
        $year = $row['year'];
        if (in_array($year, $years)) {
            $monthIndex = $row['month'] - 1;
            $yearlyData[$year][$monthIndex] = (int)$row['count'];
        }
    }
}

echo "<!-- Debug: Years found: " . json_encode($years) . " -->";
echo "<!-- Debug: Yearly Data: " . json_encode($yearlyData) . " -->";

// Prepare datasets for Chart.js
$datasets = [];
$colors = [
    '#F78166', // Orange
    '#58A6FF', // Blue
    '#7EE787', // Green
    '#FF7B72', // Red
    '#8B949E'  // Gray
];

foreach ($yearlyData as $year => $counts) {
    $colorIndex = count($datasets) % count($colors);
    $datasets[] = [
        'label' => (string)$year,
        'data' => array_values($counts),
        'borderColor' => $colors[$colorIndex],
        'backgroundColor' => 'rgba(' . implode(',', sscanf($colors[$colorIndex], "#%02x%02x%02x")) . ',0.1)',
        'tension' => 0.4,
        'fill' => true,
        'borderWidth' => 2
    ];
}

echo "<!-- Debug: Final Datasets: " . json_encode($datasets) . " -->";

// Popular Room Types with revenue
$roomTypeQuery = "
    SELECT 
        r.room_type,
        COUNT(*) as booking_count,
        SUM(b.total_price) as revenue,
        AVG(DATEDIFF(b.check_out_date, b.check_in_date)) as avg_stay
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.status IN ('Confirmed', 'Completed')
    GROUP BY r.room_type
    ORDER BY booking_count DESC";

echo "<!-- Debug: Room Type Query: $roomTypeQuery -->";

$roomTypeData = mysqli_query($conn, $roomTypeQuery);

if (!$roomTypeData) {
    echo "<!-- Debug: Room Type Query Error: " . mysqli_error($conn) . " -->";
    $roomTypes = [];
    $roomCounts = [];
    $roomRevenues = [];
    $avgStays = [];
} else {
    $roomTypes = [];
    $roomCounts = [];
    $roomRevenues = [];
    $avgStays = [];
    
    while ($row = mysqli_fetch_assoc($roomTypeData)) {
        $roomTypes[] = $row['room_type'];
        $roomCounts[] = (int)$row['booking_count'];
        $roomRevenues[] = round((float)$row['revenue'], 2);
        $avgStays[] = round((float)$row['avg_stay'], 1);
    }
    
    echo "<!-- Debug: Room Types Data: " . json_encode([
        'types' => $roomTypes,
        'counts' => $roomCounts,
        'revenues' => $roomRevenues,
        'avgStays' => $avgStays
    ]) . " -->";
}

// Recent Activities
$recentActivities = mysqli_query($conn, "
    SELECT l.*, u.first_name, u.last_name 
    FROM audit_logs l 
    JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.timestamp DESC LIMIT 5
");

// Today's Overview
$today = date('Y-m-d');
$todayStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(CASE WHEN DATE(check_in_date) = '$today' THEN 1 END) as checkins,
        COUNT(CASE WHEN DATE(check_out_date) = '$today' THEN 1 END) as checkouts,
        COUNT(CASE WHEN DATE(created_at) = '$today' THEN 1 END) as new_bookings,
        COALESCE(SUM(CASE WHEN DATE(created_at) = '$today' THEN total_price ELSE 0 END), 0) as today_revenue
    FROM bookings
    WHERE status IN ('Confirmed', 'Completed')
"));

$todayCheckins = $todayStats['checkins'] ?? 0;
$todayCheckouts = $todayStats['checkouts'] ?? 0;
$todayNewBookings = $todayStats['new_bookings'] ?? 0;
$todayRevenue = $todayStats['today_revenue'] ?? 0;

// Include Chart.js in extra_js
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
window.addEventListener("load", function() {
    if (typeof Chart === "undefined") {
        console.error("Chart.js failed to load!");
        return;
    }
    
    console.log("Chart.js loaded successfully");
    
    // Set default Chart.js configuration
    Chart.defaults.color = "#c9d1d9";
    Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif";
});
</script>
';

// After preparing the datasets, before the HTML
echo "<!-- Debug Data -->";
echo "<!-- Years Data: " . json_encode($yearlyData) . " -->";
echo "<!-- Datasets: " . json_encode($datasets) . " -->";
echo "<!-- Room Types: " . json_encode($roomTypes) . " -->";
echo "<!-- Room Counts: " . json_encode($roomCounts) . " -->";

$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// After preparing all the data, before including header.php
echo "
<script>
// Debug data objects
window.debugData = {
    datasets: " . json_encode($datasets) . ",
    roomTypes: " . json_encode($roomTypes) . ",
    roomCounts: " . json_encode($roomCounts) . ",
    months: " . json_encode($months) . "
};

// Console logging function
window.logDebugData = function() {
    console.log('=== Dashboard Debug Data ===');
    console.log('Datasets:', this.debugData.datasets);
    console.log('Room Types:', this.debugData.roomTypes);
    console.log('Room Counts:', this.debugData.roomCounts);
    console.log('Months:', this.debugData.months);
}
</script>
";

include 'includes/header.php';
?>

<!-- Stats Overview -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
    <div class="btn-group">
        <a href="?period=month" class="btn btn-sm <?php echo $time_period == 'month' ? 'btn-primary' : 'btn-dark'; ?>">This Month</a>
        <a href="?period=total" class="btn btn-sm <?php echo $time_period == 'total' ? 'btn-primary' : 'btn-dark'; ?>">All Time</a>
    </div>
</div>

<div class="grid-container">
  <div class="stats-card">
    <div class="stats-title">
      TOTAL BOOKINGS
      <span class="period-label">
        <?php 
        switch($time_period) {
            case 'month': echo '(This Month)';
                break;
            default: echo '(All Time)';
        }
        ?>
      </span>
    </div>
    <div class="stats-value"><?php echo number_format($bookingStats['total']); ?></div>
    <div class="stats-change">
      <i class="fas fa-chart-line"></i> 
      <span class="text-success"><?php echo number_format($bookingStats['completed']); ?></span> completed, 
      <span class="text-danger"><?php echo number_format($bookingStats['cancelled']); ?></span> cancelled
    </div>
  </div>
  
  <div class="stats-card">
    <div class="stats-title">
      ACTIVE BOOKINGS
      <span class="period-label">
        (Current)
      </span>
    </div>
    <div class="stats-value"><?php echo number_format($bookingStats['active']); ?></div>
    <div class="stats-change">
      <i class="fas fa-clock"></i> Current
    </div>
  </div>
  
  <div class="stats-card">
    <div class="stats-title">
      TOTAL REVENUE
      <span class="period-label">
        <?php 
        switch($time_period) {
            case 'month': echo '(This Month)';
                break;
            default: echo '(All Time)';
        }
        ?>
      </span>
    </div>
    <div class="stats-value">₱<?php echo number_format($revenueStats['total_revenue'], 2); ?></div>
    <div class="stats-change">
      <i class="fas fa-chart-line"></i>
      <span class="<?php echo $revenueGrowth >= 0 ? 'text-success' : 'text-danger'; ?>">
        <?php echo $revenueGrowth; ?>% vs last month
      </span>
    </div>
  </div>
  
  <div class="stats-card">
    <div class="stats-title">
      OCCUPANCY RATE
      <span class="period-label">
        (Current)
      </span>
    </div>
    <div class="stats-value"><?php echo $occupancyRate; ?>%</div>
    <div class="stats-change">
      <i class="fas fa-bed"></i> <?php echo $occupiedRooms; ?> of <?php echo $totalRooms; ?> rooms
    </div>
  </div>
</div>

<style>
.period-label {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-left: 0.5rem;
}

.btn-group .btn {
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-group .btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.btn-group .btn.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-dark {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>

<!-- Recent Activities and Daily Overview -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="stats-card">
      <h5 class="mb-4">Recent Activities</h5>
      <div class="activity-list">
        <?php while($activity = mysqli_fetch_assoc($recentActivities)): ?>
          <div class="activity-item d-flex align-items-center mb-3">
            <div class="activity-icon me-3">
              <i class="fas fa-circle-dot text-primary"></i>
            </div>
            <div class="activity-details flex-grow-1">
              <div class="activity-text">
                <span class="fw-medium text-white"><?php echo htmlspecialchars($activity['first_name']); ?></span>
                <span class="text-light"><?php echo htmlspecialchars($activity['action']); ?></span>
              </div>
              <div class="activity-time text-info-emphasis">
                <?php echo date('M d, H:i', strtotime($activity['timestamp'])); ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="stats-card">
      <h5 class="mb-4">Today's Overview</h5>
      <div class="daily-stats">
        <div class="daily-stat-item p-3 mb-3 rounded" style="background: rgba(46, 160, 67, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-sign-in-alt text-success me-2"></i>
              <span class="text-white">Check-ins Today</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-success"><?php echo number_format((int)$todayCheckins); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 mb-3 rounded" style="background: rgba(248, 81, 73, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-sign-out-alt text-danger me-2"></i>
              <span class="text-white">Check-outs Today</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-danger"><?php echo number_format((int)$todayCheckouts); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 mb-3 rounded" style="background: rgba(88, 166, 255, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-calendar-plus text-info me-2"></i>
              <span class="text-white">New Bookings Today</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-info"><?php echo number_format((int)$todayNewBookings); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 rounded" style="background: rgba(246, 185, 59, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-money-bill-wave text-warning me-2"></i>
              <span class="text-white">Today's Revenue</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-warning">₱<?php echo number_format((float)$todayRevenue, 2); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
  <div class="col-md-8">
    <div class="stats-card">
      <h5 class="mb-4">Booking Trends</h5>
      <div class="chart-container">
        <canvas id="bookingTrends"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stats-card">
      <h5 class="mb-4">Popular Room Types</h5>
      <div class="chart-container">
        <canvas id="roomTypeChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Room Type Performance -->
<div class="row mt-4">
  <div class="col-12">
    <div class="stats-card">
      <h5 class="mb-4">Room Type Performance</h5>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Room Type</th>
              <th>Total Bookings</th>
              <th>Revenue</th>
              <th>Avg. Stay (Days)</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i = 0; $i < count($roomTypes); $i++): ?>
              <tr>
                <td><?php echo htmlspecialchars($roomTypes[$i]); ?></td>
                <td><?php echo number_format($roomCounts[$i]); ?></td>
                <td>₱<?php echo number_format($roomRevenues[$i], 2); ?></td>
                <td><?php echo $avgStays[$i]; ?></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Dashboard Initialization Started ===');
    
    // Log debug data
    window.logDebugData();
    
    // Check if Chart.js is loaded
    console.log('Chart.js status:', typeof Chart !== 'undefined' ? 'Loaded' : 'Not loaded');
    
    // Check canvas elements
    const bookingTrendsCtx = document.getElementById('bookingTrends');
    const roomTypeCtx = document.getElementById('roomTypeChart');
    
    console.log('Canvas elements:', {
        bookingTrends: bookingTrendsCtx ? 'Found' : 'Not found',
        roomTypeChart: roomTypeCtx ? 'Found' : 'Not found'
    });
    
    // Verify data before chart creation
    console.log('=== Chart Data Verification ===');
    console.log('Booking Trends Data:', {
        labels: window.debugData.months,
        datasets: window.debugData.datasets
    });
    console.log('Room Types Data:', {
        labels: window.debugData.roomTypes,
        data: window.debugData.roomCounts
    });

    if (!bookingTrendsCtx) {
        console.error('Critical: bookingTrends canvas not found in DOM');
        return;
    }
    
    try {
        console.log('Attempting to create Booking Trends chart...');
        new Chart(bookingTrendsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: window.debugData.months,
                datasets: window.debugData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#c9d1d9',
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(26, 29, 33, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#c9d1d9',
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#c9d1d9',
                            padding: 5,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
        console.log('Booking Trends chart created successfully');
    } catch (error) {
        console.error('Failed to create Booking Trends chart:', error);
        console.error('Error details:', {
            message: error.message,
            stack: error.stack
        });
    }
    
    if (!roomTypeCtx) {
        console.error('Critical: roomTypeChart canvas not found in DOM');
        return;
    }
    
    try {
        console.log('Attempting to create Room Types chart...');
        new Chart(roomTypeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: window.debugData.roomTypes,
                datasets: [{
                    data: window.debugData.roomCounts,
                    backgroundColor: [
                        '#F78166',
                        '#58A6FF',
                        '#8B949E',
                        '#7EE787',
                        '#FF7B72'
                    ],
                    borderWidth: 2,
                    borderColor: '#1a1d21'
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
                            color: '#c9d1d9',
                            padding: 10,
                            font: {
                                size: 11
                            },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26, 29, 33, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return ` ${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        console.log('Room Types chart created successfully');
    } catch (error) {
        console.error('Failed to create Room Types chart:', error);
        console.error('Error details:', {
            message: error.message,
            stack: error.stack
        });
    }
    
    console.log('=== Dashboard Initialization Completed ===');
});
</script>

<?php include 'includes/footer.php'; ?>
