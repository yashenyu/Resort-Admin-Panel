<?php 
include 'db_connect.php'; 
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
$page_title = "Manage Users";

// Add CSS file reference
$extra_css = '<link href="assets/css/users.css" rel="stylesheet">';

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$allowed_columns = ['first_name', 'last_name', 'email', 'contact_number', 'gender', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Get filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$gender_filter = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : '';

// Build the query
$query = "SELECT * FROM users WHERE 1=1";

if ($search) {
    $query .= " AND (first_name LIKE '%$search%' 
                OR last_name LIKE '%$search%' 
                OR email LIKE '%$search%'
                OR contact_number LIKE '%$search%')";
}

if ($gender_filter) {
    $query .= " AND gender = '$gender_filter'";
}

$query .= " ORDER BY $sort_column $sort_order";

$result = mysqli_query($conn, $query);

// Get user statistics
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$activeUsers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id) as count 
    FROM bookings 
    WHERE status IN ('Confirmed', 'Completed') 
    AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
"))['count'];
$newUsers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
"))['count'];

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Users</h2>
</div>

<!-- User Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">Total Users</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($totalUsers); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">Active Users (30 Days)</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($activeUsers); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-label text-secondary-light">New Users (30 Days)</div>
                    <div class="stats-value h4 mb-0 text-white"><?php echo number_format($newUsers); ?></div>
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
                       placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="col-md-4">
            <select name="gender" class="form-select bg-dark text-light border-dark">
                <option value="">All Gender</option>
                <option value="Male" <?php echo $gender_filter === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $gender_filter === 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
        
        <div class="col-md-2 text-end">
            <div class="input-group justify-content-end">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search || $gender_filter): ?>
                    <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="stats-card">
    <div class="table-responsive">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th class="text-light">
                        <a href="?sort=first_name&order=<?php echo $sort_column === 'first_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $gender_filter ? "&gender=$gender_filter" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Name
                            <?php if ($sort_column === 'first_name'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=email&order=<?php echo $sort_column === 'email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $gender_filter ? "&gender=$gender_filter" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Email
                            <?php if ($sort_column === 'email'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=contact_number&order=<?php echo $sort_column === 'contact_number' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $gender_filter ? "&gender=$gender_filter" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Contact
                            <?php if ($sort_column === 'contact_number'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=gender&order=<?php echo $sort_column === 'gender' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $gender_filter ? "&gender=$gender_filter" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Gender
                            <?php if ($sort_column === 'gender'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light">
                        <a href="?sort=created_at&order=<?php echo $sort_column === 'created_at' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $gender_filter ? "&gender=$gender_filter" : ''; ?>" 
                           class="text-light text-decoration-none">
                            Registered
                            <?php if ($sort_column === 'created_at'): ?>
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-light text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td class="text-light">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </td>
                    <td class="text-light"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="text-light"><?php echo htmlspecialchars($user['contact_number']); ?></td>
                    <td class="text-light">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['gender']); ?></span>
                    </td>
                    <td class="text-light">
                        <div><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                        <div class="small text-muted"><?php echo date('h:i A', strtotime($user['created_at'])); ?></div>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                onclick="viewBookings(<?php echo $user['user_id']; ?>)">
                            <i class="fas fa-history"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-user-slash mb-2 h3"></i>
                        <div>No users found</div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-start align-items-center mt-4">
        <div class="text-light">
            Total <?php echo mysqli_num_rows($result); ?> entries
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewBookings(userId) {
    window.location.href = `bookings.php?user_id=${userId}`;
}

function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', 'delete');

    fetch('modules/users/process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the user');
    });
}
</script>

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

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

.form-select option {
    background-color: #212529;
    color: #e9ecef;
    padding: 8px;
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

/* Badge */
.badge.bg-secondary {
    background-color: #495057 !important;
    color: #e9ecef;
}
</style>

<?php include 'includes/footer.php'; ?>

