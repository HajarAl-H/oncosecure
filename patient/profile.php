<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// fetch profile (decrypt medical_history only)
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

    $age = intval($_POST['age'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $history = sanitize($_POST['history'] ?? '');

    // encrypt history only
    $encHistory = $history ? encrypt_aes($history) : null;

    $stmt = $pdo->prepare("UPDATE patients SET age = ?, weight = ?, medical_history = ? WHERE user_id = ?");
    $stmt->execute([$age, $weight, $encHistory, $uid]);

    logAction($pdo, $uid, "Patient updated profile");
    flash_set('success','Profile updated.');
    header("Location: profile.php");
    exit;
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Edit Profile</h2>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3">
    <label>Age</label>
    <input class="form-control" name="age" 
           value="<?= htmlspecialchars($patient['age'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label>Weight (kg)</label>
    <input class="form-control" name="weight" 
           value="<?= htmlspecialchars($patient['weight'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label>Medical History</label>
    <textarea class="form-control" name="history"><?= htmlspecialchars($patient['medical_history'] ?? '') ?></textarea>
  </div>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
