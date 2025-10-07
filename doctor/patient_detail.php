<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();

$patient_id = intval($_GET['id'] ?? 0);
$appointment_id = isset($_GET['appointment']) ? intval($_GET['appointment']) : null;

if (!$patient_id) { header("Location: dashboard.php"); exit; }

// 🧍 Fetch patient data
$stmt = $pdo->prepare("
    SELECT p.*, u.name, u.email 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
if (!$patient) { header("Location: dashboard.php"); exit; }

// Decrypt history
$patient['medical_history'] = $patient['medical_history'] 
    ? decrypt_aes($patient['medical_history']) 
    : '';

// 📝 Fetch appointment info (status + notes)
$appointment_status = null;
$appointment_notes = null;
if ($appointment_id) {
    $stmt = $pdo->prepare("SELECT status, notes FROM appointments WHERE id = ?");
    $stmt->execute([$appointment_id]);
    $appt = $stmt->fetch();
    if ($appt) {
        $appointment_status = $appt['status'];
        $appointment_notes = $appt['notes'];
    }
}

// 📝 Handle new medical report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    if ($appointment_status === 'completed') {
        $error = "You cannot add reports to a completed appointment.";
    } else {
        if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');
        $details = trim($_POST['details'] ?? '');

        if (!$details) {
            $error = "Details required.";
        } else {
            // Encrypt report
            $encrypted = encrypt_aes($details);
            $stmt = $pdo->prepare("
                INSERT INTO medical_reports (patient_id, doctor_id, details, appointment_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$patient_id, $_SESSION['user_id'], $encrypted, $appointment_id]);
            $report_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], "Added report $report_id for patient $patient_id");

            // 🤖 AI Prediction
            $postData = ['patient_id' => $patient_id, 'report' => $details];
            $ch = curl_init('http://127.0.0.1:5000/predict');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $res = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                $warning = "AI service unreachable.";
            } else {
                $json = json_decode($res, true) ?: null;
                $result = $json['result'] ?? null;
                $conf = $json['confidence'] ?? null;
                $recommendation = $json['recommendation'] ?? null;
                $stmt = $pdo->prepare("
                    INSERT INTO predictions (patient_id, doctor_id, input_json, result, confidence, recommendation, appointment_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $patient_id, 
                    $_SESSION['user_id'], 
                    json_encode($postData), 
                    $result, 
                    $conf, 
                    $recommendation, 
                    $appointment_id
                ]);
                logAction($pdo, $_SESSION['user_id'], "AI run for patient $patient_id ($result)");
                $success = "Report added and AI prediction saved.";
            }
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">👤 Patient Details</h2>

<div class="row">
  <?php if ($appointment_id): ?>
    <p><strong>Appointment Ref:</strong> <?= formatAppointmentRef($appointment_id) ?></p>
  <?php endif; ?>

  <?php if ($appointment_notes): ?>
    <div class="col-12 mb-3">
      <div class="card bg-light">
        <div class="card-header"><strong>📝 Patient Notes for This Appointment</strong></div>
        <div class="card-body">
          <?= nl2br(htmlspecialchars($appointment_notes)) ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header"><strong>Basic Information</strong></div>
      <div class="card-body">
          <p><strong>Name:</strong> <?= htmlspecialchars($patient['name'] ?? '—') ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($patient['email'] ?? '—') ?></p>
          <p><strong>Age:</strong> <?= htmlspecialchars($patient['age'] !== null && $patient['age'] !== '' ? $patient['age'] : '—') ?></p>
          <p><strong>Weight:</strong> <?= htmlspecialchars($patient['weight'] !== null && $patient['weight'] !== '' ? $patient['weight'] : '—') ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone'] ?? '—') ?></p>
          <p><strong>Marital:</strong> <?= htmlspecialchars($patient['marital_status'] ?? '—') ?></p>
          <p><strong>Address:</strong> <?= htmlspecialchars($patient['address'] ?? '—') ?></p>
      </div>

    </div>
  </div>

  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header"><strong>Medical History</strong></div>
      <div class="card-body">
        <?= nl2br(htmlspecialchars($patient['medical_history'])) ?>
        <?php if (!empty($patient['medical_history_file'])): ?>
          <p class="mt-3">
            <a href="/<?= htmlspecialchars($patient['medical_history_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
              📄 View / Download File
            </a>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if(isset($warning)) echo "<div class='alert alert-warning'>$warning</div>"; ?>
<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<!-- Add Medical Report -->
<div class="card mb-4">
  <div class="card-header"><strong>Add Medical Report / Observation</strong></div>
  <div class="card-body">
    <?php if ($appointment_status === 'completed'): ?>
      <div class="alert alert-info mb-0">
        📝 This appointment is marked as <strong>Completed</strong>. You can no longer add reports or run AI predictions.
      </div>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= $token ?>">
        <div class="mb-3">
          <textarea class="form-control" name="details" rows="5" required></textarea>
        </div>
        <button class="btn btn-primary" name="add_report" type="submit">💾 Save & Run AI</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- Previous Reports -->
<h4>📜 Previous Reports</h4>
<?php
$stmt = $pdo->prepare("
    SELECT * FROM medical_reports 
    WHERE patient_id = ? AND appointment_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$patient_id, $appointment_id]);
$reports = $stmt->fetchAll();
?>
<ul class="list-group mb-4">
  <?php foreach($reports as $r): ?>
    <li class="list-group-item">
      <strong><?= $r['created_at'] ?>:</strong><br>
      <?= nl2br(htmlspecialchars(decrypt_aes($r['details']))) ?>
    </li>
  <?php endforeach; ?>
</ul>

<!-- Previous Predictions -->
<h4>🤖 AI Predictions</h4>
<?php
$stmt = $pdo->prepare("
    SELECT * FROM predictions 
    WHERE patient_id = ? AND appointment_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$patient_id, $appointment_id]);
$preds = $stmt->fetchAll();
?>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>Date</th>
      <th>Result</th>
      <th>Confidence</th>
      <th>Recommendation</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($preds as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['created_at']) ?></td>
        <td><?= htmlspecialchars($p['result']) ?></td>
        <td><?= htmlspecialchars($p['confidence']) ?></td>
        <td><?= htmlspecialchars($p['recommendation']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
