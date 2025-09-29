<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OncoSecure - Breast Cancer Care Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/breast-cancer-theme.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">OncoSecure</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/add_doctor.php">Add Doctor</a></li>
          <?php elseif ($_SESSION['role'] === 'doctor'): ?>
            <li class="nav-item"><a class="nav-link" href="/doctor/dashboard.php">Patients</a></li>
          <?php elseif ($_SESSION['role'] === 'patient'): ?>
            <li class="nav-item"><a class="nav-link" href="/patient/dashboard.php">My Health</a></li>
            <li class="nav-item"><a class="nav-link" href="/patient/appointments.php">Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="/patient/profile.php">Profile</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav ms-auto">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><span class="nav-link">Hi, <?php echo htmlspecialchars($_SESSION['role']); ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/auth/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="main-content">
  <div class="container">
    
    <!-- Awareness Banner -->
    <div class="awareness-banner d-none d-md-block">
      <div class="alert alert-info text-center" style="background: linear-gradient(135deg, #fce4ec 0%, #e1f5fe 100%);">
        🎗️ <strong>Breast Cancer Awareness:</strong> Early detection saves lives. 🎗️
      </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if (function_exists('flash_get')): ?>
      <?php if ($success = flash_get('success')): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
    <?php endif; ?>