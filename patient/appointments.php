<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();
$uid = $_SESSION['user_id'];

// get patient id
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();

if (!$patient) { header("Location: profile.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');
    $doctor_id = intval($_POST['doctor_id']);
    $date = sanitize($_POST['appointment_date']);
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date) VALUES (?, ?, ?)");
    $stmt->execute([$patient['id'], $doctor_id, $date]);
    logAction($pdo, $uid, "Booked appointment with doctor $doctor_id at $date");
    flash_set('success','Appointment booked.');
    header("Location: appointments.php");
    exit;
}

// fetch doctors
$stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE role='doctor'");
$stmt->execute();
$doctors = $stmt->fetchAll();

// fetch patient's appointments
$stmt = $pdo->prepare("SELECT a.*, u.name AS doctor_name FROM appointments a JOIN users u ON a.doctor_id = u.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC");
$stmt->execute([$patient['id']]);
$appts = $stmt->fetchAll();

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Appointments</h2>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<form method="post" class="w-50 mb-4">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3">
    <label>Doctor</label>
    <select name="doctor_id" class="form-control" required>
      <?php foreach($doctors as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3"><label>Date & Time</label><input class="form-control" type="datetime-local" name="appointment_date" required></div>
  <button class="btn btn-primary" name="book" type="submit">Book</button>
</form>

<h4>Your Appointments</h4>
<table class="table">
  <thead><tr><th>Date</th><th>Doctor</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach($appts as $a): ?>
      <tr>
        <td><?= $a['appointment_date'] ?></td>
        <td><?= htmlspecialchars($a['doctor_name']) ?></td>
        <td><?= $a['status'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>