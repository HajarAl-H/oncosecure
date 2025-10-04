<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();

$id = intval($_GET['id'] ?? 0);

// Ensure doctor exists
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ? AND role = 'doctor'");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    flash_set('error', "Doctor not found.");
    header("Location: dashboard.php");
    exit;
}

// Generate new password
$newPass = bin2hex(random_bytes(4)); // 8-char random password
$hash = password_hash($newPass, PASSWORD_DEFAULT);

// Update DB
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hash, $id]);

// Log action
logAction($pdo, $_SESSION['user_id'], "Reset password for doctor ID {$id}");

// Show new password
flash_set('success', "Password reset for doctor (ID {$id}). New password: <b>$newPass</b>");

// Redirect back
header("Location: dashboard.php");
exit;
