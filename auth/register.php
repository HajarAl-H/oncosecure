<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) { 
        $error = "Invalid request."; 
    } else {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $pw       = $_POST['password'] ?? '';
        $age      = intval($_POST['age'] ?? 0);
        $phone    = sanitize($_POST['phone'] ?? '');
        $address  = sanitize($_POST['address'] ?? '');
        $marital_status = sanitize($_POST['marital_status'] ?? '');

        // === Validations ===
        if (!$name || !$email || !$pw || !$age || !$phone || !$address || !$marital_status) {
            $error = "All fields are required.";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            $error = "Name must contain only letters and spaces.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (!preg_match('/@(gmail\.com|hotmail\.com|yahoo\.com|facebook\.com)$/i', $email)) {
            $error = "Email must be Gmail, Hotmail, Yahoo or Facebook.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pw)) {
            $error = "Password must be at least 8 characters, include upper and lower case letters, a number, and a special character.";
        } elseif ($age < 16 || $age > 80) {
            $error = "Age must be between 16 and 80.";
        } elseif (!preg_match('/^[97][0-9]{7}$/', $phone)) {
            $error = "Phone must start with 9 or 7 and be exactly 8 digits.";
        } elseif (!preg_match('/^(?=.*[a-zA-Z])[a-zA-Z0-9\s]+$/', $address)) {
            $error = "Address must contain letters (and can include numbers), not numbers only.";
        } elseif (!in_array($marital_status, ['single','married','other'])) {
            $error = "Invalid marital status.";
        } else {
            // ✅ All good -> Insert into DB
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            try {
                // Insert into users
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'patient')");
                $stmt->execute([$name, $email, $hash]);
                $uid = $pdo->lastInsertId();

                // Insert into patients
                $stmt = $pdo->prepare("INSERT INTO patients (user_id, age, phone, address, marital_status, medical_history) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$uid, $age, $phone, $address, $marital_status, null]);

                logAction($pdo, $uid, "Patient registered");
                flash_set('success', 'Account created. Please login.');
                header("Location: login.php");
                exit;
            } catch (PDOException $e) {
                $error = "Email already registered.";
            }
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Patient Registration</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>

<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label>Name</label>
    <input class="form-control" name="name" required>
  </div>

  <div class="mb-3">
    <label>Email</label>
    <input class="form-control" type="email" name="email" required>
    <small class="form-text text-muted">Allowed domains: Gmail, Hotmail, Yahoo, Facebook</small>
  </div>

  <div class="mb-3">
    <label>Password</label>
    <input class="form-control" type="password" name="password" required>
    <small class="form-text text-muted">
      Must be at least 8 characters, include upper and lower case letters, a number, and a special character.
    </small>
  </div>

  <div class="mb-3">
    <label>Age</label>
    <input class="form-control" type="number" name="age" min="16" max="80" required>
  </div>

  <div class="mb-3">
    <label>Phone</label>
    <input class="form-control" type="text" name="phone" maxlength="8" required>
    <small class="form-text text-muted">Must start with 9 or 7 and be 8 digits.</small>
  </div>

  <div class="mb-3">
    <label>Address</label>
    <input class="form-control" type="text" name="address" required>
    <small class="form-text text-muted">Must contain letters (can include numbers), not numbers only.</small>
  </div>

  <div class="mb-3">
    <label>Marital Status</label>
    <select class="form-control" name="marital_status" required>
      <option value="single">Single</option>
      <option value="married">Married</option>
      <option value="other">Other</option>
    </select>
  </div>

  <button class="btn btn-primary" type="submit">Register</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
