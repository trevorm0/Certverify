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
    <title>Contact Us — CertsVerify</title>
    <style>
        body { background: #f8faff; }

        /* ── Page hero ── */
        .contact-hero {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 72px 24px 60px;
            text-align: center;
        }
        .contact-hero h1 { font-size: 38px; font-weight: 800; margin-bottom: 14px; }
        .contact-hero p  { font-size: 16px; opacity: .85; max-width: 500px; margin: 0 auto; }

        /* ── Section tag ── */
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

        /* ── Main layout ── */
        .contact-wrap {
            padding: 64px 24px;
        }

        /* ── Contact info cards ── */
        .info-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 16px;
            transition: box-shadow .2s;
        }
        .info-card:hover { box-shadow: 0 4px 16px rgba(37,99,235,.08); }
        .info-card-icon {
            width: 44px; height: 44px; min-width: 44px;
            background: #eff6ff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            color: #2563eb;
        }
        .info-card h6 { font-size: 13px; font-weight: 700; color: #1e3a5f; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .4px; }
        .info-card p  { font-size: 14px; color: #4b5563; margin: 0; line-height: 1.6; }
        .info-card a  { color: #2563eb; text-decoration: none; }
        .info-card a:hover { text-decoration: underline; }

        /* ── Contact form ── */
        .contact-form-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 36px;
        }

        .contact-form-card .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px;
        }

        .contact-form-card .form-control,
        .contact-form-card .form-select {
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            font-size: 14px;
            padding: 10px 14px;
            transition: border-color .2s;
        }

        .contact-form-card .form-control:focus,
        .contact-form-card .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .btn-send {
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px 32px;
            font-size: 15px;
            border: none;
            width: 100%;
            transition: background .15s, transform .15s;
        }
        .btn-send:hover { background: #1d4ed8; transform: translateY(-1px); }

        /* ── Success / error messages ── */
        .form-success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 16px 20px;
            color: #166534;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 16px 20px;
            color: #991b1b;
            font-size: 14px;
            font-weight: 600;
        }

        /* ── FAQ section ── */
        .faq-section {
            background: #fff;
            padding: 64px 24px;
            border-top: 1px solid #f3f4f6;
        }

        .faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .faq-question {
            padding: 18px 20px;
            font-weight: 700;
            font-size: 14px;
            color: #1e3a5f;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: none;
            width: 100%;
            text-align: left;
            transition: background .15s;
        }
        .faq-question:hover { background: #f8faff; }
        .faq-question .faq-icon { font-size: 18px; color: #2563eb; transition: transform .2s; }
        .faq-question.open .faq-icon { transform: rotate(45deg); }

        .faq-answer {
            display: none;
            padding: 0 20px 18px;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.7;
            border-top: 1px solid #f3f4f6;
        }
        .faq-answer.open { display: block; }

        /* ── Footer ── */
        .cv-navbar .nav-links a { font-size: 13px; font-weight: 600; color: #374151; text-decoration: none; padding: 6px 12px; border-radius: 8px; transition: background .15s; }
        .cv-navbar .nav-links a:hover, .cv-navbar .nav-links a.active { background: #eff6ff; color: #2563eb; }
    </style>
</head>
<body>

<?php
// ── Handle form submission ──
$formStatus  = '';
$formMessage = '';
$formData    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $role    = trim($_POST['role']    ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Keep form values for repopulation
    $formData = compact('name', 'email', 'role', 'subject', 'message');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $formStatus  = 'error';
        $formMessage = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formStatus  = 'error';
        $formMessage = 'Please enter a valid email address.';
    } else {
        // Log inquiry to DB if possible, otherwise just confirm receipt
        try {
            $mysqli = require 'processes/db.php';
            // Store in a contact_inquiries table if it exists, otherwise skip silently
            @mysqli_query($mysqli,
                "INSERT INTO contact_inquiries (name, email, role, subject, message, created_at)
                 VALUES (
                    '" . mysqli_real_escape_string($mysqli, $name)    . "',
                    '" . mysqli_real_escape_string($mysqli, $email)   . "',
                    '" . mysqli_real_escape_string($mysqli, $role)    . "',
                    '" . mysqli_real_escape_string($mysqli, $subject) . "',
                    '" . mysqli_real_escape_string($mysqli, $message) . "',
                    NOW()
                 )"
            );
        } catch (Exception $e) {
            // DB logging failed — still show success to user
        }

        $formStatus  = 'success';
        $formMessage = "Thank you, $name! Your message has been received. We'll get back to you as soon as possible.";
        $formData    = []; // Clear form after success
    }
}
?>

    <!-- NAVBAR -->
    <nav class="cv-navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <div class="nav-links d-flex gap-1 align-items-center">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
        </div>
    </nav>

    <!-- HERO -->
    <div class="contact-hero">
        <div class="section-tag" style="background:rgba(255,255,255,.15);color:#fff;">Get In Touch</div>
        <h1>Contact Us</h1>
        <p>Have a question, issue, or feedback? We're here to help. Reach out and we'll respond as soon as we can.</p>
    </div>

    <!-- MAIN CONTENT -->
    <div class="contact-wrap">
        <div class="container" style="max-width:1000px;">
            <div class="row g-5">

                <!-- LEFT: Contact info -->
                <div class="col-md-5">
                    <div class="section-tag">Contact Information</div>
                    <h2 style="font-size:24px;font-weight:800;color:#1e3a5f;margin-bottom:8px;">We'd love to hear from you</h2>
                    <p style="font-size:14px;color:#6b7280;margin-bottom:28px;line-height:1.7;">
                        Whether you're having trouble verifying a certificate, want to register your university,
                        or just want to learn more — send us a message.
                    </p>

                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h6>Email</h6>
                            <p><a href="mailto:support@certsverify.ac.ke">support@certsverify.ac.ke</a><br>
                            <a href="mailto:admin@certsverify.ac.ke">admin@certsverify.ac.ke</a></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h6>Phone</h6>
                            <p>+254 700 000 000<br>
                            <span style="font-size:12px;color:#9ca3af;">Monday – Friday, 8:00 AM – 5:00 PM EAT</span></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h6>Location</h6>
                            <p>Nairobi, Kenya<br>
                            <span style="font-size:12px;color:#9ca3af;">Serving institutions across East Africa</span></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-icon"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <h6>Response Time</h6>
                            <p>We aim to respond to all enquiries within <strong>24 hours</strong> on business days.</p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Contact form -->
                <div class="col-md-7">
                    <div class="contact-form-card">
                        <h5 style="font-weight:800;color:#1e3a5f;margin-bottom:4px;">Send us a message</h5>
                        <p style="font-size:13px;color:#6b7280;margin-bottom:24px;">Fields marked <span style="color:#dc2626;">*</span> are required.</p>

                        <?php if ($formStatus === 'success'): ?>
                            <div class="form-success mb-4">
                                <i class="bi bi-check-circle-fill" style="font-size:20px;"></i>
                                <?php echo htmlspecialchars($formMessage); ?>
                            </div>
                        <?php elseif ($formStatus === 'error'): ?>
                            <div class="form-error mb-4">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($formMessage); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Full Name <span style="color:#dc2626;">*</span></label>
                                    <input type="text" name="name" class="form-control"
                                           placeholder="e.g. John Kamau"
                                           value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                                           required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Email Address <span style="color:#dc2626;">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                           placeholder="e.g. john@example.com"
                                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                           required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">I am a <span style="color:#dc2626;">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="" disabled <?php echo empty($formData['role']) ? 'selected' : ''; ?>>— Select your role —</option>
                                        <option value="Graduate" <?php echo ($formData['role'] ?? '') === 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                                        <option value="Employer" <?php echo ($formData['role'] ?? '') === 'Employer' ? 'selected' : ''; ?>>Employer / HR Professional</option>
                                        <option value="Registrar" <?php echo ($formData['role'] ?? '') === 'Registrar' ? 'selected' : ''; ?>>University Registrar</option>
                                        <option value="Other" <?php echo ($formData['role'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subject <span style="color:#dc2626;">*</span></label>
                                    <select name="subject" class="form-select" required>
                                        <option value="" disabled <?php echo empty($formData['subject']) ? 'selected' : ''; ?>>— Select a subject —</option>
                                        <option value="Verification issue" <?php echo ($formData['subject'] ?? '') === 'Verification issue' ? 'selected' : ''; ?>>Certificate verification issue</option>
                                        <option value="Account access" <?php echo ($formData['subject'] ?? '') === 'Account access' ? 'selected' : ''; ?>>Account access or login problem</option>
                                        <option value="Register university" <?php echo ($formData['subject'] ?? '') === 'Register university' ? 'selected' : ''; ?>>Register my university</option>
                                        <option value="Report fraud" <?php echo ($formData['subject'] ?? '') === 'Report fraud' ? 'selected' : ''; ?>>Report suspected certificate fraud</option>
                                        <option value="General enquiry" <?php echo ($formData['subject'] ?? '') === 'General enquiry' ? 'selected' : ''; ?>>General enquiry</option>
                                        <option value="Other" <?php echo ($formData['subject'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message <span style="color:#dc2626;">*</span></label>
                                    <textarea name="message" class="form-control" rows="5"
                                              placeholder="Describe your issue or question in as much detail as possible…"
                                              required style="resize:vertical;"><?php echo htmlspecialchars($formData['message'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="contact_submit" class="btn-send">
                                        <i class="bi bi-send me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FAQ SECTION -->
    <div class="faq-section">
        <div class="container" style="max-width:720px;">
            <div class="text-center mb-5">
                <div class="section-tag">FAQ</div>
                <h2 style="font-size:28px;font-weight:800;color:#1e3a5f;">Frequently Asked Questions</h2>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I verify a certificate as an employer?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    Log in as an Employer, go to the <strong>Confirm Graduate</strong> tab and enter the candidate's
                    first and last name. You can also use our <strong>AI Scan</strong> feature to upload the
                    certificate PDF directly and let our OCR engine verify it automatically.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    My certificate is not showing up in the system. What should I do?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    Contact your university's registrar and ask them to upload your certificate to CertsVerify.
                    Only registered university registrars can add certificates to the system. If the registrar
                    is not yet registered, they can sign up at our <a href="registrar/register.php">registrar registration page</a>.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I register my university on CertsVerify?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    The university's registrar should create a Registrar account. After registration,
                    the system administrator will review and approve the account. Once approved, the
                    registrar can log in and start uploading official certificates.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Is it free to use CertsVerify?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    Yes — CertsVerify is completely free for all users: graduates, employers, and
                    university registrars. There are no subscription fees or verification charges.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    The QR code on my certificate shows a localhost link. Is this normal?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    Certificates downloaded from CertsVerify have QR codes that encode the graduate's
                    information directly as plain text. Scanning with your phone camera will show the
                    certificate details instantly — no internet connection is required.
                    If you are seeing a localhost link, the certificate may be an older version.
                    Please contact your registrar to request an updated copy.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I report a fraudulent certificate?
                    <i class="bi bi-plus faq-icon"></i>
                </button>
                <div class="faq-answer">
                    Use the contact form above and select <strong>"Report suspected certificate fraud"</strong>
                    as the subject. Provide as much detail as possible including the certificate in question
                    and the circumstances in which it was presented to you.
                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer style="background:#1e3a5f;color:rgba(255,255,255,.6);text-align:center;padding:20px;font-size:12px;">
        © <?php echo date('Y'); ?> CertsVerify — Educational Document Verification System &nbsp;·&nbsp;
        <a href="about.php" style="color:rgba(255,255,255,.6);">About</a> &nbsp;·&nbsp;
        <a href="contact.php" style="color:rgba(255,255,255,.6);">Contact</a>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
    function toggleFaq(btn) {
        const answer = btn.nextElementSibling;
        const icon   = btn.querySelector('.faq-icon');
        const isOpen = answer.classList.contains('open');

        // Close all
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
        document.querySelectorAll('.faq-question').forEach(b => b.classList.remove('open'));

        // Open clicked (if it wasn't already open)
        if (!isOpen) {
            answer.classList.add('open');
            btn.classList.add('open');
        }
    }
    </script>
</body>
</html>
