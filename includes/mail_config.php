<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alhajoor21@gmail.com'; // ✅ your Gmail
        $mail->Password = 'egwd ozqk ycoi iwbe'; // ✅ App Password (no spaces if needed)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('alhajoor21@gmail.com', 'OncoSecure System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
