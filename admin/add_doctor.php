<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (!$name || !$email) $error = "All fields required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email.";
    else {
        $temp = bin2hex(random_bytes(4));
        $hash = password_hash($temp, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'doctor')");
            $stmt->execute([$name, $email, $hash]);
            $id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], "Doctor added: $id");
            // Send temp via email in production
            flash_set('success', "Doctor created. Temp password: $temp (showed for demo)");
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Email already exists.";
        }
    }
}
$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Add Doctor</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3"><label>Name</label><input class="form-control" name="name" required></div>
  <div class="mb-3"><label>Email</label><input class="form-control" name="email" type="email" required></div>
  <button class="btn btn-primary" type="submit">Create Doctor</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>