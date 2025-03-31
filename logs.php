<?php 
include 'db_connect.php'; 
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
$page_title = "Audit Logs";

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$allowed_columns = ['timestamp', 'action', 'first_name', 'last_name'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'timestamp';
}

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

// Build the query
$base_query = "
    SELECT l.*, u.first_name, u.last_name 
    FROM audit_logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    WHERE 1=1";

if ($search) {
    $base_query .= " AND (u.first_name LIKE '%$search%' 
                OR u.last_name LIKE '%$search%'
                OR l.action LIKE '%$search%')";
}

if ($date_from) {
    $base_query .= " AND DATE(l.timestamp) >= '$date_from'";
}

if ($date_to) {
    $base_query .= " AND DATE(l.timestamp) <= '$date_to'";
}

// Get total filtered records
$total_query = "SELECT COUNT(*) as count FROM (" . $base_query . ") as filtered_logs";
$total_filtered = mysqli_fetch_assoc(mysqli_query($conn, $total_query))['count'];

// Add sorting and pagination to the main query
$query = $base_query . " ORDER BY $sort_column $sort_order LIMIT $offset, $per_page";

$result = mysqli_query($conn, $query);

// Get log statistics
$totalLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM audit_logs"))['count'];
$todayLogs = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM audit_logs 
    WHERE DATE(timestamp) = CURDATE()
"))['count'];
$weekLogs = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM audit_logs 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
"))['count'];

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title mb-4">
    <h2 class="mb-2" style="color: #e76f51;">Audit Logs</h2>
</div>

<!-- Log Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-history"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">Total Logs</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($totalLogs); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">Today's Activities</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($todayLogs); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">Last 7 Days</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($weekLogs); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="stats-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-dark border-dark">
                    <i class="fas fa-search text-light"></i>
                </span>
                <input type="text" name="search" class="form-control bg-dark text-light border-dark" 
                       placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control bg-dark text-light border-dark" 
                   value="<?php echo $date_from; ?>" placeholder="From Date">
        </div>
        
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control bg-dark text-light border-dark" 
                   value="<?php echo $date_to; ?>" placeholder="To Date">
        </div>
        
        <div class="col-md-2 text-end">
            <div class="input-group justify-content-end">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search || $date_from || $date_to): ?>
                    <a href="logs.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="stats-card">
    <div class="table-responsive">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th class="text-light">
                        <a href="?sort=timestamp&order=<?php echo $sort_column === 'timestamp' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Timestamp
                            <?php if ($sort_column === 'timestamp'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=first_name&order=<?php echo $sort_column === 'first_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?>" 
                           class="text-light text-decoration-none">
                            User
                            <?php if ($sort_column === 'first_name'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=action&order=<?php echo $sort_column === 'action' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Action
                            <?php if ($sort_column === 'action'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td class="text-light">
                        <div><?php echo date('M d, Y', strtotime($log['timestamp'])); ?></div>
                        <div class="small text-muted"><?php echo date('h:i A', strtotime($log['timestamp'])); ?></div>
                    </td>
                    <td class="text-light">
                        <?php 
                        if ($log['first_name'] || $log['last_name']) {
                            echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
                        } else {
                            echo '<span class="text-muted">Admin</span>';
                        }
                        ?>
                    </td>
                    <td class="text-light"><?php echo htmlspecialchars($log['action']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                        <i class="fas fa-history mb-2 h3"></i>
                        <div>No logs found</div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-light">
            Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $per_page, $total_filtered); ?> of <?php echo $total_filtered; ?> entries
        </div>
        <?php if ($total_filtered > $per_page): ?>
        <nav aria-label="Log navigation">
            <ul class="pagination mb-0">
                <?php
                $total_pages = ceil($total_filtered / $per_page);
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                // Previous button
                if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link bg-dark text-light border-secondary" href="?page=<?php echo ($page - 1); ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?><?php echo $sort_column ? "&sort=$sort_column" : ''; ?><?php echo $sort_order ? "&order=$sort_order" : ''; ?>">
                            Previous
                        </a>
                    </li>
                <?php endif;

                // Page numbers
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link bg-dark text-light border-secondary <?php echo $i === $page ? 'bg-secondary' : ''; ?>" href="?page=<?php echo $i; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?><?php echo $sort_column ? "&sort=$sort_column" : ''; ?><?php echo $sort_order ? "&order=$sort_order" : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor;

                // Next button
                if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link bg-dark text-light border-secondary" href="?page=<?php echo ($page + 1); ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $date_from ? "&date_from=$date_from" : ''; ?><?php echo $date_to ? "&date_to=$date_to" : ''; ?><?php echo $sort_column ? "&sort=$sort_column" : ''; ?><?php echo $sort_order ? "&order=$sort_order" : ''; ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Title */
.page-title {
    position: relative;
    display: inline-block;
}

.page-title h2 {
    color: #e76f51;
}

.title-line {
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #e76f51 0%, #e76f51 100%);
    border-radius: 2px;
}

/* Table Styles */
.table {
    --bs-table-color: #e9ecef;
    --bs-table-bg: #212529;
    --bs-table-border-color: #373b3e;
    --bs-table-striped-bg: #2c3034;
    --bs-table-striped-color: #e9ecef;
    --bs-table-hover-bg: #323539;
    --bs-table-hover-color: #ffffff;
}

.table th {
    white-space: nowrap;
    color: #ffffff !important;
    font-weight: 500;
}

.table td {
    vertical-align: middle;
    color: #e9ecef !important;
}

.table td .text-muted {
    color: #adb5bd !important;
}

/* Form Controls */
.form-control, .form-select {
    color: #e9ecef;
    background-color: #2b3035;
    border-color: #373b3e;
}

.form-control:focus, .form-select:focus {
    background-color: #2b3035;
    border-color: #495057;
    color: #e9ecef;
    box-shadow: none;
}

.form-control::placeholder {
    color: #6c757d;
}

/* Stats Card */
.stats-card {
    background-color: #212529;
    border-radius: 0.5rem;
    padding: 1.5rem;
    border: 1px solid #373b3e;
}

.stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.text-secondary-light {
    color: #adb5bd !important;
}

/* Button Styles */
.btn-secondary {
    background-color: #495057;
    border-color: #495057;
    color: #ffffff;
}

.btn-outline-secondary {
    border-color: #495057;
    color: #e9ecef;
}

.btn-outline-secondary:hover {
    background-color: #495057;
    color: #ffffff;
}

/* Input Group */
.input-group-text {
    color: #e9ecef;
}

/* Pagination Styles */
.pagination .page-link {
    padding: 0.375rem 0.75rem;
}

.pagination .page-link:hover {
    background-color: #495057;
    border-color: #495057;
}

.pagination .active .page-link {
    background-color: #495057;
    border-color: #495057;
}
</style>

<?php include 'includes/footer.php'; ?>

