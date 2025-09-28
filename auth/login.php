<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) { $error = "Invalid request."; }
    else {
        $email = sanitize($_POST['email'] ?? '');
        $pw = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid credentials.";
            logAction($pdo, null, "Login failed (unknown email: $email)");
        } else {
            if (is_locked($user)) {
                $error = "Account locked until " . $user['lockout_until'];
                logAction($pdo, $user['id'], "Login attempt while locked");
            } elseif (password_verify($pw, $user['password'])) {
                reset_failed_attempts($pdo, $user['id']);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                logAction($pdo, $user['id'], "Login success");
                // redirect
                if ($user['role'] === 'admin') header("Location: /admin/dashboard.php");
                elseif ($user['role'] === 'doctor') header("Location: /doctor/dashboard.php");
                else header("Location: /patient/dashboard.php");
                exit;
            } else {
                record_failed_login($pdo, $user['id']);
                $error = "Invalid credentials.";
                logAction($pdo, $user['id'], "Login failed (bad password)");
            }
        }
    }
}
$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Login</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
  <div class="mb-3"><label>Password</label><input class="form-control" type="password" name="password" required></div>
  <button class="btn btn-primary" name="login" type="submit">Login</button>
  <a class="btn btn-link" href="reset_request.php">Forgot Password?</a>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
