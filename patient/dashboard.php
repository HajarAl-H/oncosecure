<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('patient');
check_session_timeout();
require_once __DIR__ . '/../includes/header.php';

$uid = $_SESSION['user_id'];
// get patient id
$stmt = $pdo->prepare("SELECT p.* , u.email, u.name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->execute([$uid]);
$patient = $stmt->fetch();

?>
<h2>Patient Dashboard</h2>
<?php if (!$patient)
  echo "<div class='alert alert-warning'>Please complete your profile.</div>"; ?>
<div class="row">
  <div class="col-md-6">
    <h4>Profile</h4>
    <p><strong>Name:</strong> <?= htmlspecialchars($patient['name'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($patient['email'] ?? '') ?></p>
    <p><strong>Age:</strong> <?= htmlspecialchars($patient['age'] ?? '') ?></p>
    <a class="btn btn-sm btn-primary" href="/patient/profile.php">Edit Profile</a>
  </div>
  <div class="col-md-6">
    <div class="row">
      <div class="col-md-6">
        <h4>Appointments</h4>
        <a class="btn btn-sm btn-primary" href="/patient/appointments.php">Manage Appointments</a>
      </div>

    </div>
    <br>
    <div class="row">
      <div class="col-md-6">
        <h4>View & Edit Medical History</h4>
        <a class="btn btn-sm btn-primary" href="/patient/medical_history.php">Viwe</a>
      </div>
    </div>

  </div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>