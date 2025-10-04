<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: dashboard.php"); exit; }

// 🧍 Fetch full patient data including file path
$stmt = $pdo->prepare("
    SELECT p.*, u.name, u.email 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) { header("Location: dashboard.php"); exit; }

// Decrypt medical history text
$patient['medical_history'] = $patient['medical_history'] 
    ? decrypt_aes($patient['medical_history']) 
    : '';

// 📝 Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $details = trim($_POST['details'] ?? '');
    if (!$details) {
        $error = "Details required.";
    } else {
        // Encrypt report details before storing
        $encryptedDetails = encrypt_aes($details);
        $stmt = $pdo->prepare("INSERT INTO medical_reports (patient_id, doctor_id, details) VALUES (?, ?, ?)");
        $stmt->execute([$id, $_SESSION['user_id'], $encryptedDetails]);
        $report_id = $pdo->lastInsertId();
        logAction($pdo, $_SESSION['user_id'], "Added report $report_id for patient $id");

        // Run AI Prediction
        $postData = ['patient_id' => $id, 'report' => $details];
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
            $stmt = $pdo->prepare("INSERT INTO predictions (patient_id, doctor_id, input_json, result, confidence) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_SESSION['user_id'], json_encode($postData), $result, $conf]);
            logAction($pdo, $_SESSION['user_id'], "Ran AI for patient $id => $result");
            $success = "Report added and AI prediction saved.";
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">👤 Patient Details</h2>

<div class="row">
  <!-- Left Column: Patient Info -->
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header"><strong>Basic Information</strong></div>
      <div class="card-body">
        <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?></p>
        <p><strong>Weight:</strong> <?= htmlspecialchars($patient['weight']) ?> kg</p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
        <p><strong>Marital Status:</strong> <?= htmlspecialchars($patient['marital_status']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($patient['address']) ?></p>
      </div>
    </div>
  </div>

  <!-- Right Column: Medical History -->
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header"><strong>Medical History</strong></div>
      <div class="card-body">
        <p><?= nl2br(htmlspecialchars($patient['medical_history'])) ?></p>

        <?php if (!empty($patient['medical_history_file'])): ?>
          <p class="mt-3">
            <strong>Attached File:</strong><br>
            <a href="/<?= htmlspecialchars($patient['medical_history_file']) ?>" 
               target="_blank" 
               class="btn btn-outline-primary btn-sm">
               📄 View / Download File
            </a>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Notifications -->
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if(isset($warning)) echo "<div class='alert alert-warning'>$warning</div>"; ?>
<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<!-- Add Medical Report Form -->
<div class="card mb-4">
  <div class="card-header"><strong>Add Medical Report / Observation</strong></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= $token ?>">
      <div class="mb-3">
        <textarea class="form-control" name="details" rows="6" required></textarea>
      </div>
      <button class="btn btn-primary" name="add_report" type="submit">💾 Save & Run AI</button>
    </form>
  </div>
</div>

<!-- Previous Reports -->
<h4>📜 Previous Reports</h4>
<?php
$stmt = $pdo->prepare("SELECT * FROM medical_reports WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$reports = $stmt->fetchAll();
?>
<ul class="list-group mb-4">
  <?php foreach ($reports as $r): ?>
    <li class="list-group-item">
      <strong><?= $r['created_at'] ?>:</strong><br>
      <?= nl2br(htmlspecialchars(decrypt_aes($r['details']))) ?>
    </li>
  <?php endforeach; ?>
</ul>

<!-- AI Predictions Table -->
<h4>🤖 Previous Predictions</h4>
<?php
$stmt = $pdo->prepare("SELECT * FROM predictions WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
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
    <?php foreach($preds as $pr): ?>
      <tr>
        <td><?= htmlspecialchars($pr['created_at']) ?></td>
        <td><?= htmlspecialchars($pr['result']) ?></td>
        <td><?= htmlspecialchars($pr['confidence']) ?></td>
        <td><?= htmlspecialchars($pr['recommendation']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
