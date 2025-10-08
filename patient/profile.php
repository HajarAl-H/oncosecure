<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// 🧍 Fetch patient profile (excluding medical history)
$stmt = $pdo->prepare("SELECT p.*, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $age      = intval($_POST['age'] ?? 0);
    $weight   = floatval($_POST['weight'] ?? 0);
    $phone    = sanitize($_POST['phone'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $marital  = sanitize($_POST['marital_status'] ?? '');

    // === Validations ===
    if ($age < 16 || $age > 80) {
        $error = "Age must be between 16 and 80.";
    } elseif (!preg_match('/^[97][0-9]{7}$/', $phone)) {
        $error = "Phone must start with 9 or 7 and be exactly 8 digits.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])[a-zA-Z0-9\s]+$/', $address)) {
        $error = "Address must contain letters (and can include numbers), not numbers only.";
    } elseif (!in_array($marital, ['single','married','other'])) {
        $error = "Invalid marital status.";
    } else {
        // ✅ Update profile (no medical history here)
        $stmt = $pdo->prepare("UPDATE patients 
                               SET age = ?, weight = ?, phone = ?, address = ?, marital_status = ?
                               WHERE user_id = ?");
        $stmt->execute([$age, $weight, $phone, $address, $marital, $uid]);

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
    <input class="form-control" type="text" name="phone" maxlength="8" 
           value="<?= htmlspecialchars($patient['phone'] ?? '') ?>" required>
    <small class="form-text text-muted">Must start with 9 or 7 and be 8 digits.</small>
  </div>

  <div class="mb-3">
    <label>Address</label>
    <input class="form-control" type="text" name="address" 
           value="<?= htmlspecialchars($patient['address'] ?? '') ?>" required>
    <small class="form-text text-muted">Must contain letters (can include numbers), not numbers only.</small>
  </div>

  <div class="mb-3">
    <label>Marital Status</label>
    <select class="form-control" name="marital_status" required>
      <option value="single"  <?= ($patient['marital_status'] ?? '')==='single'?'selected':'' ?>>Single</option>
      <option value="married" <?= ($patient['marital_status'] ?? '')==='married'?'selected':'' ?>>Married</option>
      <option value="other"   <?= ($patient['marital_status'] ?? '')==='other'?'selected':'' ?>>Other</option>
    </select>
  </div>

  <button class="btn btn-primary" type="submit">Save</button>
  <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
