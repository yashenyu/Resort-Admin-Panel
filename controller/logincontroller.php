<?php
session_start();
require '../database/connection.php'; // Ensure this file correctly initializes $conn

if (isset($_SESSION['user_id'])) {
    header("Location: ../view/dashboard.php"); // Redirect to dashboard if already logged in
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $query = "SELECT user_id, first_name, last_name, password_hash FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    header("Location: ../view/dashboard.php"); // Redirect to dashboard
                    exit();
                }
            }
            $stmt->close();
        }
        // Redirect with an error message
        header("Location: ../view/login.php?error=Invalid email or password");
        exit();
    } else {
        header("Location: ../view/login.php?error=Please fill in both fields");
        exit();
    }
}
?>