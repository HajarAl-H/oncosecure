<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();
require_once __DIR__ . '/../includes/header.php';

// list doctors
$stmt = $pdo->prepare("SELECT id,name,email,created_at FROM users WHERE role = 'doctor' ORDER BY created_at DESC");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>
<h2>Admin Dashboard</h2>
<a class="btn btn-sm btn-success mb-3" href="add_doctor.php">Add Doctor</a>
<h4>Doctors</h4>
<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th></tr></thead>
  <tbody>
    <?php foreach($doctors as $d): ?>
      <tr>
        <td><?= $d['id'] ?></td>
        <td><?= htmlspecialchars($d['name']) ?></td>
        <td><?= htmlspecialchars($d['email']) ?></td>
        <td><?= $d['created_at'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h4>Security Logs</h4>
<a class="btn btn-sm btn-outline-secondary mb-2" href="logs.php">View Logs</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>