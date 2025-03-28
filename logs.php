<?php include 'db_connect.php'; session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}?>
<!DOCTYPE html>
<html>
<head>
  <title>Audit Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid p-4">
    <h2>Audit Logs</h2>
    <table class="table table-striped table-bordered mt-4">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Action</th>
          <th>Date & Time</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "
          SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
          FROM audit_logs a
          JOIN users u ON a.user_id = u.user_id
          ORDER BY a.timestamp DESC
          LIMIT 100
        ";

        $result = mysqli_query($conn, $query);
        $count = 1;

        while ($row = mysqli_fetch_assoc($result)) {
          echo "<tr>
                  <td>{$count}</td>
                  <td>{$row['user_name']}</td>
                  <td>{$row['action']}</td>
                  <td>" . date("F j, Y - g:i A", strtotime($row['timestamp'])) . "</td>
                </tr>";
          $count++;
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
