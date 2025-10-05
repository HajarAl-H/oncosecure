<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Email not found.";
        } else {
            // 🧹 Clean old OTPs first
            $pdo->exec("DELETE FROM otp WHERE expires_at < NOW()");

            // 🔑 Generate fresh OTP
            $otp = gen_otp();
            $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $stmt = $pdo->prepare("INSERT INTO otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $otp, $expires]);
            logAction($pdo, $user['id'], "OTP generated for password reset");

            // 📧 Send email
            $subject = "OncoSecure - Password Reset OTP";
            $body = "
                <p>Hello,</p>
                <p>Your OTP for password reset is: <b>$otp</b></p>
                <p>This code will expire in 10 minutes.</p>
            ";

            if (sendEmail($email, $subject, $body)) {
                flash_set('success', 'An OTP has been sent to your email address.');
                header("Location: reset_verify.php?email=" . urlencode($email));
                exit;
            } else {
                $error = "Failed to send OTP email. Please try again later.";
            }
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
  <div class="mb-3">
    <label>Email</label>
    <input class="form-control" type="email" name="email" required>
  </div>
  <button class="btn btn-primary" type="submit">Send OTP</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
