<?php
// includes/functions.php
// This file contains various helper functions for the application
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// ---- Sanitization ----
// Sanitizes a string by trimming and encoding it with htmlspecialchars to prevent XSS attacks
function sanitize($v){
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// ---- CSRF ----
// Generates a CSRF token and stores it in the session
function csrf_token(){
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
// Checks if the submitted CSRF token matches the one stored in the session
function check_csrf($token){
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---- Logging ----
// Logs an action performed by a user
function logAction($pdo, $user_id, $action){
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $stmt->execute([$user_id ?: null, $action, $ip]);
}

// ---- Account lockout ----
// Checks if a user is locked out based on their failed login attempts
function is_locked($user_row){
    if (!$user_row) return false;
    if (!empty($user_row['lockout_until']) && strtotime($user_row['lockout_until']) > time()) return true;
    return false;
}
// Records a failed login attempt for a user
function record_failed_login($pdo, $user_id){
    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    $stmt = $pdo->prepare("SELECT failed_attempts FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $count = $row ? (int)$row['failed_attempts'] : 0;
    if ($count >= 5) {
        $lock_until = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        $stmt = $pdo->prepare("UPDATE users SET lockout_until = ?, failed_attempts = 0 WHERE id = ?");
        $stmt->execute([$lock_until, $user_id]);
    }
}
// Resets a user's failed login attempts and lockout status
function reset_failed_attempts($pdo, $user_id){
    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
}

// ---- Session timeout check (include on protected pages) ----
// Checks if a user's session has timed out and redirects them to the login page if it has
function check_session_timeout($timeout_seconds = 240){
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)){
        session_unset();
        session_destroy();
        header("Location: /auth/login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ---- Role helpers ----
// Checks if a user is logged in and redirects them to the login page if they are not
function require_login(){
    if (empty($_SESSION['user_id'])) {
        header("Location: /auth/login.php");
        exit;
    }
}
// Checks if a user has the required role and redirects them to the login page if they do not
function require_role($role){
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: /auth/login.php");
        exit;
    }
}

// ---- Simple flash helpers ----
// Sets a flash message in the session
function flash_set($key, $msg){ $_SESSION[$key] = $msg; }
// Gets a flash message from the session and removes it
function flash_get($key){ $v = $_SESSION[$key] ?? null; if ($v) unset($_SESSION[$key]); return $v; }

// ---- OTP generator (string 6 digits) ----
// Generates a random 6-digit OTP
function gen_otp(){ return strval(random_int(100000, 999999)); }
