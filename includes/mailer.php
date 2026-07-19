<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function loadEmailTemplate($templateName, $placeholders = []) {
    $path = __DIR__ . "/templates/{$templateName}.html";
    
    if (!file_exists($path)) {
        error_log("Email Template not found: " . $path);
        return "Error loading email template.";
    }

    $templateContent = file_get_contents($path);

    foreach ($placeholders as $key => $value) {
        $templateContent = str_replace("{{" . $key . "}}", $value, $templateContent);
    }

    return $templateContent;
}

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('GMAIL_USER') ?: 'hi.genie.service@gmail.com';
        $mail->Password   = getenv('GMAIL_APP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPDebug = 0; 

        $mail->setFrom($mail->Username, 'Hi.Genie');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>