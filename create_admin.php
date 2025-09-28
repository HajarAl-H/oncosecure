<?php
// create_admin.php - run once to create admin (delete after use)
require_once __DIR__ . '/includes/db.php';
$name = 'System Admin';
$email = 'admin@oncosecure.com';
$pass = password_hash('Admin@1234', PASSWORD_DEFAULT);
$role = 'admin';
try {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
    $stmt->execute([$name,$email,$pass,$role]);
    echo "Admin created: $email (password: Admin@1234)";
} catch (PDOException $e){
    echo "Error: " . $e->getMessage();
}
?>