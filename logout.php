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
    <style>
      body {
        background-color: #f8f9fa;
      }
      .login-box {
        max-width: 400px;
        margin: 100px auto;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      }
    </style>
</head>
<body>
    <h3>Are you sure you want to log out?</h3>
    <form method="POST">
        <button type="submit">Yes, Logout</button>
        <a href="dashboard.php">Cancel</a>
    </form>
</body>
</html>
