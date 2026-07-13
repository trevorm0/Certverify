<?php
session_start();
$mysqli = require 'processes/db.php';

$roleMap = [
    'Graduate'  => ['table' => 'graduate',  'login' => 'graduate/login.php'],
    'Employer'  => ['table' => 'employer',  'login' => 'employer/login.php'],
    'Registrar' => ['table' => 'registrar', 'login' => 'registrar/login.php'],
    'SysAdmin'  => ['table' => 'sysadmin',  'login' => 'admin/login.php'],
];

$action = $_POST['action'] ?? '';
$error  = '';
$step   = $_SESSION['fp_step'] ?? 1;

if ($action === 'find_account') {
    $role  = trim($_POST['role']  ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!isset($roleMap[$role])) {
        $error = 'Please select a valid role.';
    } else {
        $table = $roleMap[$role]['table'];
        $safe  = mysqli_real_escape_string($mysqli, $email);
        $user  = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT * FROM `$table` WHERE email='$safe' LIMIT 1"
        ));
        if (!$user) {
            $error = 'No account found with that email address.';
        } elseif (empty($user['security_question'])) {
            $error = 'No security question set for this account. Contact the system administrator.';
        } else {
            $_SESSION['fp_step']     = 2;
            $_SESSION['fp_role']     = $role;
            $_SESSION['fp_email']    = $email;
            $_SESSION['fp_user_id']  = $user['id'];
            $_SESSION['fp_question'] = $user['security_question'];
            $_SESSION['fp_answer']   = strtolower(trim($user['security_answer']));
            $step = 2;
        }
    }
}

elseif ($action === 'check_answer') {
    $answer = strtolower(trim($_POST['answer'] ?? ''));
    if ($answer === ($_SESSION['fp_answer'] ?? '~~')) {
        $_SESSION['fp_step'] = 3;
        $step = 3;
    } else {
        $error = 'Incorrect answer. Please try again.';
        $step  = 2;
    }
}

elseif ($action === 'reset_password') {
    $newPw  = $_POST['new_password']     ?? '';
    $confPw = $_POST['confirm_password'] ?? '';
    if (strlen($newPw) < 8 || !preg_match('/[A-Z]/', $newPw) || !preg_match('/[0-9]/', $newPw)) {
        $error = 'Password must be at least 8 characters with 1 uppercase letter and 1 number.';
        $step  = 3;
    } elseif ($newPw !== $confPw) {
        $error = 'Passwords do not match.';
        $step  = 3;
    } else {
        $role      = $_SESSION['fp_role']    ?? '';
        $userId    = $_SESSION['fp_user_id'] ?? 0;
        $table     = $roleMap[$role]['table'] ?? '';
        $loginPage = $roleMap[$role]['login']  ?? 'index.php';
        $hash      = password_hash($newPw, PASSWORD_DEFAULT);
        $safe      = mysqli_real_escape_string($mysqli, $hash);
        mysqli_query($mysqli, "UPDATE `$table` SET password='$safe' WHERE id='$userId'");
        $_SESSION['fp_login'] = $loginPage;
        unset($_SESSION['fp_step'], $_SESSION['fp_role'], $_SESSION['fp_email'],
              $_SESSION['fp_user_id'], $_SESSION['fp_question'], $_SESSION['fp_answer']);
        $step = 4;
    }
}

