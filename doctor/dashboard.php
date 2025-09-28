<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();
require_once __DIR__ . '/../includes/header.php';

// list patients assigned or all patients (simple)
$stmt = $pdo->prepare("SELECT p.id, u.name, u.email, p.age, p.created_at FROM patients p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$stmt->execute();
$patients = $stmt->fetchAll();
?>
<h2>Doctor Dashboard</h2>
<h4>Patients</h4>
<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($patients as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['email']) ?></td>
        <td><?= $p['age'] ?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="patient_detail.php?id=<?= $p['id'] ?>">Details</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>