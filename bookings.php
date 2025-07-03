<?php
include 'db_connect.php'; 
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
$page_title = "Manage Bookings";

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$room_type = isset($_GET['room_type']) ? mysqli_real_escape_string($conn, $_GET['room_type']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? mysqli_real_escape_string($conn, $_GET['order']) : 'DESC';

$where_clauses = [];
if ($search) {
    $where_clauses[] = "(b.booking_id LIKE '%$search%' 
                        OR u.first_name LIKE '%$search%' 
                        OR u.last_name LIKE '%$search%'
                        OR u.email LIKE '%$search%'
                        OR r.room_number LIKE '%$search%')";
}
if ($status) {
    $where_clauses[] = "b.status = '$status'";
}
if ($room_type) {
    $where_clauses[] = "r.room_type = '$room_type'";
}
if ($date_from) {
    $where_clauses[] = "b.check_in_date >= '$date_from'";
}
if ($date_to) {
    $where_clauses[] = "b.check_out_date <= '$date_to'";
}

$where_clause = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$room_types = mysqli_query($conn, "SELECT DISTINCT room_type FROM rooms ORDER BY room_type");

$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN rooms r ON b.room_id = r.room_id
    $where_clause
");
$total_records = mysqli_fetch_assoc($total_query)['total'];

$bookings = mysqli_query($conn, "
    SELECT 
        b.*, 
        u.first_name,
        u.last_name,
        u.email,
        r.room_number,
        r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN rooms r ON b.room_id = r.room_id
    $where_clause
    ORDER BY $sort $order
");

include 'includes/header.php';
?>

<!-- Page title -->
<style>
:root {
    --bs-body-bg: #0d1117 !important;
    --bs-body-color: #fff !important;
    --bs-dark: #0d1117 !important;
    --bs-dark-rgb: 13, 17, 23 !important;
}

html {
    background-color: #0d1117 !important;
}

* {
    transition: none !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bookings</h2>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Booking updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Search and Filters -->
<div class="stats-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search text-light"></i>
                </span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search bookings..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Confirmed" <?php echo $status == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="Completed" <?php echo $status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <select name="room_type" class="form-select">
                <option value="">All Room Types</option>
                <?php while($rt = mysqli_fetch_assoc($room_types)): ?>
                    <option value="<?php echo htmlspecialchars($rt['room_type']); ?>" 
                            <?php echo $room_type == $rt['room_type'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rt['room_type']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" 
                   placeholder="From Date" value="<?php echo $date_from; ?>">
        </div>
        
        <div class="col-md-3">
            <div class="input-group">
                <input type="date" name="date_to" class="form-control" 
                       placeholder="To Date" value="<?php echo $date_to; ?>">
                <button type="submit" class="btn btn-secondary px-4">Filter</button>
            </div>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="stats-card">
    <div class="table-responsive">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'booking_id', 'order' => $sort == 'booking_id' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            ID
                            <?php if ($sort == 'booking_id'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'first_name', 'order' => $sort == 'first_name' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Guest
                            <?php if ($sort == 'first_name'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'room_number', 'order' => $sort == 'room_number' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Room
                            <?php if ($sort == 'room_number'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'check_in_date', 'order' => $sort == 'check_in_date' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Check In
                            <?php if ($sort == 'check_in_date'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'check_out_date', 'order' => $sort == 'check_out_date' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Check Out
                            <?php if ($sort == 'check_out_date'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total_price', 'order' => $sort == 'total_price' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Total
                            <?php if ($sort == 'total_price'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>" 
                           class="text-light text-decoration-none">
                            Status
                            <?php if ($sort == 'status'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while($booking = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td class="text-light"><?php echo $booking['booking_id']; ?></td>
                        <td class="text-light">
                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                            <div class="small text-muted"><?php echo htmlspecialchars($booking['email']); ?></div>
                        </td>
                        <td class="text-light">
                            <?php echo htmlspecialchars($booking['room_number']); ?>
                            <div class="small text-muted"><?php echo htmlspecialchars($booking['room_type']); ?></div>
                        </td>
                        <td class="text-light"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                        <td class="text-light"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                        <td class="text-light">₱<?php echo number_format($booking['total_price'], 2); ?></td>
                        <td>
                            <select class="form-select form-select-sm" 
                                    onchange="updateStatus(<?php echo $booking['booking_id']; ?>, this.value)">
                                <option value="Pending" <?php echo $booking['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo $booking['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Completed" <?php echo $booking['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $booking['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-light">
            Total <?php echo $total_records; ?> entries
        </div>
    </div>
</div>

<script>
function updateStatus(bookingId, newStatus) {
    if (!confirm('Are you sure you want to change this booking status?')) {
        location.reload();
        return;
    }

    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('status', newStatus);

    fetch('modules/bookings/process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update status');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
        location.reload();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