$loginPage = $_SESSION['fp_login'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Forgot Password — CertsVerify</title>
    <style>
        .step-indicator{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px;}
        .step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:#e5e7eb;color:#9ca3af;}
        .step-dot.active{background:#2563eb;color:#fff;}
        .step-dot.done{background:#059669;color:#fff;}
        .step-line{flex:1;max-width:40px;height:2px;background:#e5e7eb;border-radius:1px;}
        .step-line.done{background:#059669;}
        .pw-wrap{position:relative;}
        .pw-wrap .cv-input{padding-right:42px;}
        .pw-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:16px;background:none;border:none;padding:0;}
        .pw-eye:hover{color:#2563eb;}
    </style>
</head>
<body>
    <nav class="cv-navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <span class="nav-badge">Password reset</span>
    </nav>

    <div class="cv-auth-wrap" style="padding:30px 16px;">
        <div class="cv-auth-card">
            <div class="auth-icon">🔑</div>
            <h2>Forgot password</h2>

            <?php if ($error): ?>
            <div class="alert alert-danger" style="font-size:13px;padding:10px 14px;border-radius:10px;margin-bottom:16px;">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
            <p class="auth-sub" style="margin-bottom:20px;">Enter your email and select your role.</p>
            <div class="step-indicator">
                <div class="step-dot active">1</div>
                <div class="step-line"></div>
                <div class="step-dot">2</div>
                <div class="step-line"></div>
                <div class="step-dot">3</div>
            </div>
            <form method="POST" action="forgot_password.php">
                <input type="hidden" name="action" value="find_account">
                <div class="cv-form-group">
                    <label>Your role</label>
                    <select name="role" class="cv-input" required>
                        <option value="" disabled selected>Select your role</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Employer">Employer</option>
                        <option value="Registrar">Registrar</option>
                        <option value="SysAdmin">System Admin</option>
                    </select>
                </div>
                <div class="cv-form-group" style="margin-bottom:24px;">
                    <label>Email address</label>
                    <input type="email" name="email" class="cv-input" placeholder="Enter your registered email" required>
                </div>
                <button type="submit" class="btn-cv-primary">Continue</button>
                <a href="index.php" class="btn-cv-ghost">← Back to home</a>
            </form>

            <?php elseif ($step == 2): ?>
            <p class="auth-sub" style="margin-bottom:20px;">Answer your security question.</p>
            <div class="step-indicator">
                <div class="step-dot done">✓</div>
                <div class="step-line done"></div>
                <div class="step-dot active">2</div>
                <div class="step-line"></div>
                <div class="step-dot">3</div>
            </div>
            <div class="alert alert-info" style="font-size:13px;padding:10px 14px;border-radius:10px;margin-bottom:16px;">
                <i class="bi bi-shield-lock me-2"></i>
                Account found: <strong><?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?></strong>
            </div>
            <form method="POST" action="forgot_password.php">
                <input type="hidden" name="action" value="check_answer">
                <div class="cv-form-group" style="margin-bottom:24px;">
                    <label><?php echo htmlspecialchars($_SESSION['fp_question'] ?? ''); ?></label>
                    <input type="text" name="answer" class="cv-input" placeholder="Your answer" required autocomplete="off">
                    <small style="font-size:11px;color:var(--hint);margin-top:4px;display:block;">
                        <i class="bi bi-info-circle me-1"></i>Not case-sensitive
                    </small>
                </div>
                <button type="submit" class="btn-cv-primary">Verify answer</button>
                <a href="forgot_password.php" class="btn-cv-ghost">← Start over</a>
            </form>

            <?php elseif ($step == 3): ?>
            <p class="auth-sub" style="margin-bottom:20px;">Set your new password.</p>
            <div class="step-indicator">
                <div class="step-dot done">✓</div>
                <div class="step-line done"></div>
                <div class="step-dot done">✓</div>
                <div class="step-line done"></div>
                <div class="step-dot active">3</div>
            </div>
            <div class="alert alert-success" style="font-size:13px;padding:10px 14px;border-radius:10px;margin-bottom:16px;">
                <i class="bi bi-check-circle me-2"></i>Identity verified! Choose a new password.
            </div>
            <form method="POST" action="forgot_password.php">
                <input type="hidden" name="action" value="reset_password">
                <div class="cv-form-group">
                    <label>New password</label>
                    <div class="pw-wrap">
                        <input type="password" name="new_password" id="new_password"
                               class="cv-input" placeholder="Min 8 chars, 1 capital, 1 number"
                               oninput="checkPw(this.value)" required>
                        <button type="button" class="pw-eye" onclick="togglePw('new_password',this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div style="margin-top:6px;">
                        <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
                            <div id="pw-bar" style="height:100%;width:0%;border-radius:2px;transition:all 0.3s;"></div>
                        </div>
                        <small id="pw-msg" style="font-size:11px;"></small>
                    </div>
                </div>
                <div class="cv-form-group" style="margin-bottom:24px;">
                    <label>Confirm new password</label>
                    <div class="pw-wrap">
                        <input type="password" name="confirm_password" id="confirm_password"
                               class="cv-input" placeholder="••••••••"
                               oninput="checkMatch()" required>
                        <button type="button" class="pw-eye" onclick="togglePw('confirm_password',this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small id="match-msg" style="font-size:11px;margin-top:4px;display:block;"></small>
                </div>
                <button type="submit" class="btn-cv-primary">Reset password</button>
            </form>

            <?php elseif ($step == 4): ?>
            <div class="step-indicator">
                <div class="step-dot done">✓</div>
                <div class="step-line done"></div>
                <div class="step-dot done">✓</div>
                <div class="step-line done"></div>
                <div class="step-dot done">✓</div>
            </div>
            <div class="text-center py-3">
                <i class="bi bi-check-circle-fill" style="font-size:52px;color:#059669;"></i>
                <p class="mt-3" style="font-weight:700;color:var(--navy);font-size:16px;">Password reset successfully!</p>
                <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">You can now log in with your new password.</p>
                <a href="<?php echo htmlspecialchars($loginPage); ?>" class="btn-cv-primary">Go to login →</a>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
    function checkPw(pw) {
        const bar=document.getElementById('pw-bar'), msg=document.getElementById('pw-msg');
        if(!pw){bar.style.width='0%';msg.textContent='';return;}
        let score=0,issues=[];
        if(pw.length>=8)score++;else issues.push('8+ chars');
        if(/[A-Z]/.test(pw))score++;else issues.push('1 capital');
        if(/[0-9]/.test(pw))score++;else issues.push('1 number');
        if(/[^A-Za-z0-9]/.test(pw))score++;
        const colors=['#dc2626','#d97706','#2563eb','#059669'];
        const labels=['Weak','Fair','Good','Strong'];
        bar.style.width=(score*25)+'%'; bar.style.background=colors[score-1]||'#dc2626';
        msg.textContent=issues.length?'Needs: '+issues.join(', '):(labels[score-1]||'');
        msg.style.color=colors[score-1]||'#dc2626';
    }
    function checkMatch() {
        const p1=document.getElementById('new_password').value;
        const p2=document.getElementById('confirm_password').value;
        const m=document.getElementById('match-msg');
        if(!p2){m.textContent='';return;}
        m.style.color=p1===p2?'#059669':'#dc2626';
        m.textContent=p1===p2?'✓ Passwords match':'✗ Do not match';
    }
    </script>
</body>
</html>
