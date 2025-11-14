<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// get patient id
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();
if (!$patient) {
  header("Location: profile.php");
  exit;
}
$patient_id = $patient['id'];


// =========================
// 📌 BOOK NEW APPOINTMENT
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {

  if (!check_csrf($_POST['csrf'] ?? ''))
    die('CSRF mismatch');

  $doctor_id = intval($_POST['doctor_id']);
  $dateOnly = sanitize($_POST['appointment_date']);
  $timeOnly = sanitize($_POST['appointment_time']);
  $notes = trim($_POST['notes'] ?? '');

  // 🛑 Prevent special characters in notes
  if (!preg_match('/^[a-zA-Z0-9\s.,-_]*$/', $notes)) {
    flash_set('error', 'Notes cannot contain special characters. Allowed: letters, numbers, spaces, dot, comma, dash, underscore.');
    header("Location: appointments.php");
    exit;
  }

  // Combine date + time
  $datetimeString = "$dateOnly $timeOnly:00";
  $dt = strtotime($datetimeString);
  $date = date("Y-m-d H:i:s", $dt);

  // Validate date & time
  $hour = (int) date('H', $dt);
  $minute = (int) date('i', $dt);

  if ($dt < time()) {
    flash_set('error', 'Cannot book past dates.');
    header("Location: appointments.php");
    exit;
  }

  if ($hour < 8 || $hour > 17) {
    flash_set('error', 'Appointments allowed between 08:00 and 17:00.');
    header("Location: appointments.php");
    exit;
  }

  if (!in_array($minute, [0, 30])) {
    flash_set('error', 'Time must be in 30-minute intervals (e.g., 08:00, 08:30).');
    header("Location: appointments.php");
    exit;
  }

  // Prevent duplicate booking with same doctor
  $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM appointments 
        WHERE patient_id = ? AND doctor_id = ? AND status IN ('pending','approved')
    ");
  $stmt->execute([$patient_id, $doctor_id]);
  if ($stmt->fetchColumn() > 0) {
    flash_set('error', 'You already have a pending or approved appointment with this doctor.');
    header("Location: appointments.php");
    exit;
  }

  // Prevent doctor double booking
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
  $stmt->execute([$doctor_id, $date]);
  if ($stmt->fetchColumn() > 0) {
    flash_set('error', 'This doctor already has an appointment at this time.');
    header("Location: appointments.php");
    exit;
  }

  // Prevent patient double booking same time
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date = ?");
  $stmt->execute([$patient_id, $date]);
  if ($stmt->fetchColumn() > 0) {
    flash_set('error', 'You already have an appointment at this time.');
    header("Location: appointments.php");
    exit;
  }

  // Save appointment
  $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes)
        VALUES (?, ?, ?, ?)
    ");
  $stmt->execute([$patient_id, $doctor_id, $date, $notes]);

  logAction($pdo, $uid, "Booked appointment with doctor $doctor_id at $date");
  flash_set('success', 'Appointment booked successfully.');
  header("Location: appointments.php");
  exit;
}


// =========================
// ❌ CANCEL APPOINTMENT
// =========================
if (isset($_GET['cancel'])) {
  $appt_id = intval($_GET['cancel']);
  $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ? AND status='pending'");
  $stmt->execute([$appt_id, $patient_id]);

  logAction($pdo, $uid, "Cancelled appointment $appt_id");
  flash_set('success', 'Appointment cancelled.');
  header("Location: appointments.php");
  exit;
}


// =========================
// 📌 FETCH DATA
// =========================
$stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE role='doctor'");
$stmt->execute();
$doctors = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS doctor_name, u.email AS doctor_email
    FROM appointments a 
    JOIN users u ON a.doctor_id = u.id 
    WHERE a.patient_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$patient_id]);
$appts = $stmt->fetchAll();

$token = csrf_token();

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Appointments</h2>

<?php if ($m = flash_get('success'))
  echo "<div class='alert alert-success'>$m</div>"; ?>
<?php if ($m = flash_get('error'))
  echo "<div class='alert alert-danger'>$m</div>"; ?>


<!-- ========================= -->
<!-- 📅 BOOK NEW APPOINTMENT -->
<!-- ========================= -->

<form method="post" class="w-50 mb-4">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label>Doctor</label>
    <select name="doctor_id" class="form-control" required>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Date</label>
    <input class="form-control" type="date" name="appointment_date" required>
  </div>

  <div class="mb-3">
    <label>Time (30-minute intervals)</label>
    <select class="form-control" name="appointment_time" required>
      <?php
      for ($hour = 8; $hour <= 17; $hour++) {
        foreach ([0, 30] as $min) {
          $t = sprintf("%02d:%02d", $hour, $min);
          echo "<option value='$t'>$t</option>";
        }
      }
      ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Notes (Optional)</label>
    <textarea class="form-control" name="notes" rows="3"
      placeholder="Any additional information for the doctor..."></textarea>
    <small class="text-muted">No special characters allowed.</small>
  </div>

  <button class="btn btn-primary" name="book" type="submit">Book</button>
</form>


<!-- ========================= -->
<!-- 📋 LIST OF APPOINTMENTS -->
<!-- ========================= -->

<h4>Your Appointments</h4>
<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>Ref</th>
      <th>Date</th>
      <th>Doctor</th>
      <th>Status</th>
      <th>Notes</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <?php foreach ($appts as $a): ?>
      <tr>
        <td><strong><?= formatAppointmentRef($a['id']) ?></strong></td>
        <td><?= htmlspecialchars($a['appointment_date']) ?></td>
        <td><?= htmlspecialchars($a['doctor_name']) ?></td>
        <td>
          <?php if ($a['status'] === 'pending'): ?>
            <span class="badge bg-secondary">Pending</span>
          <?php elseif ($a['status'] === 'approved'): ?>
            <span class="badge bg-success">Approved</span>
          <?php elseif ($a['status'] === 'completed'): ?>
            <span class="badge bg-primary">Completed</span>
          <?php elseif ($a['status'] === 'cancelled'): ?>
            <span class="badge bg-danger">Cancelled</span>
          <?php endif; ?>
        </td>
        <td><?= nl2br(htmlspecialchars($a['notes'])) ?></td>
        <td>
          <?php if ($a['status'] === 'pending'): ?>
            <a class="btn btn-sm btn-danger" href="?cancel=<?= $a['id'] ?>"
              onclick="return confirm('Cancel this appointment?')">Cancel</a>
          <?php elseif ($a['status'] === 'completed'): ?>
            <a href="reports.php?appointment=<?= $a['id'] ?>" class="btn btn-sm btn-outline-dark">View Report / AI</a>
          <?php else: ?>
            ---
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>

</table>

<a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>