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
    <title>Graduate Sign Up — CertsVerify</title>
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
        <span class="nav-badge">Graduate portal</span>
    </nav>
    <div class="cv-auth-wrap" style="padding:30px 16px;">
        <div class="cv-auth-card wide">
            <div class="auth-icon">🎓</div>
            <h2>Create account</h2>
            <p class="auth-sub">Already have an account? <a href="login.php">Log in</a></p>
            <form method="POST" action="../processes/register_process.php">
                <input type="hidden" name="role" value="Graduate">
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
                    <label>University</label>
                    <select name="university_name" class="cv-input" required>
                        <option value="" disabled selected>Choose university</option>
                        <option value="University of Nairobi">University of Nairobi</option>
                        <option value="Kenyatta University">Kenyatta University</option>
                        <option value="Strathmore University">Strathmore University</option>
                        <option value="Jomo Kenyatta University of Agriculture and Technology">Jomo Kenyatta University of Agriculture and Technology</option>
                        <option value="Moi University">Moi University</option>
                        <option value="Egerton University">Egerton University</option>
                        <option value="Maseno University">Maseno University</option>
                        <option value="Daystar University">Daystar University</option>
                        <option value="United States International University Africa">United States International University Africa</option>
                        <option value="Catholic University of East Africa">Catholic University of East Africa</option>
                    </select>
                </div>
                <div class="cv-form-group">
                    <label>Admission number</label>
                    <input type="text" name="admission_number" class="cv-input" placeholder="e.g. ENG/IT/001/2021" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Email address</label>
                            <input type="email" name="email" class="cv-input" placeholder="you@email.com" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Phone number</label>
                            <input type="text" name="phone_number" class="cv-input" placeholder="07XXXXXXXX" required>
                        </div>
                    </div>
                </div>
                <!-- Security question -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Security question</label>
                            <select name="security_question" class="cv-input" required>
                                <option value="" disabled selected>Choose a question</option>
                                <option value="What is your favourite colour?">What is your favourite colour?</option>
                                <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                <option value="What city were you born in?">What city were you born in?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What was the name of your primary school?">What was the name of your primary school?</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Security answer</label>
                            <input type="text" name="security_answer" class="cv-input"
                                   placeholder="Your answer" required autocomplete="off">
                            <small style="font-size:11px;color:var(--hint);margin-top:3px;display:block;">Used to reset your password</small>
                        </div>
                    </div>
                </div>
                <!-- Password fields -->
                <div class="row g-3" style="margin-bottom:24px;">
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="password" id="password" class="cv-input"
                                       placeholder="Min 8 chars, 1 capital, 1 number"
                                       oninput="checkPw(this.value)" required>
                                <button type="button" class="pw-eye" onclick="togglePw('password',this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <div style="margin-top:6px;">
                                <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
                                    <div id="pw-bar" style="height:100%;width:0%;border-radius:2px;transition:all 0.3s;"></div>
                                </div>
                                <small id="pw-msg" style="font-size:11px;"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="cv-form-group mb-0">
                            <label>Confirm password</label>
                            <div class="pw-wrap">
                                <input type="password" name="confirm_password" id="confirm_password"
                                       class="cv-input" placeholder="••••••••" required>
                                <button type="button" class="pw-eye" onclick="togglePw('confirm_password',this)"><i class="bi bi-eye"></i></button>
                            </div>
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
    document.querySelector('form').addEventListener('submit',function(e){
        const pw=document.getElementById('password').value;
        const pw2=document.getElementById('confirm_password').value;
        if(pw.length<8||!/[A-Z]/.test(pw)||!/[0-9]/.test(pw)){
            e.preventDefault();alert('Password must be at least 8 characters with 1 uppercase and 1 number.');return;
        }
        if(pw!==pw2){e.preventDefault();alert('Passwords do not match!');}
    });
    </script>
</body>
</html>
