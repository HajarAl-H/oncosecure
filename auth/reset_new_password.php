<?php
require_once __DIR__ . '/../includes/functions.php';

$email = $_GET['email'] ?? '';
$otp = $_GET['otp'] ?? '';

if (!$email || !$otp) {
    die("Invalid password reset request.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!check_csrf($_POST['csrf'] ?? '')) {
        $error = "Invalid request.";
    } else {

        $newpw = $_POST['password'] ?? '';
        $confirmpw = $_POST['confirm_password'] ?? '';

        if ($newpw !== $confirmpw) {
            $error = "Passwords do not match.";
        } elseif (strlen($newpw) < 8) {
            $error = "Password must be at least 8 characters.";
        } else {
            // Find user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "Invalid email.";
            } else {
                // Validate OTP again before updating
                $stmt = $pdo->prepare("
                    SELECT * FROM otp
                    WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at >= NOW()
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt->execute([$user['id'], $otp]);
                $row = $stmt->fetch();

                if (!$row) {
                    $error = "OTP expired or invalid.";
                } else {
                    // Update password
                    $hash = password_hash($newpw, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hash, $user['id']]);

                    // Mark OTP used
                    $stmt = $pdo->prepare("UPDATE otp SET is_used = 1 WHERE id = ?");
                    $stmt->execute([$row['id']]);

                    flash_set('success', "Password updated. Please login.");
                    header("Location: login.php");
                    exit;
                }
            }
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Set New Password</h2>

<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="post" class="w-50">
  <input type="hidden" name="csrf" value="<?= $token ?>">

  <div class="mb-3">
    <label>New Password</label>
    <input type="password" class="form-control" name="password" required>
  </div>

  <div class="mb-3">
    <label>Confirm New Password</label>
    <input type="password" class="form-control" name="confirm_password" required>
  </div>

  <button type="submit" class="btn btn-success">Update Password</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
