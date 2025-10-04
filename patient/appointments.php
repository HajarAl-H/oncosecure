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

    // validate date
    $dt = strtotime($date);
    $hour = (int)date('H', $dt);
    if ($dt < time()) {
        flash_set('error','Cannot book past dates.');
    } elseif ($hour < 8 || $hour > 17) {
        flash_set('error','Appointments only allowed between 08:00 and 17:00.');
    } else {
        // check double booking for doctor
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
        $stmt->execute([$doctor_id, $date]);
        if ($stmt->fetchColumn() > 0) {
            flash_set('error','This doctor already has an appointment at this time.');
        } else {
            // check patient double booking
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date = ?");
            $stmt->execute([$patient['id'], $date]);
            if ($stmt->fetchColumn() > 0) {
                flash_set('error','You already have an appointment at this time.');
            } else {
                // insert
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date) VALUES (?, ?, ?)");
                $stmt->execute([$patient['id'], $doctor_id, $date]);
                logAction($pdo, $uid, "Booked appointment with doctor $doctor_id at $date");
                flash_set('success','Appointment booked.');
            }
        }
    }
    header("Location: appointments.php");
    exit;
}

// handle cancel
if (isset($_GET['cancel'])) {
    $appt_id = intval($_GET['cancel']);
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->execute([$appt_id, $patient['id']]);
    logAction($pdo, $uid, "Cancelled appointment $appt_id");
    flash_set('success','Appointment cancelled.');
    header("Location: appointments.php");
    exit;
}

// fetch doctors
$stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE role='doctor'");
$stmt->execute();
$doctors = $stmt->fetchAll();

// fetch patient's appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS doctor_name 
    FROM appointments a 
    JOIN users u ON a.doctor_id = u.id 
    WHERE a.patient_id = ? 
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$patient['id']]);
$appts = $stmt->fetchAll();

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Appointments</h2>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<?php if($m = flash_get('error')) echo "<div class='alert alert-danger'>$m</div>"; ?>

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
  <div class="mb-3">
    <label>Date & Time</label>
    <input class="form-control" type="datetime-local" name="appointment_date" required>
    <small class="form-text text-muted">Appointments allowed 08:00–17:00 only.</small>
  </div>
  <button class="btn btn-primary" name="book" type="submit">Book</button>
</form>

<h4>Your Appointments</h4>
<table class="table">
  <thead><tr><th>Date</th><th>Doctor</th><th>Status</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach($appts as $a): ?>
      <tr>
        <td><?= $a['appointment_date'] ?></td>
        <td><?= htmlspecialchars($a['doctor_name']) ?></td>
        <td><?= $a['status'] ?></td>
        <td>
          <?php if($a['status']=='pending'): ?>
            <a class="btn btn-sm btn-danger" href="?cancel=<?= $a['id'] ?>" onclick="return confirm('Cancel this appointment?')">Cancel</a>
          <?php else: ?>
            ---
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
