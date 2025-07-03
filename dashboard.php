<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

$page_title = "Dashboard";

$time_period = isset($_GET['period']) ? $_GET['period'] : 'total';
$date_condition = '';
switch($time_period) {
    case 'today':
        $date_condition = "DATE(created_at) = CURDATE()";
        break;
    case 'month':
        $date_condition = "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        break;
    case 'year':
        $date_condition = "YEAR(created_at) = YEAR(CURDATE())";
        break;
    default:
        $date_condition = "1=1"; 
}

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

$roomStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(DISTINCT r.room_id) as total_rooms,
        COUNT(DISTINCT CASE 
            WHEN b.status = 'Confirmed' 
            AND CURDATE() BETWEEN b.check_in_date AND DATE_SUB(b.check_out_date, INTERVAL 1 DAY)
            THEN r.room_id 
        END) as occupied_rooms
    FROM rooms r
    LEFT JOIN bookings b ON r.room_id = b.room_id
"));

$totalRooms = $roomStats['total_rooms'];
$occupiedRooms = $roomStats['occupied_rooms'];
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

error_log("Occupancy Debug - Total Rooms: " . $totalRooms);
error_log("Occupancy Debug - Occupied Rooms: " . $occupiedRooms);
error_log("Occupancy Debug - Rate: " . $occupancyRate . "%");

$roomDetails = mysqli_query($conn, "
    SELECT 
        r.room_id,
        r.room_number,
        b.booking_id,
        b.check_in_date,
        b.check_out_date,
        b.status
    FROM rooms r
    LEFT JOIN bookings b ON r.room_id = b.room_id
    AND b.status = 'Confirmed'
    AND CURDATE() BETWEEN b.check_in_date AND DATE_SUB(b.check_out_date, INTERVAL 1 DAY)
    ORDER BY r.room_id
");

while ($room = mysqli_fetch_assoc($roomDetails)) {
    error_log(sprintf(
        "Room Debug - ID: %s, Number: %s, Booking: %s, Check-in: %s, Check-out: %s, Status: %s",
        $room['room_id'],
        $room['room_number'],
        $room['booking_id'] ?? 'None',
        $room['check_in_date'] ?? 'N/A',
        $room['check_out_date'] ?? 'N/A',
        $room['status'] ?? 'Available'
    ));
}

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
    $yearlyData = [];
    $years = [];
    
    while ($row = mysqli_fetch_assoc($bookingTrends)) {
        $year = $row['year'];
        if (!in_array($year, $years)) {
            $years[] = $year;
        }
    }
    
    rsort($years);
    
    $years = array_slice($years, 0, 5);
    
    foreach ($years as $year) {
        $yearlyData[$year] = array_fill(0, 12, 0);
    }
    
    mysqli_data_seek($bookingTrends, 0);
    
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

$datasets = [];
$colors = [
    '#F78166',
    '#58A6FF',
    '#7EE787', 
    '#FF7B72', 
    '#1a1d21'  
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

$recentActivities = mysqli_query($conn, "
    SELECT 
        l.*,
        COALESCE(u.first_name, 'Admin') as first_name,
        COALESCE(u.last_name, '') as last_name 
    FROM audit_logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.timestamp DESC LIMIT 5
");

$today = date('Y-m-d');
$todayStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN DATE(check_in_date) = CURDATE() AND status = 'Confirmed' THEN 1 ELSE 0 END) as checkins,
        SUM(CASE WHEN DATE(check_out_date) = CURDATE() AND status = 'Confirmed' THEN 1 ELSE 0 END) as checkouts,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_bookings,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() AND status IN ('Confirmed', 'Completed') THEN total_price ELSE 0 END), 0) as today_revenue
    FROM bookings
"));

$todayCheckins = $todayStats['checkins'] ?? 0;
$todayCheckouts = $todayStats['checkouts'] ?? 0;
$todayNewBookings = $todayStats['new_bookings'] ?? 0;
$todayRevenue = $todayStats['today_revenue'] ?? 0;

$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Set default Chart.js configuration
    Chart.defaults.color = "#8b949e";
    Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif";
    
    // Configure chart options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "top",
                align: "end",
                labels: {
                    color: "#e0e0e0",
                    font: { size: 12, weight: "500" },
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: "rgba(44, 48, 52, 0.9)",
                titleColor: "#e0e0e0",
                bodyColor: "#8b949e",
                borderColor: "rgba(255, 255, 255, 0.1)",
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8,
                titleFont: { size: 14, weight: "600" },
                bodyFont: { size: 13 },
                displayColors: false
            }
        },
        scales: {
            x: {
                grid: {
                    color: "rgba(201, 209, 217, 0.1)",
                    drawBorder: false
                },
                ticks: {
                    color: "#8b949e",
                    font: { size: 11, weight: "500" }
                }
            },
            y: {
                grid: {
                    color: "rgba(201, 209, 217, 0.1)",
                    drawBorder: false
                },
                ticks: {
                    color: "#8b949e",
                    font: { size: 11, weight: "500" },
                    padding: 8
                }
            }
        }
    };
});
</script>
';

echo "<!-- Debug Data -->";
echo "<!-- Years Data: " . json_encode($yearlyData) . " -->";
echo "<!-- Datasets: " . json_encode($datasets) . " -->";
echo "<!-- Room Types: " . json_encode($roomTypes) . " -->";
echo "<!-- Room Counts: " . json_encode($roomCounts) . " -->";

