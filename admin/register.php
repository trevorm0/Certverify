<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Admin Sign Up — CertsVerify</title>
</head>
<body>
    <nav class="cv-navbar">
        <a href="../index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <span class="nav-badge">System admin</span>
    </nav>
    <div class="cv-auth-wrap" style="padding:30px 16px;">
        <div class="cv-auth-card wide">
            <div class="auth-icon">🔐</div>
            <h2>Create admin account</h2>
            <p class="auth-sub">Already have an account? <a href="login.php">Log in</a></p>
            <form method="POST" action="../processes/register_process.php">
                <input type="text" name="role" value="SysAdmin" style="display:none;">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>First name</label>
                            <input type="text" name="first_name" class="cv-input" placeholder="John" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Last name</label>
                            <input type="text" name="last_name" class="cv-input" placeholder="Doe" required>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Email address</label>
                            <input type="email" name="email" class="cv-input" placeholder="admin@certsverify.com" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Phone number</label>
                            <input type="text" name="phone_number" class="cv-input" placeholder="07XXXXXXXX" required>
                        </div>
                    </div>
                </div>
                <div class="row g-3" style="margin-bottom:8px;">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Password</label>
                            <input type="password" name="password" id="password" class="cv-input" placeholder="••••••••"
                                oninput="checkPasswordStrength(this.value)" required>
                            <div style="margin-top:6px;">
                                <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
                                    <div id="pw-bar" style="height:100%;width:0;border-radius:2px;transition:all 0.3s;"></div>
                                </div>
                                <small id="pw-msg" style="font-size:11px;"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Confirm password</label>
                            <input type="password" name="confirm_password" class="cv-input" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                <small style="color:var(--hint);font-size:11px;display:block;margin-bottom:20px;">
                    <i class="bi bi-shield-check me-1"></i>
                    Password must be at least 8 characters with 1 uppercase letter and 1 number.
                </small>
                <button type="submit" class="btn-cv-primary">Create account</button>
                <a href="../index.php" class="btn-cv-ghost">← Back to home</a>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
    function checkPasswordStrength(pw) {
        const bar = document.getElementById('pw-bar');
        const msg = document.getElementById('pw-msg');
        let strength = 0, tips = [];
        if (pw.length >= 8) strength++; else tips.push('8+ characters');
        if (/[A-Z]/.test(pw)) strength++; else tips.push('uppercase letter');
        if (/[0-9]/.test(pw)) strength++; else tips.push('number');
        const colors = ['#dc2626','#d97706','#059669'];
        const labels = ['Weak','Fair','Strong'];
        const widths = ['33%','66%','100%'];
        bar.style.width      = strength > 0 ? widths[strength-1] : '0%';
        bar.style.background = strength > 0 ? colors[strength-1] : '#e5e7eb';
        msg.textContent      = strength > 0 ? labels[strength-1] + (tips.length ? ' — needs: ' + tips.join(', ') : ' ✓') : '';
        msg.style.color      = strength > 0 ? colors[strength-1] : '#6b7280';
    }
    </script>
</body>
</html>
