<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();
require_once __DIR__ . '/../includes/header.php';

// list doctors
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, address, specialization, experience, certificates, created_at 
    FROM users 
    WHERE role = 'doctor' 
    ORDER BY created_at DESC
");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>
<h2>Admin Dashboard</h2>
<a class="btn btn-sm btn-primary mb-3" href="add_doctor.php">Add Doctor</a>

<h4>Doctors</h4>
<table class="table">
  <thead>
    <tr>
      <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th>
      <th>Specialization</th><th>Experience</th><th>Certificates</th>
      <th>Created</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($doctors as $d): ?>
      <tr>
        <td><?= $d['id'] ?></td>
        <td><?= htmlspecialchars($d['name']) ?></td>
        <td><?= htmlspecialchars($d['email']) ?></td>
        <td><?= htmlspecialchars($d['phone']) ?></td>
        <td><?= htmlspecialchars($d['address']) ?></td>
        <td><?= htmlspecialchars($d['specialization']) ?></td>
        <td><?= htmlspecialchars($d['experience']) ?></td>
        <td>
            <?php if ($d['certificates']): ?>
                <a href="/<?= htmlspecialchars($d['certificates']) ?>" target="_blank">View</a>
            <?php else: ?>
                No file
            <?php endif; ?>
        </td>
        <td><?= $d['created_at'] ?></td>
        <td>
            <a href="edit_doctor.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="delete_doctor.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this doctor?');">Delete</a>
            <a href="reset_password.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-info" onclick="return confirm('Reset password for this doctor?');">Reset Password</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h4>Security Logs</h4>
<a class="btn btn-sm btn-primary mb-2" href="logs.php">View Logs</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
