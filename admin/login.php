<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Admin Login — CertsVerify</title>
    <style>
        .pw-wrap { position:relative; }
        .pw-wrap .cv-input { padding-right:42px; }
        .pw-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#9ca3af; font-size:16px; background:none; border:none; padding:0; }
        .pw-eye:hover { color:#2563eb; }
    </style>
</head>
<body>
    <nav class="cv-navbar">
        <a href="../index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <span class="nav-badge">System admin</span>
    </nav>
    <div class="cv-auth-wrap">
        <div class="cv-auth-card">
            <div class="auth-icon">🔐</div>
            <h2>Admin login</h2>
            <p class="auth-sub">Don't have an account? <a href="register.php">Sign up</a></p>

            <?php if (($_GET['error'] ?? '') === 'too_many'): ?>
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;text-align:center;margin-bottom:14px;">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Too many incorrect attempts. Please log in again.
                </div>
            <?php endif; ?>
            <form method="POST" action="../processes/login_process.php">
                <input type="hidden" name="role" value="SysAdmin">
                <div class="cv-form-group">
                    <label>Email address</label>
                    <input type="email" name="email" class="cv-input" placeholder="admin@certsverify.com" required>
                </div>
                <div class="cv-form-group">
                    <label>Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="password" class="cv-input" placeholder="••••••••" required>
                        <button type="button" class="pw-eye" onclick="togglePw('password',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div style="text-align:right;margin-bottom:20px;">
                    <a href="../forgot_password.php" style="font-size:12px;color:var(--blue);text-decoration:none;">
                        <i class="bi bi-question-circle me-1"></i>Forgot password?
                    </a>
                </div>
                <button type="submit" class="btn-cv-primary">Log in</button>
                <a href="../index.php" class="btn-cv-ghost">← Back to home</a>
            </form>
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
    </script>
</body>
</html>
