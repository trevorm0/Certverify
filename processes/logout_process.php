<?php
/*
 * logout_process.php
 * Clears ONLY the role that is logging out from this tab.
 * Other roles' sessions in other tabs remain untouched.
 */
session_start();

$role = $_SESSION['role'] ?? ($_GET['role'] ?? '');

$cookieNames = [
    'Graduate'  => 'cv_token_graduate',
    'Employer'  => 'cv_token_employer',
    'Registrar' => 'cv_token_registrar',
    'SysAdmin'  => 'cv_token_admin',
];
$cookieName = $cookieNames[$role] ?? 'cv_login_token';

// Remove only this role's namespaced session data
if ($role && isset($_SESSION['sessions'][$role])) {
    unset($_SESSION['sessions'][$role]);
}

// Also clear the flat keys for this tab's request lifecycle
unset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'],
      $_SESSION['email'], $_SESSION['role'], $_SESSION['login_token']);

// Delete only this role's cookie
setcookie($cookieName, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);

header("Location: http://localhost/CertsVerify/index.php");
exit;
?>
