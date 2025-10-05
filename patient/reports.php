<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// Get patient ID
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();
if (!$patient) { header("Location: profile.php"); exit; }
$patient_id = $patient['id'];

// ✅ Optional: filter by appointment ID (from URL)
$appointment_id = isset($_GET['appointment']) ? intval($_GET['appointment']) : null;

// ✅ Fetch completed appointments for this patient
$sql = "
    SELECT a.*, u.name AS doctor_name, u.email AS doctor_email 
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND a.status = 'completed'
";

$params = [$patient_id];

if ($appointment_id) {
    $sql .= " AND a.id = ?";
    $params[] = $appointment_id;
}

$sql .= " ORDER BY a.appointment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2>📝 Medical Reports & AI Results</h2>

<?php if (empty($appointments)): ?>
  <div class="alert alert-info">No completed appointments found.</div>
<?php else: ?>

  <?php foreach ($appointments as $appt): 
    $aid = $appt['id'];
  ?>
    <div class="card mb-4">
      <div class="card-header bg-light">
        <strong><?= formatAppointmentRef($aid) ?></strong><br>
        Date: <?= htmlspecialchars($appt['appointment_date']) ?> — Doctor: <?= htmlspecialchars($appt['doctor_name']) ?>
      </div>
      <div class="card-body">
        <!-- 📝 Doctor Reports -->
        <h5>Doctor Reports</h5>
        <?php
        $stmt = $pdo->prepare("
            SELECT * FROM medical_reports 
            WHERE appointment_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$aid]);
        $reports = $stmt->fetchAll();

        if ($reports):
          foreach ($reports as $r): ?>
            <div class="border rounded p-2 mb-2">
              <strong><?= htmlspecialchars($r['created_at']) ?></strong><br>
              <?= nl2br(htmlspecialchars(decrypt_aes($r['details']))) ?>
            </div>
          <?php endforeach;
        else: ?>
          <p class="text-muted">No reports for this appointment.</p>
        <?php endif; ?>

        <!-- 🤖 AI Predictions -->
        <h5 class="mt-3">AI Predictions</h5>
        <?php
        $stmt = $pdo->prepare("
            SELECT * FROM predictions 
            WHERE appointment_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$aid]);
        $preds = $stmt->fetchAll();

        if ($preds): ?>
          <table class="table table-sm table-bordered mt-2">
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
        <?php else: ?>
          <p class="text-muted">No AI predictions for this appointment.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>
<a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
