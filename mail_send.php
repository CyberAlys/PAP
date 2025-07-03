<?php
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once 'mailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAILER_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAILER_USERNAME;
        $mail->Password   = MAILER_PASSWORD;
        $mail->SMTPSecure = MAILER_SECURE;
        $mail->Port       = MAILER_PORT;

        // Recipients
        $mail->setFrom(MAILER_FROM, MAILER_FROMNAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
?>