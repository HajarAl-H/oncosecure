<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OncoSecure</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{padding-top:70px}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">OncoSecure</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if(!empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><span class="nav-link">Hi, <?= htmlspecialchars($_SESSION['role']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/auth/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
