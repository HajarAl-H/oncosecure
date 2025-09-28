<?php
// index.php - redirect to login or dashboard depending on session
session_start();
if (!empty($_SESSION['user_id'])){
    $role = $_SESSION['role'] ?? 'patient';
    header("Location: /" . $role . "/dashboard.php");
    exit;
} else {
    header("Location: /auth/login.php");
    exit;
}
?>