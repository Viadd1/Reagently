<?php
/*
This is a template function for sending emails that is called in other files.
To set up the email sending functionality, the following prerequisites are required:

1.  Download the Composer file manager for PHP: 
    https://getcomposer.org/download/
    When asked for the PHP version, locate php.exe in your server stack

2.  Download PHPMailer and phpdotenv by running the following two commands in the project folder:
    composer require phpmailer/phpmailer
    composer require vlucas/phpdotenv

    Note: The project folder must be stored in the server stack folder! 

3.  Create a file .env in the project folder, with the following content (if using a Gmail account):
    SMTP_HOST = "smtp.gmail.com"
    SMTP_USER = "user@gmail.com"
    SMTP_PASS - "App password to the Gmail account"

*/

//Create a namespace with PHPMailer classes and Dotenv
namespace Email;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load utilities from Composer
require_once 'init.php';
require BASE_PATH . '/vendor/autoload.php';

// Load variables from .env
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Function for sending email
function sendEmail(string $toAddress, string $toName, string $subject, string $body):
bool {
    $mail = new PHPMailer(true); // true = enable exceptions

    try { 
        // --- SMTP Settings --- 
        $mail->isSMTP(); 
        $mail->Host         = $_ENV['SMTP_HOST'];               // Gmail's SMTP server 
        $mail->SMTPAuth     = true;                             // Require login
        $mail->Username     = $_ENV['SMTP_USER'];               // Your Gmail address
        $mail->Password     = $_ENV['SMTP_PASS'];               // Gmail App Password (see note)
        $mail->SMTPSecure   = PHPMailer::ENCRYPTION_STARTTLS;   // Use TLS encryption
        $mail->Port         = 587; // TLS port
        
        // --- Who is the email from? ---
        $mail->setFrom($_ENV['SMTP_USER'], 'Reagently');
        
        // --- Who is it going to? ---

        $mail->addAddress($toAddress, $toName);
        
        // --- Email content ---
        $mail->isHTML(true); // Allow HTML in the email body
        $mail->Subject = $subject;
        $mail->Body = $body; // HTML version
        $mail->AltBody = strip_tags($body); // Plain text fallback
        $mail->send();
        return true;

    } catch (Exception $e) { 
        // $mail->ErrorInfo contains the detailed error
        error_log('Email failed: ' . $mail->ErrorInfo); 
        return false; 
    }
}

?>