<?php
/* if (isset($_COOKIE['logout_message'])) {
    echo "<script>alert('Logged out successfully');</script>";
    setcookie('logout_message', '', time()-3600, '/'); // Delete cookie
} */
?> 

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Paradise Resort</title>
    <link rel="stylesheet" href="../css/loginstyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <!-- Login Section -->
    <section class="login">
      <div class="center-login">
        <div class="login-card">
          <h2>Login to Your Admin Account</h2>
          <form action="../controller/logincontroller.php" method="POST">
            <label for="email">Email Address</label>
            <div class="password-container">
                <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                required
                />
            </div>

            <label for="password">Password</label>
            <div class="password-container">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
              />
              <i class="fas fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
          </form>
        </div>
      </div>
    </section>

    <script src="../js/loginscript.js"></script>
  </body>
</html>