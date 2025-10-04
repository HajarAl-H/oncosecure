<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();

$id = intval($_GET['id'] ?? 0);

// Delete doctor
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role='doctor'");
$stmt->execute([$id]);

logAction($pdo, $_SESSION['user_id'], "Deleted doctor ID $id");
flash_set('success', "Doctor deleted.");
header("Location: dashboard.php");
exit;
