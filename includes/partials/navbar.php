<?php
/**
 * Navbar/Sidebar Partial
 * Contains the main navigation menu
 */
?>
<div class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="brand text-decoration-none">
            <i class="fas fa-cogs"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>
    </div>

    <div class="sidebar-section">
        <h6 class="section-title">GENERAL</h6>
        <ul class="nav flex-column sidebar-links">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="bookings.php" class="nav-link <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt me-2"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a href="rooms.php" class="nav-link <?php echo $current_page === 'rooms.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed me-2"></i> Rooms
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a href="logs.php" class="nav-link <?php echo $current_page === 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt me-2"></i> Audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a href="analytics.php" class="nav-link <?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie me-2"></i> Analytics
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section mt-auto">
        <h6 class="section-title">ACCOUNT</h6>
        <ul class="nav flex-column sidebar-links">
            <li class="nav-item">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
