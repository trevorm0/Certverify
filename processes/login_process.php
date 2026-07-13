<?php
session_start();

$mysqli = require __DIR__ . "/db.php";
require_once __DIR__ . "/log_activity.php";

$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';
$role     = $_POST['role']     ?? '';

// ── Map role to table and redirect ──
$roleMap = [
    'Graduate'  => ['table' => 'graduate',  'redirect' => 'http://localhost/CertsVerify/graduate/dashboard.php',  'login' => 'http://localhost/CertsVerify/graduate/login.php'],
    'Employer'  => ['table' => 'employer',  'redirect' => 'http://localhost/CertsVerify/employer/dashboard.php',  'login' => 'http://localhost/CertsVerify/employer/login.php'],
    'Registrar' => ['table' => 'registrar', 'redirect' => 'http://localhost/CertsVerify/registrar/dashboard.php', 'login' => 'http://localhost/CertsVerify/registrar/login.php'],
    'SysAdmin'  => ['table' => 'sysadmin',  'redirect' => 'http://localhost/CertsVerify/admin/dashboard.php',     'login' => 'http://localhost/CertsVerify/admin/login.php'],
];

if (!isset($roleMap[$role])) {
    echo "<script>alert('Invalid role.')</script>";
    echo "<script>document.location='http://localhost/CertsVerify/index.php';</script>";
    exit;
}

$table    = $roleMap[$role]['table'];
$redirect = $roleMap[$role]['redirect'];
$loginUrl = $roleMap[$role]['login'];

// ── Look up user ──
$safeEmail = mysqli_real_escape_string($mysqli, $email);
$result    = mysqli_query($mysqli, "SELECT * FROM $table WHERE email='$safeEmail' LIMIT 1");
$user      = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password'])) {
    log_action($mysqli, $role, 0, 'LOGIN_FAILED', "Failed login attempt for email: $email");
    echo "<script>alert('Incorrect email or password.')</script>";
    echo "<script>document.location='$loginUrl';</script>";
    exit;
}

// ── Registrar approval status check ──
if ($role === 'Registrar') {
    $status = $user['status'] ?? 'pending';

    if ($status === 'pending') {
        echo "<script>alert('Your account is pending admin approval.\\n\\nYou will be able to log in once a system administrator has reviewed and approved your account.\\n\\nPlease check back later or contact your institution.')</script>";
        echo "<script>document.location='$loginUrl';</script>";
        exit;
    }

    if ($status === 'rejected') {
        $reg_id = (int)$user['id'];
        mysqli_query($mysqli, "DELETE FROM registrar WHERE id='$reg_id'");
        echo "<script>alert('Your registrar account application was rejected by the administrator.\\n\\nYour registration data has been removed from the system.\\n\\nIf you believe this is an error, please contact your institution directly.')</script>";
        echo "<script>document.location='$loginUrl';</script>";
        exit;
    }

    if (isset($user['is_active']) && $user['is_active'] == 0) {
        echo "<script>alert('Your registrar account has been deactivated.\\n\\nPlease contact the system administrator.')</script>";
        echo "<script>document.location='$loginUrl';</script>";
        exit;
    }
}

// ── Employer approval status check ──
if ($role === 'Employer') {
    $status = $user['status'] ?? 'pending';

    if ($status === 'pending') {
        echo "<script>alert('Your employer account is pending admin approval.\\n\\nYou will be able to log in once a system administrator has reviewed and approved your account.')</script>";
        echo "<script>document.location='$loginUrl';</script>";
        exit;
    }

    if ($status === 'rejected') {
        $emp_id = (int)$user['id'];
        mysqli_query($mysqli, "DELETE FROM employer WHERE id='$emp_id'");
        echo "<script>alert('Your employer account application was rejected by the administrator.\\n\\nIf you believe this is an error, please contact the system administrator.')</script>";
        echo "<script>document.location='$loginUrl';</script>";
        exit;
    }
}

// ── SysAdmin: require 2FA before creating full session ──
if ($role === 'SysAdmin') {
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Namespace OTP data under 'SysAdmin' key — does NOT touch other roles' sessions
    $_SESSION['sessions']['SysAdmin'] = [
        'otp_pending'    => true,
        'otp_user_id'    => $user['id'],
        'otp_first_name' => $user['firstname'],
        'otp_last_name'  => $user['lastname'],
        'otp_email'      => $user['email'],
        'otp_code'       => $otpCode,
        'otp_expires'    => time() + 300,
        'otp_attempts'   => 0,
    ];

    // Send the OTP to the admin's real email
    require_once __DIR__ . '/send_otp_email.php';
    $emailSent = send_otp_email($user['email'], $user['firstname'], $otpCode);

    if (!$emailSent) {
        // Email failed to send — fall back to showing it on screen so admin isn't locked out
        $_SESSION['sessions']['SysAdmin']['otp_email_failed'] = true;
    }

    header('Location: http://localhost/CertsVerify/admin/verify_otp.php');
    exit;
}

// ── All other roles: create namespaced session ──
// Each role's data lives under $_SESSION['sessions'][role] so logging in
// as one role never wipes out another role's active session in a different tab.
$loginToken = bin2hex(random_bytes(32));

$_SESSION['sessions'][$role] = [
    'user_id'     => $user['id'],
    'first_name'  => $user['firstname'],
    'last_name'   => $user['lastname'],
    'email'       => $user['email'],
    'role'        => $role,
    'login_token' => $loginToken,
];

log_action($mysqli, $role, $user['id'], 'LOGIN', $user['firstname'] . ' ' . $user['lastname'] . ' logged in');

// ── Role-specific cookie (so the browser knows which role-session to use per tab) ──
$cookieNames = [
    'Graduate'  => 'cv_token_graduate',
    'Employer'  => 'cv_token_employer',
    'Registrar' => 'cv_token_registrar',
    'SysAdmin'  => 'cv_token_admin',
];
$cookieName = $cookieNames[$role] ?? 'cv_login_token';

setcookie($cookieName, $loginToken, [
    'expires'  => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);

header("Location: $redirect");
exit;
?>
