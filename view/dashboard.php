<?php
session_start();
require '../database/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// ====================================================
// EXAMPLE: Additional or modified queries for advanced metrics
// (Replace with actual queries/calculations as needed)
// ====================================================

// Placeholder: This Month's Revenue
$thisMonthsRevenue = 7890.55;

// Placeholder: Occupancy Rate (percentage)
$occupancyRate = 72.5;

// Placeholder: Average Daily Rate (ADR)
$ADR = 120.00;

// Placeholder: Revenue Per Available Room (RevPAR)
$RevPAR = 85.60;

// Example placeholders for new charts
//  - Occupancy Over Time: array of days & occupancy percentages
$occupancyDays = ['1 Sep', '2 Sep', '3 Sep', '4 Sep', '5 Sep', '6 Sep', '7 Sep'];
$occupancyData = [68, 70, 72, 75, 80, 78, 82];

//  - Cancellations vs Confirmations Over Time
$cancConfDays = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];
$cancellationsData = [5, 7, 3, 4, 6];
$confirmationsData = [20, 25, 22, 28, 30];

// ====================================================
// ORIGINAL QUERIES FOR YOUR EXISTING METRICS
// ====================================================

// 1. Total Revenue (multiply room_rate by number of nights)
$totalRevenueQuery = "
  SELECT SUM(DATEDIFF(bookings.check_out_date, bookings.check_in_date) * rooms.room_rate) AS total_revenue
  FROM bookings
  JOIN rooms ON bookings.room_id = rooms.room_id
  WHERE bookings.status = 'confirmed'
";

// 2. Total Bookings
$totalBookingsQuery = "SELECT COUNT(*) AS total_bookings FROM bookings WHERE status = 'confirmed'";

// 3. Check-Ins (today, confirmed)
$checkInsQuery = "
  SELECT COUNT(*) AS check_ins
  FROM bookings
  WHERE check_in_date = CURDATE()
    AND status = 'confirmed'
";

// 4. Check-Outs (today, confirmed)
$checkOutsQuery = "
  SELECT COUNT(*) AS check_outs
  FROM bookings
  WHERE check_out_date = CURDATE()
    AND status = 'confirmed'
";

// 5. Room Occupancy (rooms currently occupied)
$roomOccupancyQuery = "
  SELECT COUNT(*) AS occupied_rooms
  FROM bookings
  WHERE status = 'confirmed'
    AND check_in_date <= CURDATE()
    AND check_out_date > CURDATE()
";

// 6. Total Rooms
$totalRoomsQuery = "SELECT COUNT(*) AS total_rooms FROM rooms";

// 7. Bookings Per Month
$bookingsPerMonthQuery = "
  SELECT MONTHNAME(check_in_date) AS month, COUNT(*) AS count
  FROM bookings
  WHERE status = 'confirmed'
  GROUP BY MONTH(check_in_date)
  ORDER BY MONTH(check_in_date)
";

// 8. Bookings Per Room Type
$bookingsPerRoomTypeQuery = "
  SELECT rooms.room_type, COUNT(*) AS count
  FROM bookings
  JOIN rooms ON bookings.room_id = rooms.room_id
  GROUP BY rooms.room_type
";

// 9. Average Length of Stay
$avgLengthOfStayQuery = "
  SELECT AVG(DATEDIFF(check_out_date, check_in_date)) AS avg_stay
  FROM bookings
  WHERE status = 'confirmed'
";

// 10. Monthly Revenue (line chart)
$monthlyRevenueQuery = "
  SELECT DATE_FORMAT(check_in_date, '%b %Y') AS month_year,
         SUM(DATEDIFF(check_out_date, check_in_date) * rooms.room_rate) AS monthly_revenue
  FROM bookings
  JOIN rooms ON bookings.room_id = rooms.room_id
  WHERE bookings.status = 'confirmed'
  GROUP BY YEAR(check_in_date), MONTH(check_in_date)
  ORDER BY YEAR(check_in_date), MONTH(check_in_date)
";

// Execute queries
$totalRevenueResult  = $conn->query($totalRevenueQuery)->fetch_assoc();
$totalBookingsResult = $conn->query($totalBookingsQuery)->fetch_assoc();
$checkInsResult      = $conn->query($checkInsQuery)->fetch_assoc();
$checkOutsResult     = $conn->query($checkOutsQuery)->fetch_assoc();
$roomOccupancyResult = $conn->query($roomOccupancyQuery)->fetch_assoc();
$totalRoomsResult    = $conn->query($totalRoomsQuery)->fetch_assoc();

