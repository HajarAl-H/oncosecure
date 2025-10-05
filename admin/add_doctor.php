<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();
require_once __DIR__ . '/../includes/mail_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) die('CSRF mismatch');

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $specialization = sanitize($_POST['specialization'] ?? '');
    $experience = sanitize($_POST['experience'] ?? '');

    // Certificate file
    $cert_file = '';
    if (!empty($_FILES['certificates']['name'])) {
        $cert_file = 'uploads/certificates/' . time() . '_' . basename($_FILES['certificates']['name']);
        move_uploaded_file($_FILES['certificates']['tmp_name'], __DIR__ . '/../' . $cert_file);
    }

    // Validations
    if (!$name || !$email || !$phone || !$address) {
        $error = "All fields are required.";
    } elseif (!preg_match('/@(gmail\.com|hotmail\.com|yahoo\.com|facebook\.com)$/i', $email)) {
        $error = "Email must be Gmail, Hotmail, Yahoo, or Facebook.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $error = "Phone must be 8 digits.";
    } else {
        $temp = bin2hex(random_bytes(4)); // temp password
        $hash = password_hash($temp, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users 
                (name, email, password, role, phone, address, specialization, experience, certificates, force_password_change) 
                VALUES (?,?,?,?,?,?,?,?,?,0)
            ");
            $stmt->execute([$name, $email, $hash, 'doctor', $phone, $address, $specialization, $experience, $cert_file]);
            $id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], "Added doctor ID $id");

            // 📧 Send email with credentials
            $subject = "Welcome to OncoSecure – Your Account Details";
            $body = "
                Hello Dr. {$name},<br><br>
                Your OncoSecure account has been created.<br>
                <b>Email:</b> {$email}<br>
                <b>Temporary Password:</b> {$temp}<br><br>
                Please log in and change your password upon first login.
            ";
            sendEmail($email, $subject, $body);

            flash_set('success', "Doctor created successfully and credentials emailed.");
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Email already exists.";
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Add Doctor</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post" enctype="multipart/form-data" class="w-50">
    <input type="hidden" name="csrf" value="<?= $token ?>">
    <div class="mb-3"><label>Name</label><input class="form-control" name="name" required></div>
    <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="mb-3"><label>Phone</label><input class="form-control" name="phone" maxlength="8" required></div>
    <div class="mb-3"><label>Address</label><input class="form-control" name="address" required></div>
    <div class="mb-3"><label>Specialization</label><input class="form-control" name="specialization"></div>
    <div class="mb-3"><label>Experience</label><input class="form-control" name="experience"></div>
    <div class="mb-3"><label>Certificates (PDF/Image)</label><input class="form-control" type="file" name="certificates"></div>
    <button class="btn btn-primary" type="submit">Create Doctor</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
