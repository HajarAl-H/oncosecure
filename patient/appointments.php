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
$today = date('Y-m-d');


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

  // Prevent doctor double booking when appointment already approved
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'approved'");
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

  // Save appointment (encrypt notes)
  $encryptedNotes = $notes ? encrypt_aes($notes) : null;
  $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes)
        VALUES (?, ?, ?, ?)
    ");
  $stmt->execute([$patient_id, $doctor_id, $date, $encryptedNotes]);

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
    <select name="doctor_id" id="doctor_id" class="form-control" required>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Date</label>
    <input class="form-control" type="date" name="appointment_date" id="appointment_date" min="<?= $today ?>" required>
  </div>

  <div class="mb-3">
    <label>Time (30-minute intervals)</label>
    <select class="form-control" name="appointment_time" id="appointment_time" required disabled>
      <option value="">Select doctor and date first</option>
    </select>
    <div class="form-text" id="slot-helper">Choose a doctor and date to view available slots.</div>
  </div>

  <div class="mb-3">
    <label>Notes (Optional)</label>
    <textarea class="form-control" name="notes" rows="3"
      placeholder="Any additional information for the doctor..."></textarea>
    <small class="text-muted">No special characters allowed.</small>
  </div>

  <button class="btn btn-primary" name="book" type="submit">Book</button>
</form>

<script>
  const doctorSelect = document.getElementById('doctor_id');
  const dateInput = document.getElementById('appointment_date');
  const timeSelect = document.getElementById('appointment_time');
  const slotHelper = document.getElementById('slot-helper');

  function setSelectState(enabled) {
    timeSelect.disabled = !enabled;
  }

  function showMessage(message, isError = false) {
    if (!slotHelper) return;
    slotHelper.textContent = message;
    slotHelper.classList.toggle('text-danger', isError);
  }

  async function loadSlots() {
    const doctorId = doctorSelect.value;
    const dateValue = dateInput.value;

    if (!doctorId || !dateValue) {
      timeSelect.innerHTML = '<option value="">Select doctor and date first</option>';
      setSelectState(false);
      showMessage('Choose a doctor and date to view available slots.');
      return;
    }

    setSelectState(false);
    showMessage('Loading available slots...');
    timeSelect.innerHTML = '';

    try {
      const response = await fetch(`get_available_slots.php?doctor_id=${doctorId}&date=${dateValue}`);
      if (!response.ok) throw new Error('Failed to load slots');
      const data = await response.json();

      const slots = Array.isArray(data.slots) ? data.slots : [];
      timeSelect.innerHTML = '';

      if (!slots.length) {
        timeSelect.innerHTML = '<option value="">No available slots</option>';
        showMessage('Doctor has no approved slots available for this date.', true);
        return;
      }

      const frag = document.createDocumentFragment();
      slots.forEach((slot) => {
        const opt = document.createElement('option');
        opt.value = slot;
        opt.textContent = slot;
        frag.appendChild(opt);
      });

      timeSelect.appendChild(frag);
      setSelectState(true);
      showMessage('Select one of the available slots.');
    } catch (error) {
      timeSelect.innerHTML = '<option value="">Could not load slots</option>';
      showMessage('Could not load available slots. Please try again.', true);
    }
  }

  doctorSelect.addEventListener('change', loadSlots);
  dateInput.addEventListener('change', loadSlots);
</script>


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
        <?php $noteText = $a['notes'] ? decrypt_aes($a['notes']) : ''; ?>
        <td><?= nl2br(htmlspecialchars($noteText)) ?></td>
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