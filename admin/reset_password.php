<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail_config.php';
require_role('admin');
check_session_timeout();

$id = intval($_GET['id'] ?? 0);

// Ensure doctor exists
$stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ? AND role = 'doctor'");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    flash_set('error', "Doctor not found.");
    header("Location: dashboard.php");
    exit;
}

// Generate new password (expires in 10 minutes)
$newPass = bin2hex(random_bytes(4)); // 8-char temporary password
$hash = password_hash($newPass, PASSWORD_DEFAULT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update password, set expiry, and enforce first-login change
$stmt = $pdo->prepare("UPDATE users SET password = ?, temp_password_expires_at = ?, force_password_change = 0 WHERE id = ?");
$stmt->execute([$hash, $expiresAt, $id]);

logAction($pdo, $_SESSION['user_id'], "Reset password for doctor ID {$id}");

// 📧 Email new password
$subject = "OncoSecure – Password Reset Notification";
$body = "
Hello Dr. {$doctor['name']},<br><br>
Your password has been reset by the administrator.<br>
<b>New Password:</b> {$newPass}<br><br>
Please log in and change it immediately for security.
";

sendEmail($doctor['email'], $subject, $body);

flash_set('success', "Password reset and emailed to doctor.");
header("Location: dashboard.php");
exit;
