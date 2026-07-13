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
    <title>About Us — CertsVerify</title>
    <style>
        body { background: #f8faff; }

        /* ── Page hero ── */
        .about-hero {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 72px 24px 60px;
            text-align: center;
        }
        .about-hero h1 { font-size: 38px; font-weight: 800; margin-bottom: 14px; }
        .about-hero p  { font-size: 16px; opacity: .85; max-width: 560px; margin: 0 auto; }

        /* ── Sections ── */
        .about-section { padding: 64px 24px; }
        .about-section.alt { background: #fff; }

        .section-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2563eb;
            background: #eff6ff;
            border-radius: 20px;
            padding: 4px 14px;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 800;
            color: #1e3a5f;
            margin-bottom: 16px;
        }

        .section-body {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.8;
            max-width: 680px;
        }

        /* ── Mission / Vision cards ── */
        .mv-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px;
            height: 100%;
            transition: box-shadow .2s;
        }
        .mv-card:hover { box-shadow: 0 8px 24px rgba(37,99,235,.1); }
        .mv-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .mv-card h5 { font-size: 17px; font-weight: 700; color: #1e3a5f; margin-bottom: 10px; }
        .mv-card p  { font-size: 14px; color: #6b7280; line-height: 1.7; margin: 0; }

        /* ── Feature list ── */
        .feature-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .feature-item:last-child { border-bottom: none; }
        .feature-icon {
            width: 44px; height: 44px; min-width: 44px;
            background: #eff6ff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            color: #2563eb;
        }
        .feature-item h6 { font-size: 15px; font-weight: 700; color: #1e3a5f; margin-bottom: 4px; }
        .feature-item p  { font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6; }

        /* ── Stats ── */
        .about-stat {
            text-align: center;
            padding: 28px 20px;
        }
        .about-stat .num {
            font-size: 42px;
            font-weight: 800;
            color: #2563eb;
            line-height: 1;
            margin-bottom: 6px;
        }
        .about-stat .lbl {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }

        /* ── Team ── */
        .team-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            height: 100%;
        }
        .team-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            font-weight: 800;
            margin: 0 auto 14px;
            color: #fff;
        }
        .team-card h6 { font-weight: 700; color: #1e3a5f; margin-bottom: 4px; }
        .team-card .role { font-size: 12px; color: #2563eb; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

        /* ── CTA ── */
        .about-cta {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 64px 24px;
            text-align: center;
        }
        .about-cta h2 { font-size: 28px; font-weight: 800; margin-bottom: 12px; }
        .about-cta p  { font-size: 15px; opacity: .85; margin-bottom: 28px; }
        .btn-cta-white {
            background: #fff;
            color: #2563eb;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px 32px;
            text-decoration: none;
            font-size: 15px;
            display: inline-block;
            transition: transform .15s;
        }
        .btn-cta-white:hover { transform: translateY(-2px); color: #1e3a5f; }
        .btn-cta-outline {
            background: transparent;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px 32px;
            text-decoration: none;
            font-size: 15px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,.5);
            margin-left: 12px;
            transition: border-color .15s;
        }
        .btn-cta-outline:hover { border-color: #fff; color: #fff; }

        /* ── Nav active link ── */
        .cv-navbar .nav-links a { font-size: 13px; font-weight: 600; color: #374151; text-decoration: none; padding: 6px 12px; border-radius: 8px; transition: background .15s; }
        .cv-navbar .nav-links a:hover, .cv-navbar .nav-links a.active { background: #eff6ff; color: #2563eb; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="cv-navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <div class="nav-links d-flex gap-1 align-items-center">
            <a href="index.php">Home</a>
            <a href="about.php" class="active">About</a>
            <a href="contact.php">Contact</a>
        </div>
    </nav>

    <!-- HERO -->
    <div class="about-hero">
        <div class="section-tag" style="background:rgba(255,255,255,.15);color:#fff;">Our Story</div>
        <h1>About CertsVerify</h1>
        <p>A modern, secure platform built to eliminate certificate fraud and make academic verification instant, reliable, and accessible to everyone.</p>
    </div>

    <!-- WHAT WE DO -->
    <section class="about-section alt">
        <div class="container" style="max-width:960px;">
            <div class="row align-items-center g-5">
                <div class="col-md-6">
                    <div class="section-tag">What We Do</div>
                    <h2 class="section-title">Bringing trust to academic credentials</h2>
                    <p class="section-body">
                        CertsVerify is an educational document verification system designed for Kenyan
                        universities, graduates, and employers. We provide a centralised platform where
                        university registrars can securely upload and manage official certificates,
                        graduates can check and share their credentials, and employers can instantly
                        verify whether a certificate is genuine — without having to call or email anyone.
                    </p>
                    <p class="section-body mt-3">
                        Our system uses <strong>OCR (Optical Character Recognition)</strong> powered
                        by an AI engine to read uploaded certificate documents and match them against
                        verified university records in real time.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="about-stat" style="background:#eff6ff;border-radius:16px;">
                                <div class="num">50+</div>
                                <div class="lbl">Certificates verified</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-stat" style="background:#f0fdf4;border-radius:16px;">
                                <div class="num" style="color:#059669;">10</div>
                                <div class="lbl">Universities registered</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-stat" style="background:#fff7ed;border-radius:16px;">
                                <div class="num" style="color:#d97706;">4</div>
                                <div class="lbl">User roles supported</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-stat" style="background:#fdf4ff;border-radius:16px;">
                                <div class="num" style="color:#9333ea;">100%</div>
                                <div class="lbl">Free to use</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MISSION & VISION -->
    <section class="about-section">
        <div class="container" style="max-width:960px;">
            <div class="text-center mb-5">
                <div class="section-tag">Our Purpose</div>
                <h2 class="section-title" style="max-width:none;">Mission & Vision</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="mv-card">
                        <div class="mv-icon" style="background:#eff6ff;">🎯</div>
                        <h5>Our Mission</h5>
                        <p>To provide a fast, secure, and transparent platform for verifying academic certificates, eliminating fraud and building trust between graduates, institutions, and employers across Kenya.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mv-card">
                        <div class="mv-icon" style="background:#f0fdf4;">🔭</div>
                        <h5>Our Vision</h5>
                        <p>A Kenya where no employer is ever deceived by a forged certificate, and where every genuine graduate's credentials are instantly verifiable from anywhere in the world.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mv-card">
                        <div class="mv-icon" style="background:#fff7ed;">⚖️</div>
                        <h5>Our Values</h5>
                        <p>Integrity in every verification. Security in every transaction. Accessibility for every institution, graduate, and employer — regardless of size or resources.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="about-section alt">
        <div class="container" style="max-width:960px;">
            <div class="row align-items-start g-5">
                <div class="col-md-5">
                    <div class="section-tag">How It Works</div>
                    <h2 class="section-title">Built for everyone in the ecosystem</h2>
                    <p class="section-body">
                        CertsVerify serves four distinct user roles, each with a tailored experience
                        designed around their specific needs.
                    </p>
                </div>
                <div class="col-md-7">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <h6>Graduates</h6>
                            <p>Log in to verify your own certificate is in the system, view your verification history, and check how many times employers have confirmed your credentials.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <div>
                            <h6>Employers</h6>
                            <p>Search for a candidate by name, run an AI-powered OCR scan on an uploaded certificate PDF, or batch-verify multiple candidates at once during high-volume hiring.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-building-fill"></i></div>
                        <div>
                            <h6>University Registrars</h6>
                            <p>Upload and manage official certificates for your institution's graduates. Approve graduate records and keep the database up to date with the latest cohorts.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                        <div>
                            <h6>System Administrators</h6>
                            <p>Oversee the full platform — approve new registrar and employer accounts, manage universities, monitor all activity through a live audit log, and generate reports.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TECHNOLOGY -->
    <section class="about-section">
        <div class="container" style="max-width:960px;">
            <div class="text-center mb-5">
                <div class="section-tag">Technology</div>
                <h2 class="section-title">Powered by AI and secure by design</h2>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-md-3">
                    <div class="mv-card text-center">
                        <div class="mv-icon mx-auto" style="background:#eff6ff;">🤖</div>
                        <h5>OCR Engine</h5>
                        <p>Tesseract — Google's open-source AI engine — reads and extracts text from certificate images using deep learning (LSTM neural networks).</p>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="mv-card text-center">
                        <div class="mv-icon mx-auto" style="background:#f0fdf4;">📄</div>
                        <h5>PDF Processing</h5>
                        <p>Ghostscript converts uploaded certificate PDFs into high-resolution images that the OCR engine can then analyse and verify.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="mv-card text-center">
                        <div class="mv-icon mx-auto" style="background:#fff7ed;">📱</div>
                        <h5>QR Verification</h5>
                        <p>Every certificate carries a QR code encoding the graduate's details. Scanning with any phone camera instantly reveals the certificate information — no internet required.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="mv-card text-center">
                        <div class="mv-icon mx-auto" style="background:#fdf4ff;">🔐</div>
                        <h5>Secure Storage</h5>
                        <p>Certificate files are stored under cryptographic hash filenames. Admin accounts are protected with two-factor authentication on every login.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <div class="about-cta">
        <h2>Ready to verify a certificate?</h2>
        <p>Join thousands of graduates, employers and institutions already using CertsVerify.</p>
        <a href="index.php" class="btn-cta-white">Get started</a>
        <a href="contact.php" class="btn-cta-outline">Contact us</a>
    </div>

    <!-- FOOTER -->
    <footer style="background:#1e3a5f;color:rgba(255,255,255,.6);text-align:center;padding:20px;font-size:12px;">
        © <?php echo date('Y'); ?> CertsVerify — Educational Document Verification System &nbsp;·&nbsp;
        <a href="about.php" style="color:rgba(255,255,255,.6);">About</a> &nbsp;·&nbsp;
        <a href="contact.php" style="color:rgba(255,255,255,.6);">Contact</a>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>
</html>
