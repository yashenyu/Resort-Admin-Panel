<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Registered Users</h2>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">User updated successfully.</div>
    <?php endif; ?>

    <table class="table table-bordered mt-4">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Gender</th>
          <th>Contact</th>
          <th>Registered On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM users ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);

        while ($user = mysqli_fetch_assoc($result)) {
          $fullName = $user['first_name'] . ' ' . $user['last_name'];
          $created = date("M d, Y", strtotime($user['created_at']));

          echo "<tr>
                  <td>{$user['user_id']}</td>
                  <td>$fullName</td>
                  <td>{$user['email']}</td>
                  <td>{$user['gender']}</td>
                  <td>{$user['contact_number']}</td>
                  <td>$created</td>
                  <td>
                    <a href='edit_user.php?id={$user['user_id']}' class='btn btn-sm btn-warning'>Edit</a>
                    <a href='deactivate_user.php?id={$user['user_id']}' class='btn btn-sm btn-danger'>Deactivate</a>
                  </td>
                </tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
