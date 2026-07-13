<?php include('processes/db.php') ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>CertsVerify — Academic Certificate Verification</title>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="cv-navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="about.php"   style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;padding:6px 12px;border-radius:8px;transition:background .15s;" onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb'" onmouseout="this.style.background='';this.style.color='#374151'">About</a>
            <a href="contact.php" style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;padding:6px 12px;border-radius:8px;transition:background .15s;" onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb'" onmouseout="this.style.background='';this.style.color='#374151'">Contact</a>
            <span class="nav-badge"><i class="bi bi-shield-check me-1"></i>Secure verification</span>
        </div>
    </nav>

    <!-- HERO — graduation photo + white overlay -->
    <section class="cv-hero" style="position:relative;min-height:calc(100vh - 63px);width:100%;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        <!-- Background video -->
        <video autoplay muted loop playsinline style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;object-position:center;z-index:0;">
            <source src="assets/img/hero-bg.mp4" type="video/mp4">
        </video>
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.50);z-index:1;"></div>
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(180deg,rgba(37,99,235,0.07) 0%,transparent 60%);z-index:1;"></div>

        <div class="cv-hero-content" style="position:relative;z-index:2;">
            <div class="cv-hero-eyebrow">
                <div class="cv-hero-dot"></div>
                Academic certificate verification system
            </div>

            <h1>Verify certificates<br><span>instantly &amp; securely</span></h1>

            <p class="cv-hero-sub">
                A trusted platform for graduates, employers and institutions
                to verify the authenticity of academic certificates.
            </p>

            <div class="cv-cards-grid">
                <a href="graduate/login.php" class="cv-role-card">
                    <span class="cv-role-icon">🎓</span>
                    <h5>Graduate</h5>
                    <p>Verify your own academic certificate</p>
                    <span class="cv-role-btn">Proceed →</span>
                </a>
                <a href="employer/login.php" class="cv-role-card">
                    <span class="cv-role-icon">💼</span>
                    <h5>Employer</h5>
                    <p>Verify a candidate's academic certificate</p>
                    <span class="cv-role-btn">Proceed →</span>
                </a>
                <a href="registrar/login.php" class="cv-role-card">
                    <span class="cv-role-icon">🏛️</span>
                    <h5>Registrar</h5>
                    <p>Upload and manage official certificates</p>
                    <span class="cv-role-btn">Proceed →</span>
                </a>
                <a href="admin/login.php" class="cv-role-card">
                    <span class="cv-role-icon">🔐</span>
                    <h5>Admin</h5>
                    <p>System administration and oversight</p>
                    <span class="cv-role-btn">Proceed →</span>
                </a>
            </div>
        </div>
    </section>

    <!-- STATS BAR -->
    <div class="cv-stats">
        <div><div class="cv-stat-num">100%</div><div class="cv-stat-lbl">Secure</div></div>
        <div><div class="cv-stat-num">OCR</div><div class="cv-stat-lbl">AI-powered scan</div></div>
        <div><div class="cv-stat-num">4</div><div class="cv-stat-lbl">User roles</div></div>
        <div><div class="cv-stat-num">Free</div><div class="cv-stat-lbl">For all users</div></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>
</html>
