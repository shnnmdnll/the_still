<?php
// backend/includes/mailer.php
// Reusable PHPMailer helper para sa pagpapadala ng OTP at password reset emails

require_once __DIR__ . '/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ⚠️ PALITAN mo itong mga config value ng totoong SMTP details mo (Gmail o iba pa)
if (!defined('MAIL_HOST'))      define('MAIL_HOST', 'smtp.gmail.com');
if (!defined('MAIL_USERNAME'))  define('MAIL_USERNAME', 'shannadugho12@gmail.com');
if (!defined('MAIL_PASSWORD'))  define('MAIL_PASSWORD', 'bvzjtkkojvwbbgqp'); // Gmail App Password
if (!defined('MAIL_FROM'))      define('MAIL_FROM', 'shannadugho12@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Pahingahan by The Still');
if (!defined('MAIL_PORT'))      define('MAIL_PORT', 587);

// ⚠️ PALITAN ng totoong base URL ng site mo (dapat 'the_still', hindi 'pahingahan')
if (!defined('SITE_BASE_URL'))  define('SITE_BASE_URL', 'http://localhost/the_still');

// Email ng owner na tatanggap ng mga inquiry mula sa Contact page
if (!defined('OWNER_EMAIL')) define('OWNER_EMAIL', 'thestill828@gmail.com');

/**
 * Nagpapadala ng contact form inquiry papunta sa email ng owner.
 * Ang reply-to ay itinatakda papunta sa email ng nag-inquire, para
 * direktang makasagot ang owner nang hindi na kailangang mag-copy-paste.
 * @return bool true kung successful ang pagpadala, false kung na-fail.
 */
function sendContactFormEmail(string $fromName, string $fromEmail, string $message): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress(OWNER_EMAIL);
        $mail->addReplyTo($fromEmail, $fromName);

        $safeMessage = nl2br(htmlspecialchars($message));

        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Inquiry from ' . $fromName;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#3c6b41;'>New inquiry from Pahingahan Contact Page</h2>
                <p><strong>Name:</strong> {$fromName}</p>
                <p><strong>Email:</strong> {$fromEmail}</p>
                <p><strong>Message:</strong></p>
                <p style='background:#f7f0d8; padding:14px; border-radius:8px;'>{$safeMessage}</p>
                <p style='color:#8a8266; font-size:.85rem;'>Reply directly to this email to respond to {$fromName}.</p>
            </div>
        ";
        $mail->AltBody = "New inquiry from {$fromName} ({$fromEmail}):\n\n{$message}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Nagpapadala ng 6-digit OTP code papunta sa bagong-register na guest.
 * @return bool true kung successful ang pagpadala, false kung na-fail.
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Your Pahingahan verification code';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#3c6b41;'>Welcome to Pahingahan, {$toName}!</h2>
                <p>Gamitin ang code sa baba para i-verify ang email mo. Valid lang ito for 10 minutes:</p>
                <p style='text-align:center; margin: 24px 0;'>
                    <span style='display:inline-block; font-size:2rem; font-weight:700; letter-spacing:8px; background:#f7f0d8; color:#3c6b41; padding:14px 24px; border-radius:10px;'>{$otp}</span>
                </p>
                <p>Kung hindi ikaw ang nag-request nito, i-ignore mo na lang itong email.</p>
            </div>
        ";
        $mail->AltBody = "Ang verification code mo ay: {$otp} (valid for 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Nagpapadala ng password reset link papunta sa user.
 * @return bool true kung successful ang pagpadala, false kung na-fail.
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $token): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $resetLink = rtrim(SITE_BASE_URL, '/') . '/reset_password.php?token=' . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Reset your Pahingahan password';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#3c6b41;'>Password Reset Request</h2>
                <p>Hi {$toName}, we received a request to reset your password. Click the button below to choose a new one. This link is valid for 30 minutes.</p>
                <p style='text-align:center; margin: 24px 0;'>
                    <a href='{$resetLink}' style='background:#5c8a3a; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold; display:inline-block;'>Reset Password</a>
                </p>
                <p>If you didn't request this, you can safely ignore this email — your password will stay the same.</p>
                <p>If the button doesn't work, paste this link into your browser:</p>
                <p style='word-break:break-all; color:#4a7130;'>{$resetLink}</p>
            </div>
        ";
        $mail->AltBody = "Reset your Pahingahan password: {$resetLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}