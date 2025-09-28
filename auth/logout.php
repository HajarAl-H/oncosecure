<?php
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['user_id'])) {
    logAction($pdo, $_SESSION['user_id'], "Logout");
}
session_unset();
session_destroy();
header("Location: /auth/login.php");
exit;
?>