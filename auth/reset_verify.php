<?php
require_once __DIR__ . '/../includes/functions.php';

$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $otp_in = sanitize($_POST['otp'] ?? '');
        $newpw = $_POST['password'] ?? '';

        // Find user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid request.";
        } else {
            // Check OTP validity
            $stmt = $pdo->prepare("
                SELECT * FROM otp 
                WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at >= NOW()
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$user['id'], $otp_in]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = "OTP invalid or expired.";
            } elseif (strlen($newpw) < 8) {
                $error = "Password must be at least 8 characters.";
            } else {
                // ✅ Update password
                $hash = password_hash($newpw, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user['id']]);

                // Mark OTP as used
                $stmt = $pdo->prepare("UPDATE otp SET is_used = 1 WHERE id = ?");
                $stmt->execute([$row['id']]);

                logAction($pdo, $user['id'], "Password reset via OTP");
                flash_set('success', 'Password updated. Please login.');
                header("Location: login.php");
                exit;
            }
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Verify OTP & Reset Password</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label>Email</label>
    <input class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
  </div>

  <div class="mb-3">
    <label>OTP</label>
    <input class="form-control" name="otp" required>
  </div>

  <div class="mb-3">
    <label>New Password</label>
    <input class="form-control" type="password" name="password" required>
  </div>

  <button class="btn btn-primary" type="submit">Set New Password</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
