<?php
session_start();
include 'db_connect.php';
$page_title = "Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $query = "SELECT * FROM admin WHERE username = ? LIMIT 1";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($result) === 1) {
    $admin = mysqli_fetch_assoc($result);

    if (password_verify($password, $admin['password_hash'])) {
      $_SESSION['admin'] = true;
      $_SESSION['admin_id'] = $admin['admin_id'];
      $_SESSION['admin_username'] = $admin['username'];
      header("Location: dashboard.php");
      exit;
    } else {
      $error = "Invalid password.";
    }
  } else {
    $error = "Admin not found.";
  }
}

$extra_css = '
<style>
  body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    background-color: var(--bg-color);
  }
  .login-box {
    width: 400px;
    background-color: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 8px 24px var(--shadow);
    padding: 2rem;
    animation: fadeIn 0.5s ease-in-out;
  }
  .login-box h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    font-weight: 600;
    text-align: center;
  }
  .form-label {
    color: var(--text-color);
    font-weight: 500;
  }
  .form-control {
    background-color: var(--secondary-color);
    border: 1px solid var(--accent-color);
    color: var(--text-color);
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
  }
  .form-control:focus {
    background-color: var(--secondary-color);
    border-color: var(--primary-color);
    color: var(--text-color);
    box-shadow: 0 0 0 2px rgba(247, 129, 102, 0.25);
  }
  .btn-primary {
    background-color: var(--primary-color);
    border: none;
    font-weight: 600;
    padding: 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
  }
  .btn-primary:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .brand-logo {
    display: block;
    text-align: center;
    margin-bottom: 1rem;
    font-size: 2.5rem;
    color: var(--primary-color);
  }
</style>
';

include 'includes/header.php';
?>

<div class="login-box">
  <div class="brand-logo">
    <i class="fas fa-cogs"></i>
  </div>
  <h3>Resort Admin Login</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required autofocus>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
