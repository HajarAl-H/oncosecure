<?php
// includes/header.php - Simple Working Version
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OncoSecure - Breast Cancer Care Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <!-- Custom Breast Cancer Theme CSS -->
  <style>
    /* Inline CSS for immediate working - you can move this to external file later */
    :root {
        --bc-primary: #e91e63;
        --bc-primary-dark: #c2185b;
        --bc-primary-light: #f8bbd9;
        --bc-secondary: #2196f3;
        --bc-success: #4caf50;
        --bc-warning: #ff9800;
        --bc-danger: #f44336;
        --bc-light: #fce4ec;
        --bc-gradient-primary: linear-gradient(135deg, #e91e63 0%, #ad1457 100%);
        --bc-shadow-md: 0 4px 12px rgba(233, 30, 99, 0.15);
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #fce4ec 0%, #fff 50%, #e1f5fe 100%);
        min-height: 100vh;
        padding-top: 80px;
    }

    .navbar {
        background: var(--bc-gradient-primary) !important;
        box-shadow: var(--bc-shadow-md);
        padding: 1rem 0;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        color: white !important;
    }

    .navbar-brand::before {
        content: "🎗️ ";
        margin-right: 0.5rem;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
    }

    .btn-primary {
        background: var(--bc-gradient-primary);
        border: none;
        border-radius: 25px;
        padding: 0.6rem 2rem;
        font-weight: 600;
        box-shadow: var(--bc-shadow-md);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #c2185b 0%, #880e4f 100%);
    }

    .alert {
        border: none;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
        color: #2e7d32;
        border-left: 4px solid var(--bc-success);
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
        color: #c62828;
        border-left: 4px solid var(--bc-danger);
    }

    .alert-info {
        background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.05) 100%);
        color: #1565c0;
        border-left: 4px solid var(--bc-secondary);
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 0.8rem 1.2rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--bc-primary);
        box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
    }

    .table {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--bc-shadow-md);
    }

    .table thead th {
        background: var(--bc-gradient-primary);
        color: white;
        font-weight: 600;
        border: none;
    }

    .container {
        max-width: 1200px;
        padding: 2rem 15px;
    }

    h1, h2, h3, h4, h5, h6 {
        color: var(--bc-primary-dark);
        font-weight: 600;
    }

    h1::before, h2::before {
        content: "🎗️ ";
        margin-right: 0.5rem;
    }

    .awareness-banner {
        margin-bottom: 2rem;
    }
  </style>
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
            <li class="nav-item">
              <a class="nav-link" href="/admin/dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/admin/add_doctor.php">Add Doctor</a>
            </li>
          <?php elseif ($_SESSION['role'] === 'doctor'): ?>
            <li class="nav-item">
              <a class="nav-link" href="/doctor/dashboard.php">Patients</a>
            </li>
          <?php elseif ($_SESSION['role'] === 'patient'): ?>
            <li class="nav-item">
              <a class="nav-link" href="/patient/dashboard.php">My Health</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/patient/appointments.php">Appointments</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/patient/profile.php">Profile</a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav ms-auto">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <span class="nav-link">Hi, <?php echo htmlspecialchars($_SESSION['role']); ?></span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/auth/logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="/auth/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/auth/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  
  <!-- Breast Cancer Awareness Banner -->
  <div class="awareness-banner d-none d-md-block">
    <div class="alert alert-info text-center" style="background: linear-gradient(135deg, #fce4ec 0%, #e1f5fe 100%);">
      🎗️ <strong>Breast Cancer Awareness:</strong> Early detection saves lives. Schedule your screening today. 🎗️
    </div>
  </div>
  
  <!-- Flash Messages -->
  <?php if (function_exists('flash_get')): ?>
    <?php if ($success = flash_get('success')): ?>
      <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger">
        ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>