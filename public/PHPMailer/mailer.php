<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
 
function SendEmail($to, $subject, $body){
    // Instantiation and passing [ICODE]true[/ICODE] enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = 'smtp.gmail.com';                       // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'markcristianibe@gmail.com';            // SMTP username
        $mail->Password   = 'xtgq pzca pihs nbyx';                     // SMTP password
        $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, [ICODE]ssl[/ICODE] also accepted
        $mail->Port       = 587;                                    // TCP port to connect to
     
        //Recipients
        $mail->setFrom('markcristianibe@gmail.com', 'Floracare');
        $mail->addAddress($to);     // Add a recipient
        // $mail->addAddress('recipient2@example.com');               // Name is optional
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');
     
        // Attachments
        // $mail->addAttachment('/home/cpanelusername/attachment.txt');         // Add attachments
        // $mail->addAttachment('/home/cpanelusername/image.jpg', 'new.jpg');    // Optional name
     
        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
     
        $mail->send();
        echo 'Message has been sent';
     
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
