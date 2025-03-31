<?php 
include 'db_connect.php'; 
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
$page_title = "Manage Rooms";

// Fetch all rooms with their current status
$roomsQuery = "
    SELECT r.*, 
           COALESCE(b.current_bookings, 0) as current_bookings,
           COALESCE(b.future_bookings, 0) as future_bookings
    FROM rooms r
    LEFT JOIN (
        SELECT 
            room_id, 
            COUNT(*) as current_bookings,
            SUM(CASE WHEN check_out_date >= CURDATE() THEN 1 ELSE 0 END) as future_bookings
        FROM bookings 
        WHERE status = 'Confirmed' 
        GROUP BY room_id
    ) b ON r.room_id = b.room_id
    ORDER BY r.room_type, r.room_number";

$rooms = mysqli_query($conn, $roomsQuery);

// Group rooms by type
$roomsByType = [];
while($room = mysqli_fetch_assoc($rooms)) {
    if (!isset($roomsByType[$room['room_type']])) {
        $roomsByType[$room['room_type']] = [
            'rooms' => [],
            'total' => 0,
            'available' => 0
        ];
    }
    $roomsByType[$room['room_type']]['rooms'][] = $room;
    $roomsByType[$room['room_type']]['total']++;
    if ($room['status'] == 'Available') {
        $roomsByType[$room['room_type']]['available']++;
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h2>Rooms</h2>
</div>

<?php foreach($roomsByType as $roomType => $data): ?>
    <!-- Room Type Section -->
    <div class="room-type-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h4 mb-1"><?php echo htmlspecialchars($roomType); ?></h3>
                <div class="text-light opacity-75 small">
                    <i class="fas fa-bed me-1"></i>
                    <?php echo $data['available']; ?> of <?php echo $data['total']; ?> rooms available
                    (<?php echo round(($data['available'] / $data['total']) * 100); ?>% availability)
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach($data['rooms'] as $room): ?>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="room-card">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="room-number"><?php echo htmlspecialchars($room['room_number']); ?></div>
                            <select class="form-select form-select-sm status-select" 
                                    data-room-id="<?php echo $room['room_id']; ?>"
                                    data-has-bookings="<?php echo $room['future_bookings'] > 0 ? '1' : '0'; ?>"
                                    onchange="updateRoomStatus(<?php echo $room['room_id']; ?>, this.value)">
                                <option value="Available" <?php echo $room['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Booked" <?php echo $room['status'] == 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                <option value="Maintenance" <?php echo $room['status'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div class="room-info">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-money-bill me-2 text-success"></i>
                                <span>₱<?php echo number_format($room['room_rate'], 2); ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-check me-2 text-primary"></i>
                                <span><?php echo $room['current_bookings']; ?> bookings</span>
                                <?php if ($room['future_bookings'] > 0): ?>
                                    <span class="ms-2 badge bg-warning text-dark">
                                        <?php echo $room['future_bookings']; ?> upcoming
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<style>
/* Page Title */
.page-title {
    position: relative;
    display: inline-block;
}

.title-line {
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #e76f51 0%, #e76f51 100%);
    border-radius: 2px;
}

/* Room Section Styles */
.room-type-section {
    background-color: #2c3034;
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid #373b3e;
}

/* Room Card Styles */
.room-card {
    background-color: #212529;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #373b3e;
    color: #fff;
    height: 100%;
    font-size: 0.875rem;
}

.room-number {
    font-size: 1.1rem;
    color: #fff;
}

.room-info {
    color: #adb5bd;
    font-size: 0.8125rem;
}

.room-info i {
    width: 16px;
    text-align: center;
}

/* Status Select Styles */
.status-select {
    font-size: 0.75rem;
    min-width: 100px;
    background-color: #2c3034;
    border-color: #373b3e;
    color: #fff;
}

.status-select:focus {
    background-color: #2c3034;
    border-color: #495057;
    color: #fff;
    box-shadow: none;
}

.status-select option {
    background-color: #212529;
    color: #fff;
    padding: 4px 8px;
}

/* Form Select Custom Arrow */
.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    padding-right: 24px;
}
</style>

<script>
function updateRoomStatus(roomId, newStatus) {
    const formData = new FormData();
    formData.append('room_id', roomId);
    formData.append('status', newStatus);

    // Show warning if room has future bookings
    if (document.querySelector(`[data-room-id="${roomId}"]`).dataset.hasBookings === "1") {
        if (!confirm('Warning: This room has future bookings. Are you sure you want to change its status?')) {
            location.reload();
            return;
        }
    } else {
        if (!confirm('Are you sure you want to change this room status?')) {
            location.reload();
            return;
        }
    }

    fetch('modules/rooms/process.php', {
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

