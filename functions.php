<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function sendVerificationCode($email, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); 
        $mail->Host = 'smtp.your-email-provider.com';
        $mail->SMTPAuth = true; 
        $mail->Username = 'immunitrack2024@gmail.com';
        $mail->Password = 'ebxt abwy pwni gwyd';
        $mail->SMTPSecure = 'tls'; 
        $mail->Port = 587;

        $mail->setFrom('immunitrack2024@gmail.com', 'ImmuniTrack');
        $mail->addAddress($email);

        $mail->isHTML(true); 
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "Your verification code is: <strong>$verificationCode</strong>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
