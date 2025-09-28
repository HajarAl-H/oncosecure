<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) { $error = "Invalid request."; }
    else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $pw = $_POST['password'] ?? '';

        if (!$name || !$email || !$pw) $error = "All fields required.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email.";
        elseif (strlen($pw) < 8) $error = "Password must be at least 8 characters.";
        else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'patient')");
                $stmt->execute([$name, $email, $hash]);
                $uid = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO patients (user_id) VALUES (?)");
                $stmt->execute([$uid]);
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
  <div class="mb-3"><label>Name</label><input class="form-control" name="name" required></div>
  <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
  <div class="mb-3"><label>Password</label><input class="form-control" type="password" name="password" required></div>
  <button class="btn btn-primary" type="submit">Register</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
