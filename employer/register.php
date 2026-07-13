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
    <title>Employer Sign Up — CertsVerify</title>
    <style>
        .strength-bar { height: 4px; border-radius: 2px; background: #e5e7eb; margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; width: 0%; }
        .strength-text { font-size: 11px; margin-top: 4px; font-weight: 600; }
        .req-list { margin-top: 6px; }
        .req-item { font-size: 11px; color: #9ca3af; display: flex; align-items: center; gap: 5px; margin-bottom: 2px; transition: color 0.2s; }
        .req-item.met { color: #059669; }
        .req-item .req-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    </style>
</head>
<body>
    <nav class="cv-navbar">
        <a href="../index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <span class="nav-badge">Employer portal</span>
    </nav>
    <div class="cv-auth-wrap" style="padding: 30px 16px;">
        <div class="cv-auth-card wide">
            <div class="auth-icon">💼</div>
            <h2>Create account</h2>
            <p class="auth-sub">Already have an account? <a href="login.php">Log in</a></p>
            <form method="POST" action="../processes/register_process.php">
                <input type="text" name="role" value="Employer" style="display:none;">
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
                <div class="cv-form-group">
                    <label>Business name</label>
                    <input type="text" name="business_name" class="cv-input" placeholder="e.g. Acme Ltd" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Email address</label>
                            <input type="email" name="email" class="cv-input" placeholder="you@company.com" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Phone number</label>
                            <input type="text" name="phone_number" class="cv-input" placeholder="07XXXXXXXX" required>
                        </div>
                    </div>
                </div>
                <div class="row g-3" style="margin-bottom:24px;">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Password</label>
                            <input type="password" name="password" id="password" class="cv-input" placeholder="Min 8 chars, 1 capital, 1 number" required>
                    <div style="margin-top:6px;">
                        <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
                            <div id="pw-bar" style="height:100%;width:0%;background:#dc2626;border-radius:2px;transition:all 0.3s;"></div>
                        </div>
                        <small id="pw-msg" style="font-size:11px;color:#6b7280;"></small>
                    </div>
                    <small style="font-size:11px;color:#9ca3af;margin-top:2px;display:block;">Min 8 chars · 1 capital · 1 number</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Confirm password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="cv-input" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-cv-primary">Create account</button>
                <a href="../index.php" class="btn-cv-ghost">← Back to home</a>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>

    <script>
    // Password strength checker
    function checkPassword() {
        const pw  = document.getElementById('password').value;
        const bar = document.getElementById('pw-bar');
        const msg = document.getElementById('pw-msg');
        if (!pw) { bar.style.width='0%'; msg.textContent=''; return; }

        let score = 0;
        let issues = [];

        if (pw.length >= 8)           score++;  else issues.push('at least 8 characters');
        if (/[A-Z]/.test(pw))         score++;  else issues.push('one capital letter');
        if (/[0-9]/.test(pw))         score++;  else issues.push('one number');
        if (/[^A-Za-z0-9]/.test(pw))  score++;  // bonus for special char

        const colors = ['#dc2626','#d97706','#2563eb','#059669'];
        const labels = ['Weak','Fair','Good','Strong'];
        const widths = ['25%','50%','75%','100%'];

        bar.style.width      = widths[score-1] || '25%';
        bar.style.background = colors[score-1] || '#dc2626';

        if (issues.length > 0) {
            msg.textContent = 'Needs: ' + issues.join(', ');
            msg.style.color = '#dc2626';
        } else {
            msg.textContent = labels[score-1] || 'Weak';
            msg.style.color = colors[score-1] || '#dc2626';
        }
    }

    function validateForm(e) {
        const pw  = document.getElementById('password').value;
        const pw2 = document.getElementById('confirm_password').value;

        if (!/[A-Z]/.test(pw)) {
            e.preventDefault();
            alert('Password must contain at least one capital letter!');
            return false;
        }
        if (!/[0-9]/.test(pw)) {
            e.preventDefault();
            alert('Password must contain at least one number!');
            return false;
        }
        if (pw.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
        if (pw !== pw2) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('password').addEventListener('input', checkPassword);
        document.querySelector('form').addEventListener('submit', validateForm);
    });
    </script>
    <script>
    function checkStrength(val) {
        const len=val.length>=8, upper=/[A-Z]/.test(val), num=/[0-9]/.test(val), special=/[!@#$%^&*(),.?":{}|<>]/.test(val);
        const score=[len,upper,num,special].filter(Boolean).length;
        document.getElementById('req-len').classList.toggle('met',len);
        document.getElementById('req-upper').classList.toggle('met',upper);
        document.getElementById('req-num').classList.toggle('met',num);
        document.getElementById('req-special').classList.toggle('met',special);
        const fill=document.getElementById('strengthFill');
        const text=document.getElementById('strengthText');
        const colors=['','#dc2626','#d97706','#2563eb','#059669'];
        const labels=['','Weak','Fair','Good','Strong'];
        fill.style.width=(score*25)+'%';
        fill.style.background=colors[score]||'#e5e7eb';
        text.style.color=colors[score]||'#9ca3af';
        text.textContent=val.length>0?labels[score]:'';
    }
    function checkMatch() {
        const p1=document.getElementById('password').value;
        const p2=document.getElementById('confirm_password').value;
        const t=document.getElementById('matchText');
        if(!p2.length){t.textContent='';return;}
        t.style.color=p1===p2?'#059669':'#dc2626';
        t.textContent=p1===p2?'✓ Passwords match':'✗ Passwords do not match';
    }
    document.querySelector('form').addEventListener('submit',function(e){
        const val=document.getElementById('password').value;
        const p2=document.getElementById('confirm_password').value;
        if(val.length<8||!/[A-Z]/.test(val)||!/[0-9]/.test(val)||!/[!@#$%^&*(),.?":{}|<>]/.test(val)){
            e.preventDefault();alert('Password does not meet requirements!');return;
        }
        if(val!==p2){e.preventDefault();alert('Passwords do not match!');}
    });
    </script>
</body>
</html>
