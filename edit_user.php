<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

include 'db_connect.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
  die("Missing user ID.");
}

$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user = mysqli_fetch_assoc($result);
if (!$user) {
  die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = $_POST['first_name'];
  $last = $_POST['last_name'];
  $email = $_POST['email'];
  $contact = $_POST['contact_number'];
  $gender = $_POST['gender'];
  $newPassword = $_POST['password'];

  // If password is entered, validate and hash
  if (!empty($newPassword)) {
    if (strlen($newPassword) < 8) {
      $error = "Password must be at least 8 characters long.";
    } else {
      $hash = password_hash($newPassword, PASSWORD_DEFAULT);
      $update = "
        UPDATE users SET
          first_name = '$first',
          last_name = '$last',
          email = '$email',
          contact_number = '$contact',
          gender = '$gender',
          password_hash = '$hash'
        WHERE user_id = $user_id
      ";
    }
  } else {
    // No password change
    $update = "
      UPDATE users SET
        first_name = '$first',
        last_name = '$last',
        email = '$email',
        contact_number = '$contact',
        gender = '$gender'
      WHERE user_id = $user_id
    ";
  }

  if (isset($update) && mysqli_query($conn, $update)) {
    header("Location: users.php?updated=1");
    exit;
  } else if (!isset($error)) {
    $error = "Error: " . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Edit User: <?= $user['first_name'] . ' ' . $user['last_name'] ?></h2>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" value="<?= $user['first_name'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= $user['last_name'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Contact Number</label>
      <input type="text" name="contact_number" class="form-control" value="<?= $user['contact_number'] ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">New Password (optional)</label>
      <input type="password" name="password" class="form-control">
      <small class="text-muted">Leave blank to keep current password.</small>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="users.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
