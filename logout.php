<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
</head>
<body>
    <h3>Are you sure you want to log out?</h3>
    <form method="POST">
        <button type="submit">Yes, Logout</button>
        <a href="dashboard.php">Cancel</a>
    </form>
</body>
</html>
