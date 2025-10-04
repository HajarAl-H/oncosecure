<?php 
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();

// --- Force password change on first login ---
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT force_password_change FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && $user['force_password_change'] == 0) {
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'change_password.php') {
            header("Location: /doctor/change_password.php");
            exit;
        }
    }
}

// --- Fetch patients ---
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Doctor Dashboard</h2>

<!-- View profile button -->
<a href="profile.php" class="btn btn-primary mb-3">View My Profile</a>

<h4>Patients</h4>
<table class="table">
  <thead>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, u.name, u.email, p.age, p.created_at
        FROM patients p
        JOIN users u ON p.user_id = u.id
        JOIN appointments a ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $patients = $stmt->fetchAll();

    foreach($patients as $p): ?>
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
