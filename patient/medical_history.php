<?php 
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();

$uid = $_SESSION['user_id'];

// 🩺 Fetch patient's medical history and file info
$stmt = $pdo->prepare("SELECT medical_history, medical_history_file FROM patients WHERE user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();

$medical_history = '';
$file_path = '';

if ($patient) {
    if (!empty($patient['medical_history'])) {
        $medical_history = decrypt_aes($patient['medical_history']);
    }
    $file_path = $patient['medical_history_file'] ?? '';
}

// 📝 Handle form submission (text + optional file)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $history = sanitize($_POST['history'] ?? '');
    $uploadedFilePath = $file_path;

    // ❌ Deny special characters
    if (!preg_match('/^[a-zA-Z0-9\s.,-]*$/', $history)) {
        $error = "Special characters are not allowed. Only letters, numbers, spaces, dot, comma, and dash.";
    }

    // Only encrypt if no error
    $encHistory = empty($error) && $history ? encrypt_aes($history) : null;

    // 📎 Handle file upload
    if (empty($error) && !empty($_FILES['history_file']['name'])) {
        $file = $_FILES['history_file'];
        $allowed = ['pdf','docx','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Invalid file type. Allowed: PDF, DOCX, JPG, PNG.";
        } elseif ($file['size'] > 5*1024*1024) {
            $error = "File is too large. Max 5MB.";
        } else {
            $uploadDir = __DIR__ . '/../uploads/medical_history/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $uploadedFilePath = 'uploads/medical_history/' . $newName;
            } else {
                $error = "File upload failed.";
            }
        }
    }

    // 🟢 Save if no errors
    if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE patients SET medical_history = ?, medical_history_file = ? WHERE user_id = ?");
        $stmt->execute([$encHistory, $uploadedFilePath, $uid]);

        logAction($pdo, $uid, "Patient updated medical history and file");
        flash_set('success', 'Medical history updated.');
        header("Location: medical_history.php");
        exit;
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Medical History</h2>

<?php if ($msg = flash_get('success')): ?>
  <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label for="history" class="form-label">Your Medical History (Text)</label>
    <textarea id="history" class="form-control" name="history" rows="8"><?= htmlspecialchars($medical_history) ?></textarea>
    <small class="text-muted">
        Allowed: A–Z, a–z, numbers, spaces, dot (.), comma (,), dash (-)
    </small>
  </div>

  <div class="mb-3">
    <label for="history_file" class="form-label">Upload Medical History File (Optional)</label>
    <input type="file" class="form-control" name="history_file" id="history_file" accept=".pdf,.docx,.jpg,.jpeg,.png">
    <?php if ($file_path): ?>
      <p class="mt-2">
        Current file: 
        <a href="/<?= htmlspecialchars($file_path) ?>" target="_blank">View / Download</a>
      </p>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-primary">Save</button>
  <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
