<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// fetch profile
$stmt = $pdo->prepare("SELECT p.*, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();

if ($patient) {
    $patient['medical_history'] = $patient['medical_history'] 
        ? decrypt_aes($patient['medical_history']) 
        : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $age      = intval($_POST['age'] ?? 0);
    $weight   = floatval($_POST['weight'] ?? 0);
    $history  = sanitize($_POST['history'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $marital  = sanitize($_POST['marital_status'] ?? '');

    // === Validations ===
    if ($age < 16 || $age > 80) {
        $error = "Age must be between 16 and 80.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $error = "Phone number must be exactly 8 digits.";
    } elseif (!$address) {
        $error = "Address is required.";
    } elseif (!in_array($marital, ['single','married','other'])) {
        $error = "Invalid marital status.";
    } else {
        // encrypt history only
        $encHistory = $history ? encrypt_aes($history) : null;

        $stmt = $pdo->prepare("UPDATE patients 
                               SET age = ?, weight = ?, medical_history = ?, phone = ?, address = ?, marital_status = ?
                               WHERE user_id = ?");
        $stmt->execute([$age, $weight, $encHistory, $phone, $address, $marital, $uid]);

        logAction($pdo, $uid, "Patient updated profile");
        flash_set('success','Profile updated.');
        header("Location: profile.php");
        exit;
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Edit Profile</h2>

<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>

<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label>Age</label>
    <input class="form-control" type="number" name="age" min="16" max="80" 
           value="<?= htmlspecialchars($patient['age'] ?? '') ?>" required>
  </div>

  <div class="mb-3">
    <label>Weight (kg)</label>
    <input class="form-control" type="number" step="0.1" name="weight" 
           value="<?= htmlspecialchars($patient['weight'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label>Phone</label>
    <input class="form-control" type="text" name="phone" maxlength="8" pattern="\d{8}" 
           value="<?= htmlspecialchars($patient['phone'] ?? '') ?>" required>
    <small class="form-text text-muted">Exactly 8 digits.</small>
  </div>

  <div class="mb-3">
    <label>Address</label>
    <input class="form-control" type="text" name="address" 
           value="<?= htmlspecialchars($patient['address'] ?? '') ?>" required>
  </div>

  <div class="mb-3">
    <label>Marital Status</label>
    <select class="form-control" name="marital_status" required>
      <option value="single"  <?= ($patient['marital_status'] ?? '')==='single'?'selected':'' ?>>Single</option>
      <option value="married" <?= ($patient['marital_status'] ?? '')==='married'?'selected':'' ?>>Married</option>
      <option value="other"   <?= ($patient['marital_status'] ?? '')==='other'?'selected':'' ?>>Other</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Medical History</label>
    <textarea class="form-control" name="history"><?= htmlspecialchars($patient['medical_history'] ?? '') ?></textarea>
  </div>

  <button class="btn btn-primary" type="submit">Save</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
