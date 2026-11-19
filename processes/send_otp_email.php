<?php

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── FILL THESE TWO LINES IN ──────────────────────────────────
define('SMTP_GMAIL_ADDRESS',  'antonygaza6@gmail.com');   // the Gmail account sending the OTP
define('SMTP_GMAIL_APP_PASS', 'fski yvxy xwxj gsgt');          // the 16-character App Password from Google
// ──────────────────────────────────────────────────────────────

/**
 * Sends the OTP code to the given email address.
 * Returns true on success, false on failure.
 */
function send_otp_email(string $toEmail, string $toName, string $otpCode): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_GMAIL_ADDRESS;
        $mail->Password   = SMTP_GMAIL_APP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(SMTP_GMAIL_ADDRESS, 'CertsVerify Security');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Your CertsVerify Admin Verification Code';
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:480px;margin:0 auto;'>
                <h2 style='color:#1e3a5f;'>Admin Login Verification</h2>
                <p>Hi {$toName},</p>
                <p>Use the code below to complete your admin login to CertsVerify:</p>
                <div style='background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;
                            padding:18px;text-align:center;margin:20px 0;'>
                    <span style='font-size:32px;font-weight:800;letter-spacing:8px;color:#166534;'>
                        {$otpCode}
                    </span>
                </div>
                <p style='color:#6b7280;font-size:13px;'>
                    This code expires in 5 minutes. If you did not attempt to log in,
                    you can safely ignore this email.
                </p>
            </div>
        ";
        $mail->AltBody = "Your CertsVerify admin verification code is: $otpCode (expires in 5 minutes)";

        $mail->send();
        return true;

    } catch (Exception $e) {
        $errorDetail = $mail->ErrorInfo ?: $e->getMessage();
        error_log("OTP email failed: " . $errorDetail);

        // Store the real error so it can be shown on the verify page during setup/debugging
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['sessions']['SysAdmin'])) {
            $_SESSION['sessions']['SysAdmin']['otp_email_error'] = $errorDetail;
        }
        return false;
    }
}
?>
