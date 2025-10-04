<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('doctor');
check_session_timeout();

$user_id = $_SESSION['user_id'] ?? null;

// fetch doctor details
$stmt = $pdo->prepare("
    SELECT name, email, phone, address, specialization, experience, certificates, created_at
    FROM users
    WHERE id = ? AND role = 'doctor'
");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    flash_set('error', 'Doctor not found.');
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2>My Profile</h2>

<table class="table w-50">
    <tr>
        <th>Name</th>
        <td><?= htmlspecialchars($doctor['name']) ?></td>
    </tr>
    <tr>
        <th>Email</th>
        <td><?= htmlspecialchars($doctor['email']) ?></td>
    </tr>
    <tr>
        <th>Phone</th>
        <td><?= htmlspecialchars($doctor['phone']) ?></td>
    </tr>
    <tr>
        <th>Address</th>
        <td><?= htmlspecialchars($doctor['address']) ?></td>
    </tr>
    <tr>
        <th>Specialization</th>
        <td><?= htmlspecialchars($doctor['specialization']) ?></td>
    </tr>
    <tr>
        <th>Experience</th>
        <td><?= htmlspecialchars($doctor['experience']) ?></td>
    </tr>
    <tr>
        <th>Certificates</th>
        <td>
            <?php if ($doctor['certificates']): ?>
                <a href="/<?= htmlspecialchars($doctor['certificates']) ?>" target="_blank">View Certificate</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th>Account Created</th>
        <td><?= $doctor['created_at'] ?></td>
    </tr>
</table>

<a href="dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
