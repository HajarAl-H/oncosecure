<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) { $error = "Invalid request."; }
    else {
        $email = sanitize($_POST['email'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) $error = "Email not found.";
        else {
            $otp = gen_otp();
            $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            $stmt = $pdo->prepare("INSERT INTO otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $otp, $expires]);
            logAction($pdo, $user['id'], "OTP generated for reset");

            // ============ SEND OTP VIA EMAIL (PHPMailer) ============
            // For demo we store OTP in session (remove in prod)
            $_SESSION['demo_otp'] = $otp;
            // In production use PHPMailer to send $otp to $email via SMTP
            // ========================================================

            flash_set('success', 'OTP sent to the account email (demo shows OTP).');
            header("Location: reset_verify.php?email=" . urlencode($email));
            exit;
        }
    }
}
$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Request Password Reset</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>
<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">
  <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
  <button class="btn btn-primary" type="submit">Send OTP</button>
</form>
<?php if(!empty($_SESSION['demo_otp'])) echo "<div class='alert alert-info'>Demo OTP: " . $_SESSION['demo_otp'] . "</div>"; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>