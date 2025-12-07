<?php
require_once __DIR__ . '/../includes/functions.php';

$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $otp_in = sanitize($_POST['otp'] ?? '');

        // Find user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid email.";
        } else {
            // Validate OTP
            $stmt = $pdo->prepare("
                SELECT * FROM otp 
                WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at >= NOW()
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$user['id'], $otp_in]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = "OTP invalid or expired.";
            } else {
                // OTP OK → move to password reset page
                header("Location: reset_new_password.php?email=" . urlencode($email) . "&otp=" . $otp_in);
                exit;
            }
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Verify OTP</h2>
<?php if (isset($error))
    echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="post" class="w-50">
    <input type="hidden" name="csrf" value="<?= $token ?>">

    <div class="mb-3">
        <label>Email</label>
        <input class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
    </div>

    <div class="mb-3">
        <label>OTP Code</label>
        <input class="form-control" name="otp" required>
    </div>

    <button class="btn btn-primary" type="submit">Verify OTP</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>