<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    // Validation
    if ($pw !== $pw2) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pw)) {
        $error = "Password must be at least 8 chars, include upper & lower case letters, a number and special char.";
    } else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=?, temp_password_expires_at = NULL, force_password_change=1 WHERE id=?");
        $stmt->execute([$hash, $uid]);
        logAction($pdo, $uid, "Changed password on first login");
        flash_set('success', 'Password changed successfully.');
        header("Location: dashboard.php");
        exit;
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Change Password (First Login)</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($m = flash_get('success')) echo "<div class='alert alert-success'>$m</div>"; ?>

<form method="post" class="w-50">
    <input type="hidden" name="csrf" value="<?= $token ?>">
    <div class="mb-3">
        <label>New Password</label>
        <input class="form-control" type="password" name="password" required>
    </div>
    <div class="mb-3">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="password2" required>
    </div>
    <button class="btn btn-primary" type="submit">Change Password</button>
</form>
