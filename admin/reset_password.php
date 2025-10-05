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

// Generate new password
$newPass = bin2hex(random_bytes(4)); // 8-char temporary password
$hash = password_hash($newPass, PASSWORD_DEFAULT);

// Update password
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hash, $id]);

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
