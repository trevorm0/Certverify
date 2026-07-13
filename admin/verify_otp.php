<?php
session_start();
require_once '../processes/send_otp_email.php';

// Must have a pending OTP session under the SysAdmin namespace
$otp = $_SESSION['sessions']['SysAdmin'] ?? null;

if (empty($otp) || empty($otp['otp_pending'])) {
    header('Location: login.php');
    exit;
}

$error    = $otp['otp_error']    ?? '';
$attempts = $otp['otp_attempts'] ?? 0;
unset($_SESSION['sessions']['SysAdmin']['otp_error']);

$isResend = isset($_GET['resend']);

// Generate + email a new OTP if one doesn't exist yet, has expired, or resend was requested
if ($isResend || empty($otp['otp_code']) || time() > $otp['otp_expires']) {
    $newCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $_SESSION['sessions']['SysAdmin']['otp_code']     = $newCode;
    $_SESSION['sessions']['SysAdmin']['otp_expires']  = time() + 300; // 5 minutes
    $_SESSION['sessions']['SysAdmin']['otp_attempts'] = 0;
    $attempts = 0;

    $emailSent = send_otp_email($otp['otp_email'], $otp['otp_first_name'], $newCode);
    $_SESSION['sessions']['SysAdmin']['otp_email_failed'] = !$emailSent;

    $otp = $_SESSION['sessions']['SysAdmin']; // refresh local copy

    if ($isResend) {
        header('Location: verify_otp.php');
        exit;
    }
}

$otp_code        = $otp['otp_code'];
$expires_in      = $otp['otp_expires'] - time();
$emailFailed     = $otp['otp_email_failed'] ?? false;
$emailErrorDetail = $otp['otp_email_error'] ?? '';
$masked_email    = $otp['otp_email'] ?? '';
if ($masked_email) {
    [$local, $domain] = explode('@', $masked_email);
    $masked_email = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2)) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>2FA Verification — CertsVerify</title>
    <style>
        /* OTP digit boxes */
        .otp-wrap {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .otp-box {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .otp-box:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
            background: #fff;
        }
        .otp-box.filled { border-color: var(--blue); background: #fff; }

        /* The revealed code box */
        .code-reveal {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 10px;
            padding: 14px 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .code-reveal small {
            display: block;
            font-size: 11px;
            color: #15803d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }
        .code-reveal .otp-digits {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #166534;
        }

        /* Timer */
        .timer-wrap {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 18px;
        }
        .timer-wrap span {
            font-weight: 700;
            color: var(--blue);
        }
        .timer-wrap.expired span { color: #dc2626; }

        /* Error */
        .otp-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #dc2626;
            text-align: center;
            margin-bottom: 14px;
        }

        /* Resend link */
        .resend-wrap {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            margin-top: 14px;
        }
        .resend-wrap a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 600;
        }
        .resend-wrap a:hover { text-decoration: underline; }

        .auth-sub { text-align:center; font-size:13px; color:var(--muted); margin-bottom:18px; }
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
            <div class="auth-icon">🔒</div>
            <h2>Two-Factor Verification</h2>
            <p class="auth-sub">
                A 6-digit verification code has been sent to:<br>
                <strong><?php echo htmlspecialchars($masked_email); ?></strong>
            </p>

            <?php if ($error): ?>
                <div class="otp-error"><i class="bi bi-x-circle me-1"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($emailFailed): ?>
                <!-- Email delivery failed — fall back to on-screen code so admin isn't locked out -->
                <div class="otp-error" style="background:#fffbeb;border-color:#fde68a;color:#92400e;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Could not send email. Showing your code here instead — check your SMTP settings.
                    <?php if ($emailErrorDetail): ?>
                        <br><span style="font-size:11px;font-family:monospace;display:block;margin-top:6px;word-break:break-word;">
                            <?php echo htmlspecialchars($emailErrorDetail); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="code-reveal">
                    <small>🛡 Your verification code</small>
                    <div class="otp-digits" id="revealedCode"><?php echo $otp_code; ?></div>
                </div>
            <?php else: ?>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;text-align:center;margin-bottom:20px;">
                    <i class="bi bi-envelope-check" style="font-size:22px;color:#1d4ed8;"></i>
                    <p style="font-size:13px;color:#1e40af;margin:6px 0 0;font-weight:600;">
                        Check your inbox — the code may take a few seconds to arrive.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Countdown timer -->
            <div class="timer-wrap" id="timerWrap">
                Code expires in <span id="countdown"></span>
            </div>

            <!-- OTP input form -->
            <form method="POST" action="../processes/verify_otp_process.php" id="otpForm">
                <input type="hidden" name="otp_full" id="otp_full">

                <div class="otp-wrap">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d0">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d1">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d2">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d3">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d4">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d5">
                </div>

                <button type="submit" class="btn-cv-primary w-100" id="verifyBtn" disabled>
                    Verify Code
                </button>
            </form>

            <div class="resend-wrap">
                <a href="verify_otp.php?resend=1">Didn't work? Generate a new code</a>
            </div>

            <div class="resend-wrap" style="margin-top:8px;">
                <a href="login.php" style="color:var(--muted);">← Back to login</a>
            </div>
        </div>
    </div>

    <script>
    // ── Auto-focus & jump between boxes ──────────────────────────────────
    const boxes    = Array.from(document.querySelectorAll('.otp-box'));
    const fullInput = document.getElementById('otp_full');
    const btn       = document.getElementById('verifyBtn');

    boxes[0].focus();

    boxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            if (box.value) {
                box.classList.add('filled');
                if (i < 5) boxes[i + 1].focus();
            } else {
                box.classList.remove('filled');
            }
            syncFull();
        });

        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && i > 0) {
                boxes[i - 1].value = '';
                boxes[i - 1].classList.remove('filled');
                boxes[i - 1].focus();
                syncFull();
            }
        });

        // Allow paste of full 6-digit code
        box.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((ch, idx) => {
                if (boxes[idx]) {
                    boxes[idx].value = ch;
                    boxes[idx].classList.add('filled');
                }
            });
            if (pasted.length === 6) boxes[5].focus();
            syncFull();
        });
    });

    function syncFull() {
        const val = boxes.map(b => b.value).join('');
        fullInput.value = val;
        btn.disabled = val.length < 6;
    }

    // ── Countdown timer ──────────────────────────────────────────────────
    const expiresAt  = <?php echo $otp['otp_expires']; ?> * 1000;
    const countdown  = document.getElementById('countdown');
    const timerWrap  = document.getElementById('timerWrap');
    const revealBox  = document.getElementById('revealedCode');

    function updateTimer() {
        const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        countdown.textContent = `${m}:${s}`;

        if (remaining <= 0) {
            timerWrap.classList.add('expired');
            countdown.textContent = 'Expired';
            btn.disabled = true;
            if (revealBox) {
                revealBox.innerHTML = '<small>⏰ Code expired</small><div class="otp-digits" style="font-size:14px;letter-spacing:0;color:#dc2626;">Generate a new code below</div>';
            }
            clearInterval(timer);
        }
    }

    updateTimer();
    const timer = setInterval(updateTimer, 1000);
    </script>
</body>
</html>
