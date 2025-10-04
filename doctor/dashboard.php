<?php 
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();

$user_id = $_SESSION['user_id'] ?? null;

// --- Force password change on first login ---
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

// 📝 Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['new_status'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['approved','completed','cancelled'])) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$new_status, $appointment_id, $user_id]);
        flash_set('success', "Appointment #$appointment_id status updated to '$new_status'.");
    }
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2>👨‍⚕️ Doctor Dashboard</h2>

<!-- View profile button -->
<a href="profile.php" class="btn btn-primary mb-3">View My Profile</a>

<!-- 📅 Appointments Section -->
<h4 class="mt-4 mb-3">📅 Appointments Management</h4>

<?php if($msg = flash_get('success')): ?>
  <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>ID</th>
      <th>Patient</th>
      <th>Date</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $pdo->prepare("
        SELECT a.*, u.name AS patient_name, u.email AS patient_email
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll();

    foreach ($appointments as $a):
      $status = $a['status'];
    ?>
    <tr>
      <td><?= $a['id'] ?></td>
      <td>
        <?= htmlspecialchars($a['patient_name']) ?><br>
        <small><?= htmlspecialchars($a['patient_email']) ?></small>
      </td>
      <td><?= htmlspecialchars($a['appointment_date']) ?></td>
      <td>
        <?php if ($status === 'pending'): ?>
          <span class="badge bg-secondary">Pending</span>
        <?php elseif ($status === 'approved'): ?>
          <span class="badge bg-success">Approved</span>
        <?php elseif ($status === 'completed'): ?>
          <span class="badge bg-primary">Completed</span>
        <?php elseif ($status === 'cancelled'): ?>
          <span class="badge bg-danger">Cancelled</span>
        <?php endif; ?>
      </td>
      <td>
        <!-- Status Actions -->
        <?php if ($status === 'pending'): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
            <button name="new_status" value="approved" class="btn btn-sm btn-success">Approve</button>
          </form>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
            <button name="new_status" value="cancelled" class="btn btn-sm btn-danger">Cancel</button>
          </form>

        <?php elseif ($status === 'approved'): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
            <button name="new_status" value="completed" class="btn btn-sm btn-primary">Mark Completed</button>
          </form>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
            <button name="new_status" value="cancelled" class="btn btn-sm btn-danger">Cancel</button>
          </form>

        <?php elseif ($status === 'completed'): ?>
          <span class="text-muted">✔ Done</span>

        <?php elseif ($status === 'cancelled'): ?>
          <span class="text-muted">—</span>
        <?php endif; ?>

        <!-- Patient Details -->
        <a href="patient_detail.php?id=<?= htmlspecialchars($a['patient_id']) ?>" 
           class="btn btn-sm btn-outline-dark ms-1">
           🩺 Details / AI / Reports
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
