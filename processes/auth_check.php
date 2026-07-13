<?php
/*
 * ============================================================
 * auth_check.php — Session Token Verification (multi-tab safe)
 * ============================================================
 * Session data for each role lives under $_SESSION['sessions'][role]
 * so logging in as one role in one tab never wipes out another
 * role's session in a different tab.
 *
 * This file then copies that role's data into the flat
 * $_SESSION keys (user_id, role, etc.) that the rest of the
 * codebase already expects — so no other file needs to change.
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Role → login page mapping ──
$loginPages = [
    'Graduate'  => 'http://localhost/CertsVerify/graduate/login.php',
    'Employer'  => 'http://localhost/CertsVerify/employer/login.php',
    'Registrar' => 'http://localhost/CertsVerify/registrar/login.php',
    'SysAdmin'  => 'http://localhost/CertsVerify/admin/login.php',
];

$loginUrl = $loginPages[$required_role] ?? 'http://localhost/CertsVerify/index.php';

// ── Role-specific cookie names ──
$cookieNames = [
    'Graduate'  => 'cv_token_graduate',
    'Employer'  => 'cv_token_employer',
    'Registrar' => 'cv_token_registrar',
    'SysAdmin'  => 'cv_token_admin',
];
$cookieName = $cookieNames[$required_role] ?? 'cv_login_token';

// ── Pull this role's namespaced session data ──
$roleSession = $_SESSION['sessions'][$required_role] ?? null;

// ── Check 1: Does this role have an active namespaced session? ──
if (empty($roleSession) || empty($roleSession['user_id'])) {
    _kick($loginUrl, "Please log in to continue.", $cookieName);
}

// ── Check 2: Cookie token matches this role's session token? ──
$sessionToken = $roleSession['login_token'] ?? '';
$cookieToken  = $_COOKIE[$cookieName] ?? '';

if (empty($sessionToken) || empty($cookieToken)) {
    _kick($loginUrl, "Session expired. Please log in again.", $cookieName);
}

if (!hash_equals($sessionToken, $cookieToken)) {
    _kick($loginUrl, "Security check failed. Please log in again.", $cookieName);
}

// ── All checks passed — copy this role's data into flat session keys ──
// (so every existing dashboard file that reads $_SESSION['user_id'] etc. keeps working)
$_SESSION['user_id']     = $roleSession['user_id'];
$_SESSION['first_name']  = $roleSession['first_name'];
$_SESSION['last_name']   = $roleSession['last_name'];
$_SESSION['email']       = $roleSession['email'];
$_SESSION['role']        = $roleSession['role'];
$_SESSION['login_token'] = $roleSession['login_token'];

function _kick($url, $message, $cookieName = 'cv_login_token') {
    // Only clear THIS role's cookie — do NOT destroy the whole session
    setcookie($cookieName, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    echo "<script>alert('" . addslashes($message) . "')</script>";
    echo "<script>document.location='$url';</script>";
    exit;
}
?>