$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

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
        <a href="?period=today" class="btn btn-sm <?php echo $time_period == 'today' ? 'btn-primary' : 'btn-dark'; ?>">Today</a>
        <a href="?period=month" class="btn btn-sm <?php echo $time_period == 'month' ? 'btn-primary' : 'btn-dark'; ?>">This Month</a>
        <a href="?period=year" class="btn btn-sm <?php echo $time_period == 'year' ? 'btn-primary' : 'btn-dark'; ?>">This Year</a>
        <a href="?period=total" class="btn btn-sm <?php echo $time_period == 'total' ? 'btn-primary' : 'btn-dark'; ?>">All Time</a>
    </div>
</div>

<style>
:root {
  --primary-color: #F78166;
  --primary-light: rgba(247, 129, 102, 0.1);
  
  --bg-dark: #1a1d21;
  --bg-darker: #212529;
  --bg-card: #2c3034;
  
  --text-primary: #e0e0e0;
  --text-secondary: #8b949e;
  --text-muted: #6c757d;
  
  --border-color: rgba(255, 255, 255, 0.1);
  --border-hover: #495057;
  
  --success-color: #7EE787;
  --danger-color: #FF7B72;
  --warning-color: #f6b93b;
  --info-color: #58A6FF;
}

.grid-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 24px;
}

.stats-card {
  background: var(--bg-dark);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
  transition: transform 0.2s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
  border-color: var(--border-hover);
}

.stats-title {
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stats-value {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 600;
  margin: 8px 0;
}

.stats-change {
  color: var(--text-muted);
  font-size: 0.875rem;
}

.stats-change .text-success {
  color: var(--success-color) !important;
}

.stats-change .text-danger {
  color: var(--danger-color) !important;
}

.activity-list {
  background: var(--bg-dark);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
}

.activity-item {
  padding: 12px 0;
  border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  color: var(--primary-color);
}

.activity-text {
  color: var(--text-primary);
}

.activity-time {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.chart-container {
  background: var(--bg-dark);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
  margin-top: 24px;
}

.btn-group .btn {
  border: 1px solid var(--border-color);
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: var(--text-primary);
  background: var(--bg-darker);
}

.btn-group .btn:hover {
  background: var(--primary-light);
  border-color: var(--primary-color);
}

.btn-group .btn.btn-primary {
  background: var(--primary-color);
  border-color: var(--primary-color);
}

.daily-stat-item {
  background: var(--bg-darker);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  transition: transform 0.2s ease;
}

.daily-stat-item:hover {
  transform: translateY(-2px);
  border-color: var(--border-hover);
}

.daily-stat-label {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.daily-stat-value {
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
}

.text-success { color: var(--success-color) !important; }
.text-danger { color: var(--danger-color) !important; }
.text-warning { color: var(--warning-color) !important; }
.text-info { color: var(--info-color) !important; }
</style>

<div class="grid-container">
  <div class="stats-card">
    <div class="stats-title">
      TOTAL BOOKINGS
      <span class="period-label">
        <?php 
        switch($time_period) {
            case 'today':
                echo '(Today)';
                break;
            case 'month':
                echo '(This Month)';
                break;
            case 'year':
                echo '(This Year)';
                break;
            default:
                echo '(All Time)';
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
            case 'today':
                echo '(Today)';
                break;
            case 'month':
                echo '(This Month)';
                break;
            case 'year':
                echo '(This Year)';
                break;
            default:
                echo '(All Time)';
        }
        ?>
      </span>
    </div>
    <div class="stats-value">₱<?php echo number_format($revenueStats['total_revenue'], 2); ?></div>
    <div class="stats-change">
      <i class="fas fa-chart-line"></i>
      <span class="<?php echo $revenueGrowth >= 0 ? 'text-success' : 'text-danger'; ?>">
        <?php echo $revenueGrowth; ?>% vs last <?php echo $time_period == 'today' ? 'day' : 'month'; ?>
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
            <div class="daily-stat-value h4 mb-0 text-success"><?php echo number_format($todayCheckins); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 mb-3 rounded" style="background: rgba(248, 81, 73, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-sign-out-alt text-danger me-2"></i>
              <span class="text-white">Check-outs Today</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-danger"><?php echo number_format($todayCheckouts); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 mb-3 rounded" style="background: rgba(88, 166, 255, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-calendar-plus text-info me-2"></i>
              <span class="text-white">New Bookings Today</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-info"><?php echo number_format($todayNewBookings); ?></div>
          </div>
        </div>
        
        <div class="daily-stat-item p-3 rounded" style="background: rgba(246, 185, 59, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="daily-stat-label">
              <i class="fas fa-money-bill-wave text-warning me-2"></i>
              <span class="text-white">Today's Revenue</span>
            </div>
            <div class="daily-stat-value h4 mb-0 text-warning">₱<?php echo number_format($todayRevenue, 2); ?></div>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Dashboard Initialization Started ===');
    
    window.logDebugData();
    
    console.log('Chart.js status:', typeof Chart !== 'undefined' ? 'Loaded' : 'Not loaded');
    
    const bookingTrendsCtx = document.getElementById('bookingTrends');
    const roomTypeCtx = document.getElementById('roomTypeChart');
    
    console.log('Canvas elements:', {
        bookingTrends: bookingTrendsCtx ? 'Found' : 'Not found',
        roomTypeChart: roomTypeCtx ? 'Found' : 'Not found'
    });
    
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
