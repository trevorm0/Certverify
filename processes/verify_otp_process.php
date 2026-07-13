<?php
session_start();

// Must have a pending OTP session under the SysAdmin namespace
$otp = $_SESSION['sessions']['SysAdmin'] ?? null;

if (empty($otp) || empty($otp['otp_pending'])) {
    header('Location: ../admin/login.php');
    exit;
}

$submitted = trim($_POST['otp_full'] ?? '');

// ── Check if expired ──
if (time() > $otp['otp_expires']) {
    $_SESSION['sessions']['SysAdmin']['otp_error'] = 'Your code has expired. Please generate a new one.';
    header('Location: ../admin/verify_otp.php');
    exit;
}

// ── Track failed attempts ──
$otp['otp_attempts'] = ($otp['otp_attempts'] ?? 0) + 1;
$_SESSION['sessions']['SysAdmin']['otp_attempts'] = $otp['otp_attempts'];

// ── Too many attempts → wipe only the SysAdmin namespace ──
if ($otp['otp_attempts'] > 3) {
    unset($_SESSION['sessions']['SysAdmin']);
    header('Location: ../admin/login.php?error=too_many');
    exit;
}

// ── Wrong code ──
if ($submitted !== $otp['otp_code']) {
    $left = 3 - $otp['otp_attempts'];
    $_SESSION['sessions']['SysAdmin']['otp_error'] = 'Incorrect code. ' . ($left > 0 ? "$left attempt(s) remaining." : 'Redirecting to login...');
    if ($left <= 0) {
        unset($_SESSION['sessions']['SysAdmin']);
        header('Location: ../admin/login.php?error=too_many');
        exit;
    }
    header('Location: ../admin/verify_otp.php');
    exit;
}

// ── Code is correct — promote OTP data into a real SysAdmin session ──
$user_id    = $otp['otp_user_id'];
$first_name = $otp['otp_first_name'];
$last_name  = $otp['otp_last_name'];
$email      = $otp['otp_email'];

$loginToken = bin2hex(random_bytes(32));

// Replace the SysAdmin namespace with the final logged-in session data
$_SESSION['sessions']['SysAdmin'] = [
    'user_id'     => $user_id,
    'first_name'  => $first_name,
    'last_name'   => $last_name,
    'email'       => $email,
    'role'        => 'SysAdmin',
    'login_token' => $loginToken,
];

setcookie('cv_token_admin', $loginToken, [
    'expires'  => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);

header('Location: ../admin/dashboard.php');
exit;
?>
