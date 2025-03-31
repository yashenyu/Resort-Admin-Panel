<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Resort Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/logout.css" rel="stylesheet">
</head>
<body>
    <div class="logout-box">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h2 class="logout-title">Are you sure you want to log out?</h2>
        <div class="btn-group">
            <form method="POST" style="display: inline;">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Yes, Logout
                </button>
            </form>
            <a href="dashboard.php" class="btn btn-dark">
                <i class="fas fa-times me-2"></i> Cancel
            </a>
        </div>
    </div>
</body>
</html>
