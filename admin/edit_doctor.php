<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();

$uploadDir = __DIR__ . '/../uploads/certificates/';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='doctor'");
$stmt->execute([$id]);
$doctor = $stmt->fetch();
if (!$doctor) { die("Doctor not found."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $specialization = sanitize($_POST['specialization'] ?? '');
    $experience = sanitize($_POST['experience'] ?? '');

    // Validation
    if (!$name || !$email || !$phone || !$address) {
        $error = "All required fields must be filled.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $error = "Phone number must be exactly 8 digits.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/@(gmail\.com|hotmail\.com|yahoo\.com|outlook\.com)$/i', $email)) {
        $error = "Email must be from Gmail, Hotmail, Yahoo, or Outlook.";
    } else {
        $certFile = $doctor['certificates'];
        if (!empty($_FILES['certificates']['name'])) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['certificates']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = "Only PDF, JPG, JPEG, PNG files allowed.";
            } else {
                $certFile = uniqid('cert_') . "." . $ext;
                move_uploaded_file($_FILES['certificates']['tmp_name'], $uploadDir . $certFile);
            }
        }

        if (!isset($error)) {
            $stmt = $pdo->prepare("UPDATE users 
                SET name=?, email=?, phone=?, address=?, specialization=?, experience=?, certificates=? 
                WHERE id=? AND role='doctor'");
            $stmt->execute([$name,$email,$phone,$address,$specialization,$experience,$certFile,$id]);
            logAction($pdo, $_SESSION['user_id'], "Edited doctor ID $id");

            flash_set('success', "Doctor updated.");
            header("Location: dashboard.php");
            exit;
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Edit Doctor</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post" enctype="multipart/form-data" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3"><label>Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" required></div>
  <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" required></div>
  <div class="mb-3"><label>Phone (8 digits)</label><input class="form-control" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" maxlength="8" required></div>
  <div class="mb-3"><label>Address</label><input class="form-control" name="address" value="<?= htmlspecialchars($doctor['address']) ?>" required></div>
  <div class="mb-3"><label>Specialization</label><input class="form-control" name="specialization" value="<?= htmlspecialchars($doctor['specialization']) ?>"></div>
  <div class="mb-3"><label>Experience</label><input class="form-control" name="experience" value="<?= htmlspecialchars($doctor['experience']) ?>"></div>
  <div class="mb-3">
    <label>Certificates</label><br>
    <?php if ($doctor['certificates']): ?>
      <a href="/<?= htmlspecialchars($doctor['certificates']) ?>" target="_blank">Current File</a><br>
    <?php endif; ?>
    <input class="form-control" type="file" name="certificates">
  </div>
  <button class="btn btn-primary" type="submit">Update Doctor</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
