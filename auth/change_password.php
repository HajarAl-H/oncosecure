<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } 
        // Check if new password is same as current
        elseif (password_verify($new_password, $user['password'])) {
            $error = "New password cannot be the same as your current password.";
        }
        // Validate new password strength
        elseif (strlen($new_password) < 10) {
            $error = "Password must be at least 10 characters long.";
        } 
        elseif (!preg_match("#[0-9]+#", $new_password)) {
            $error = "Password must include at least one number!";
        } 
        elseif (!preg_match("#[a-z]+#", $new_password)) {
            $error = "Password must include at least one lowercase letter!";
        } 
        elseif (!preg_match("#[A-Z]+#", $new_password)) {
            $error = "Password must include at least one uppercase letter!";
        } 
        elseif (!preg_match("#\W+#", $new_password)) {
            $error = "Password must include at least one special character!";
        } 
        elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } 
        else {
            // Update password
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['user_id']]);
            
            logAction($pdo, $_SESSION['user_id'], "Password changed successfully");
            
            // Set success message
            $_SESSION['password_changed'] = true;
            
            // Logout and redirect to login with success message
            session_destroy();
            header("Location: login.php?password_changed=1");
            exit;
        }
    }
}

$token = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Change Password</h4>
                </div>
                <div class="card-body">
                    
                    
                    <form method="post" id="changePasswordForm">
                        <input type="hidden" name="csrf" value="<?= $token ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required
                                   minlength="10" autocomplete="current-password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required
                                   minlength="10" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$"
                                   title="Must contain at least one number, one uppercase, one lowercase letter, one special character, and at least 10 or more characters"
                                   autocomplete="new-password">
                            <div class="form-text">
                                <small>Password must contain:
                                    <ul>
                                        <li>At least 10 characters</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one lowercase letter</li>
                                        <li>At least one number</li>
                                        <li>At least one special character</li>
                                    </ul>
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="10" autocomplete="new-password">
                            <div class="invalid-feedback" id="passwordMismatch">
                                Passwords do not match!
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                            <a href="<?= get_user_dashboard() ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const mismatchError = document.getElementById('passwordMismatch');
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        document.getElementById('confirm_password').classList.add('is-invalid');
        mismatchError.style.display = 'block';
    } else {
        document.getElementById('confirm_password').classList.remove('is-invalid');
        mismatchError.style.display = 'none';
    }
});

// Show password strength
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBadge = document.getElementById('password-strength');
    
    if (!strengthBadge) {
        const badge = document.createElement('div');
        badge.id = 'password-strength';
        badge.className = 'mt-2';
        this.parentNode.appendChild(badge);
    }
    
    updateStrengthMeter(password);
});

function updateStrengthMeter(password) {
    const strengthBadge = document.getElementById('password-strength');
    if (!strengthBadge) return;
    
    let strength = 0;
    let messages = [];
    
    // Length check
    if (password.length >= 10) strength += 1;
    else messages.push("At least 10 characters");
    
    // Contains numbers
    if (/\d/.test(password)) strength += 1;
    else messages.push("Include numbers");
    
    // Contains lowercase
    if (/[a-z]/.test(password)) strength += 1;
    else messages.push("Include lowercase letters");
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) strength += 1;
    else messages.push("Include uppercase letters");
    
    // Contains special chars
    if (/[\W_]/.test(password)) strength += 1;
    else messages.push("Include special characters");
    
    // Update UI
    let strengthText = '';
    let strengthClass = '';
    
    switch(strength) {
        case 0:
        case 1:
            strengthText = 'Very Weak';
            strengthClass = 'text-danger';
            break;
        case 2:
            strengthText = 'Weak';
            strengthClass = 'text-warning';
            break;
        case 3:
            strengthText = 'Moderate';
            strengthClass = 'text-info';
            break;
        case 4:
            strengthText = 'Strong';
            strengthClass = 'text-primary';
            break;
        case 5:
            strengthText = 'Very Strong';
            strengthClass = 'text-success';
            break;
    }
    
    strengthBadge.innerHTML = `
        <div class="progress mb-2" style="height: 5px;">
            <div class="progress-bar ${strengthClass}" role="progressbar" 
                 style="width: ${strength * 20}%" 
                 aria-valuenow="${strength * 20}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
        <small class="${strengthClass}">${strengthText}</small>
        ${messages.length > 0 ? `<div><small class="text-muted">Missing: ${messages.join(', ')}</small></div>` : ''}
    `;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>