// Optional queries
$avgLengthOfStayResult  = $conn->query($avgLengthOfStayQuery)->fetch_assoc();
$monthlyRevenueResult   = $conn->query($monthlyRevenueQuery);
$bookingsPerMonthResult = $conn->query($bookingsPerMonthQuery);
$bookingsPerRoomTypeRes = $conn->query($bookingsPerRoomTypeQuery);

// Fetch all bookings (for the table)
$allBookingsQuery = "
  SELECT bookings.*, rooms.room_type, rooms.room_number
  FROM bookings
  JOIN rooms ON bookings.room_id = rooms.room_id
";
$allBookingsResult = $conn->query($allBookingsQuery);

// Fetch pending bookings
$pendingBookingsQuery  = "SELECT * FROM bookings WHERE status = 'pending'";
$pendingBookingsResult = $conn->query($pendingBookingsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Dashboard - Paradise Resort</title>
  
  <!-- Link to your improved CSS file -->
  <link rel="stylesheet" href="../css/adminstyle.css">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet"
  >
  
  <!-- Font Awesome Icons -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    rel="stylesheet"
  >
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar">
    <div class="nav-title">Paradise Resort</div>
    <div class="nav-links">
      <a href="accountpage.php" class="nav-link">Account</a>
    </div>
  </nav>

  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>Admin</h2>
      </div>
      <ul class="sidebar-menu">
        <li class="active"><a href="#dashboard-tab">Dashboard</a></li>
        <li><a href="#bookings-tab">Bookings</a></li>
        <li><a href="#guests-tab">Guests</a></li>
        <li><a href="#calendar-tab">Calendar</a></li>
        <li><a href="#analytics-tab">Analytics</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      
      <!-- DARK THEMED DASHBOARD TAB -->
      <div id="dashboard-tab" class="tab-pane active dark-dashboard-container">
        
        <!-- Top Row of Stat Cards -->
        <div class="stats-row">
          <!-- Total Revenue Card -->
          <div class="stat-card stat-revenue">
            <div class="stat-icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Total Revenue</p>
              <p class="stat-value">
                $<?php echo number_format($totalRevenueResult['total_revenue'] ?? 0, 2); ?>
              </p>
            </div>
          </div>

          <!-- Total Bookings Card -->
          <div class="stat-card stat-bookings">
            <div class="stat-icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Total Bookings</p>
              <p class="stat-value">
                <?php echo $totalBookingsResult['total_bookings'] ?? 0; ?>
              </p>
            </div>
          </div>

          <!-- Check-Ins Card -->
          <div class="stat-card stat-checkin">
            <div class="stat-icon">
              <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Check-Ins</p>
              <p class="stat-value">
                <?php echo $checkInsResult['check_ins'] ?? 0; ?>
              </p>
            </div>
          </div>

          <!-- Check-Outs Card -->
          <div class="stat-card stat-checkout">
            <div class="stat-icon">
              <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Check-Outs</p>
              <p class="stat-value">
                <?php echo $checkOutsResult['check_outs'] ?? 0; ?>
              </p>
            </div>
          </div>

          <!-- Room Occupancy Card -->
          <div class="stat-card stat-occupancy">
            <div class="stat-icon">
              <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Room Occupancy</p>
              <p class="stat-value">
                <?php 
                  echo ($roomOccupancyResult['occupied_rooms'] ?? 0) 
                    . ' / ' 
                    . ($totalRoomsResult['total_rooms'] ?? 0); 
                ?>
              </p>
            </div>
          </div>

          <!-- Avg. Length of Stay Card -->
          <div class="stat-card stat-avgstay">
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Avg. Stay</p>
              <p class="stat-value">
                <?php 
                  $avgStay = $avgLengthOfStayResult['avg_stay'] ?? 0;
                  echo number_format($avgStay, 1) . ' nights';
                ?>
              </p>
            </div>
          </div>
        </div> <!-- .stats-row -->

        <!-- Additional Stats Row (NEW) -->
        <div class="stats-row">
          <!-- Monthly Revenue Card (this month) -->
          <div class="stat-card stat-monthly-revenue">
            <div class="stat-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Monthly Revenue</p>
              <p class="stat-value">
                $<?php echo number_format($thisMonthsRevenue, 2); ?>
              </p>
            </div>
          </div>

          <!-- Occupancy Rate Card -->
          <div class="stat-card stat-occupancy-rate">
            <div class="stat-icon">
              <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Occupancy Rate</p>
              <p class="stat-value">
                <?php echo number_format($occupancyRate, 1) . '%'; ?>
              </p>
            </div>
          </div>

          <!-- ADR Card -->
          <div class="stat-card stat-adr">
            <div class="stat-icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">Avg Daily Rate (ADR)</p>
              <p class="stat-value">
                $<?php echo number_format($ADR, 2); ?>
              </p>
            </div>
          </div>

          <!-- RevPAR Card -->
          <div class="stat-card stat-revpar">
            <div class="stat-icon">
              <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
              <p class="stat-title">RevPAR</p>
              <p class="stat-value">
                $<?php echo number_format($RevPAR, 2); ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
          <!-- Bookings Per Month -->
          <div class="chart-card bookings-month">
            <h3>Bookings Per Month</h3>
            <canvas id="bookingsPerMonthChart" class="chart-bookings-month"></canvas>
          </div>

          <!-- Bookings Per Room Type -->
          <div class="chart-card bookings-room">
            <h3>Bookings Per Room Type</h3>
            <canvas id="bookingsPerRoomTypeChart" class="chart-bookings-room"></canvas>
          </div>
        </div>

        <!-- Monthly Revenue Row -->
        <div class="charts-row">
          <div class="chart-card monthly-revenue">
            <h3>Monthly Revenue</h3>
            <canvas id="monthlyRevenueChart" class="chart-monthly-revenue"></canvas>
          </div>
        </div>

        <!-- NEW: Additional Charts Row -->
        <div class="charts-row">
          <!-- Occupancy Over Time -->
          <div class="occupancy-over-time">
            <h3>Occupancy Over Time</h3>
            <div class="chart-card">
              <canvas id="occupancyOverTimeChart"></canvas>
            </div>
          </div>

          <!-- Cancellations vs Confirmations -->
          <div class="canc-conf-chart">
            <h3>Cancellations vs. Confirmations</h3>
            <div class="chart-card">
              <canvas id="cancConfChart"></canvas>
            </div>
          </div>
        </div>

      </div>
      
      <!-- Bookings Tab -->
      <div id="bookings-tab" class="tab-pane">
        <table id="bookings-table">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>User ID</th>
              <th>Room Type</th>
              <th>Room Number</th>
              <th>Check-in Date</th>
              <th>Check-out Date</th>
              <th>Guests</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($booking = $allBookingsResult->fetch_assoc()): ?>
            <tr>
              <td><?php echo $booking['booking_id']; ?></td>
              <td><?php echo $booking['user_id']; ?></td>
              <td><?php echo $booking['room_type']; ?></td>
              <td><?php echo $booking['room_number']; ?></td>
              <td><?php echo $booking['check_in_date']; ?></td>
              <td><?php echo $booking['check_out_date']; ?></td>
              <td><?php echo $booking['guests']; ?></td>
              <td>
                <form action="../controller/update_booking_status.php" method="POST">
                  <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                  <input type="hidden" name="room_id" value="<?php echo $booking['room_id']; ?>">
                  <select name="status" onchange="this.form.submit()">
                    <option value="Confirmed"  <?php echo $booking['status'] === 'Confirmed'  ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Pending"    <?php echo $booking['status'] === 'Pending'    ? 'selected' : ''; ?>>Pending</option>
                    <option value="Cancelled" <?php echo $booking['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                  </select>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Guests Tab -->
      <div id="guests-tab" class="tab-pane">
        <h2>Guests</h2>
        <p>Coming soon...</p>
      </div>

      <!-- Calendar Tab -->
      <div id="calendar-tab" class="tab-pane">
        <h2>Calendar</h2>
        <p>Coming soon...</p>
      </div>

      <!-- Analytics Tab -->
      <div id="analytics-tab" class="tab-pane">
        <h2>Analytics</h2>
        <p>Coming soon...</p>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Sidebar Tab Functionality
    document.querySelectorAll('.sidebar-menu li a').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.tab-pane').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
        document.querySelector(this.getAttribute('href')).classList.add('active');
        this.parentElement.classList.add('active');
      });
    });

    // Bookings Per Month (Bar Chart)
    const bookingsPerMonthCtx = document.getElementById('bookingsPerMonthChart').getContext('2d');
    const bookingsPerMonthData = <?php echo json_encode($bookingsPerMonthResult->fetch_all(MYSQLI_ASSOC)); ?>;
    const allMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const bookingsPerMonthCounts = allMonths.map(month => {
      const monthData = bookingsPerMonthData.find(data => data.month.substring(0, 3) === month);
      return monthData ? monthData.count : 0;
    });

    new Chart(bookingsPerMonthCtx, {
      type: 'bar',
      data: {
        labels: allMonths,
        datasets: [{
          label: 'Bookings Per Month',
          data: bookingsPerMonthCounts,
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false, // Disable aspect ratio to allow full width
        plugins: {
          legend: { display: false } // Hide legend to save space
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 10 }
          }
        }
      }
    });

    // Bookings Per Room Type (Pie Chart)
    const bookingsPerRoomTypeCtx = document.getElementById('bookingsPerRoomTypeChart').getContext('2d');
    const bookingsPerRoomTypeData = <?php echo json_encode($bookingsPerRoomTypeRes->fetch_all(MYSQLI_ASSOC)); ?>;
    const bookingsPerRoomTypeLabels = bookingsPerRoomTypeData.map(item => item.room_type);
    const bookingsPerRoomTypeCounts = bookingsPerRoomTypeData.map(item => item.count);

    new Chart(bookingsPerRoomTypeCtx, {
      type: 'pie',
      data: {
        labels: bookingsPerRoomTypeLabels,
        datasets: [{
          label: 'Bookings Per Room Type',
          data: bookingsPerRoomTypeCounts,
          backgroundColor: [
            'rgba(255, 99, 132, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)'
          ],
          borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });

    // Monthly Revenue (Line Chart)
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    const monthlyRevenueData = <?php echo ($monthlyRevenueResult) ? json_encode($monthlyRevenueResult->fetch_all(MYSQLI_ASSOC)) : json_encode([]); ?>;
    const monthlyRevenueLabels = monthlyRevenueData.map(item => item.month_year);
    const monthlyRevenueValues = monthlyRevenueData.map(item => item.monthly_revenue);

    new Chart(monthlyRevenueCtx, {
      type: 'line',
      data: {
        labels: monthlyRevenueLabels,
        datasets: [{
          label: 'Monthly Revenue',
          data: monthlyRevenueValues,
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: false,
            ticks: {
              callback: function(value) {
                return '$' + value;
              }
            }
          }
        }
      }
    });

    // Occupancy Over Time (Line Chart) - using placeholders
    const occupancyOverTimeCtx = document.getElementById('occupancyOverTimeChart').getContext('2d');
    const occupancyLabels = <?php echo json_encode($occupancyDays); ?>;
    const occupancyValues = <?php echo json_encode($occupancyData); ?>;

    new Chart(occupancyOverTimeCtx, {
      type: 'line',
      data: {
        labels: occupancyLabels,
        datasets: [{
          label: 'Daily Occupancy (%)',
          data: occupancyValues,
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: function(value) {
                return value + '%';
              }
            }
          }
        }
      }
    });

    // Cancellations vs Confirmations (Bar Chart) - using placeholders
    const cancConfCtx = document.getElementById('cancConfChart').getContext('2d');
    const cancConfLabels = <?php echo json_encode($cancConfDays); ?>;
    const cancData = <?php echo json_encode($cancellationsData); ?>;
    const confData = <?php echo json_encode($confirmationsData); ?>;

    new Chart(cancConfCtx, {
      type: 'bar',
      data: {
        labels: cancConfLabels,
        datasets: [
          {
            label: 'Cancellations',
            data: cancData,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
          },
          {
            label: 'Confirmations',
            data: confData,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 5 }
          }
        }
      }
    });

    // Ensure table rows are a multiple of 10
    const table = document.getElementById('bookings-table')?.getElementsByTagName('tbody')[0];
    if (table) {
      const rows = table.getElementsByTagName('tr').length;
      const rowsToAdd = 10 - (rows % 10);
      if (rowsToAdd < 10 && rows !== 0) {
        for (let i = 0; i < rowsToAdd; i++) {
          const newRow = table.insertRow();
          for (let j = 0; j < 8; j++) {
            const newCell = newRow.insertCell();
            newCell.innerHTML = '&nbsp;';
          }
        }
      }
    }
  </script>
</body>
</html>
