<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/functions.php';
if (isset($_GET['password_changed'])) {
    flash_set('success', 'Your password has been changed successfully. Please login with your new password.');
}

// ---- Handle form POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // CSRF check
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $_SESSION['login_error'] = "Invalid request.";
        header("Location: login.php");
        exit;
    }

    // CAPTCHA check
    if (empty($_POST['captcha']) || $_POST['captcha'] !== ($_SESSION['captcha'] ?? '')) {
        $_SESSION['login_error'] = "Invalid CAPTCHA.";
        header("Location: login.php");
        exit;
    }

    $email = sanitize($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['login_error'] = "Invalid credentials.";
        logAction($pdo, null, "Login failed (unknown email: $email)");
        header("Location: login.php");
        exit;
    }

    if (is_locked($user)) {
        $_SESSION['login_error'] = "Account locked until " . $user['lockout_until'];
        logAction($pdo, $user['id'], "Login attempt while locked");
        header("Location: login.php");
        exit;
    }

    // Temporary admin-issued passwords expire after 10 minutes
    if ((int) $user['force_password_change'] === 0 && !empty($user['temp_password_expires_at'])) {
        if (strtotime($user['temp_password_expires_at']) < time()) {
            $_SESSION['login_error'] = "Temporary password expired. Contact the administrator for a new reset.";
            logAction($pdo, $user['id'], "Login blocked - temporary password expired");
            header("Location: login.php");
            exit;
        }
    }

    if (password_verify($pw, $user['password'])) {
        reset_failed_attempts($pdo, $user['id']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        logAction($pdo, $user['id'], "Login success");

        // Redirect based on role
        if ($user['role'] === 'admin') $redirect = "/admin/dashboard.php";
        elseif ($user['role'] === 'doctor') $redirect = "/doctor/dashboard.php";
        else $redirect = "/patient/dashboard.php";

        header("Location: $redirect");
        exit;
    } else {
        record_failed_login($pdo, $user['id']);
        $_SESSION['login_error'] = "Invalid credentials.";
        logAction($pdo, $user['id'], "Login failed (bad password)");
        header("Location: login.php");
        exit;
    }
}

// ---- Show errors from previous POST ----
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// CSRF token
$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Login</h2>
<?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>

<form method="post" class="w-50">
    <input type="hidden" name="csrf" value="<?= $token ?>">

    <div class="mb-3">
        <label>Email</label>
        <input class="form-control" type="email" name="email" required>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required>
    </div>

    <!-- CAPTCHA -->
    <div class="mb-3">
        <label>Enter CAPTCHA</label><br>
        <img src="captcha.php?<?= time() ?>" alt="CAPTCHA">
        <input class="form-control mt-2" type="text" name="captcha" required>
    </div>

    <button class="btn btn-primary" name="login" type="submit">Login</button>
    <a class="btn btn-link" href="reset_request.php">Forgot Password?</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
