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
<?php if(!$patient) echo "<div class='alert alert-warning'>Please complete your profile.</div>"; ?>
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
        <h4>Medical History</h4>
           <a class="btn btn-sm btn-primary" href="/patient/medical_history.php">Viwe</a>
      </div>
    </div>
   
  </div>
</div>

<h4 class="mt-4">Predictions</h4>
<?php
// fetch predictions
if ($patient) {
    $stmt = $pdo->prepare("SELECT pr.*, u.name AS doctor_name FROM predictions pr JOIN users u ON pr.doctor_id = u.id WHERE pr.patient_id = ? ORDER BY pr.created_at DESC");
    $stmt->execute([$patient['id']]);
    $prs = $stmt->fetchAll();
    if ($prs) {
        echo "<table class='table'><thead><tr><th>Date</th><th>Result</th><th>Conf.</th><th>Doctor</th><th>Recommendation</th></tr></thead><tbody>";
        foreach($prs as $r){
            echo "<tr><td>{$r['created_at']}</td><td>{$r['result']}</td><td>{$r['confidence']}</td><td>".htmlspecialchars($r['doctor_name'])."</td><td>".htmlspecialchars($r['recommendation'])."</td></tr>";
        }
        echo "</tbody></table>";
    } else echo "<div class='alert alert-info'>No predictions yet.</div>";
}
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>