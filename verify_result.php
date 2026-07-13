<?php
session_start();

// Must have a result in session
if (!isset($_SESSION['ocr_result'])) {
    header("Location: index.php");
    exit;
}

$result     = $_SESSION['ocr_result'];
$verdict    = $result['verdict'];
$confidence = $result['confidence'] ?? '—';
$message    = $result['message'] ?? '';
$checks     = $result['checks'] ?? [];
$details    = $result['details'] ?? [];
$extracted  = $result['extracted'] ?? '';

// Clear session result after reading
unset($_SESSION['ocr_result']);

// Colour + icon based on verdict
if ($verdict === 'LEGITIMATE') {
    $color     = '#059669';
    $bgColor   = '#f0fdf4';
    $border    = '#bbf7d0';
    $icon      = 'bi-patch-check-fill';
    $iconColor = '#059669';
} elseif ($verdict === 'SUSPICIOUS') {
    $color     = '#d97706';
    $bgColor   = '#fffbeb';
    $border    = '#fde68a';
    $icon      = 'bi-exclamation-triangle-fill';
    $iconColor = '#d97706';
} elseif ($verdict === 'ERROR') {
    $color     = '#6b7280';
    $bgColor   = '#f9fafb';
    $border    = '#e5e7eb';
    $icon      = 'bi-x-circle-fill';
    $iconColor = '#6b7280';
} elseif ($verdict === 'ACCESS DENIED') {
        $color     = '#7c3aed';
        $bgColor   = '#f5f3ff';
        $border    = '#ddd6fe';
        $icon      = 'bi-shield-lock-fill';
        $iconColor = '#7c3aed';
    }else {
    $color     = '#dc2626';
    $bgColor   = '#fef2f2';
    $border    = '#fecaca';
    $icon      = 'bi-shield-x';
    $iconColor = '#dc2626';
}

// Determine back link based on role
$role     = $_SESSION['role'] ?? 'Graduate';
$backLink = 'http://localhost/CertsVerify/' . strtolower(str_replace('sysadmin', 'admin', strtolower($role))) . '/dashboard.php';
?>
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
    <title>Verification Result — CertsVerify</title>
    <style>
        /* ── Scan animation ── */
        .scan-wrap {
            position: relative;
            width: 200px;
            margin: 0 auto 24px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 14px;
            box-shadow: 0 4px 20px rgba(37,99,235,0.1);
            overflow: hidden;
        }

        .scan-line {
            position: absolute;
            left: 0; width: 100%; height: 3px;
            background: linear-gradient(90deg, transparent, #2563eb, transparent);
            box-shadow: 0 0 10px rgba(37,99,235,0.8);
            animation: scanDown 1.6s ease-in-out 3;
            animation-fill-mode: forwards;
            top: 0;
            z-index: 10;
        }

        @keyframes scanDown {
            0%   { top: 0%;   opacity: 1; }
            90%  { top: 100%; opacity: 0.8; }
            100% { top: 100%; opacity: 0; }
        }

        /* Cert preview lines */
        .cert-header-bar {
            width: 55%; height: 8px;
            background: #bfdbfe;
            border-radius: 4px;
            margin: 0 auto 10px;
        }

        .cert-text-line {
            height: 5px;
            background: #f3f4f6;
            border-radius: 3px;
            margin-bottom: 6px;
        }

        .cert-text-line.w100 { width: 100%; }
        .cert-text-line.w80  { width: 80%;  }
        .cert-text-line.w65  { width: 65%;  }
        .cert-text-line.w90  { width: 90%;  }

        .cert-seal-preview {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid #bfdbfe;
            margin: 10px auto 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #2563eb;
        }

        /* ── Scanning label ── */
        .scanning-label {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            animation: fadeInOut 1.2s ease-in-out infinite;
            margin-bottom: 24px;
        }

        @keyframes fadeInOut {
            0%,100% { opacity: 1; }
            50%      { opacity: 0.3; }
        }

        /* ── Result card — hidden until animation finishes ── */
        .result-card {
            opacity: 0;
            transform: translateY(16px);
            animation: revealResult 0.6s ease forwards;
            animation-delay: 5s; /* 3 scan passes × 1.6s each + small buffer */
        }

        @keyframes revealResult {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Verdict badge ── */
        .verdict-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 800;
            background: <?php echo $bgColor; ?>;
            border: 2px solid <?php echo $border; ?>;
            color: <?php echo $color; ?>;
            margin-bottom: 12px;
        }

        /* ── Checks list ── */
        .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .check-item:last-child { border-bottom: none; }
        .check-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #059669;
            flex-shrink: 0;
        }

        /* ── Details table ── */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.4px; }
        .detail-value { color: #111827; font-weight: 500; text-align: right; max-width: 60%; }

        /* ── OCR preview box ── */
        .ocr-preview {
            background: #f8faff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 11px;
            color: #6b7280;
            font-family: monospace;
            max-height: 80px;
            overflow: hidden;
            position: relative;
            line-height: 1.6;
        }

        .ocr-preview::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 30px;
            background: linear-gradient(transparent, #f8faff);
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a href="index.php" class="navbar-brand">Certs<span>Verify</span></a>
            <span class="nav-badge"><i class="bi bi-cpu me-1"></i>OCR Verification</span>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 640px;">

        <!-- PAGE TITLE -->
        <div class="text-center mb-4">
            <h4 style="font-weight:800;color:var(--navy);">Certificate Verification Report</h4>
            <p style="font-size:13px;color:var(--muted);">AI-powered OCR scan result</p>
        </div>

        <!-- SCAN ANIMATION -->
        <div class="text-center">
            <div class="scan-wrap">
                <div class="scan-line"></div>
                <!-- Certificate preview skeleton -->
                <div class="cert-header-bar"></div>
                <div class="cert-text-line w100"></div>
                <div class="cert-text-line w80"></div>
                <div class="cert-text-line w65"></div>
                <div class="cert-text-line w100"></div>
                <div class="cert-text-line w90"></div>
                <div class="cert-text-line w80"></div>
                <div class="cert-seal-preview">✓</div>
            </div>
            <div class="scanning-label">
                <i class="bi bi-cpu me-1"></i>
                Scanning document against university records...
            </div>
        </div>

        <!-- RESULT — revealed after animation finishes -->
        <div class="result-card">

            <!-- VERDICT BADGE -->
            <div class="text-center mb-4">
                <div class="verdict-badge">
                    <i class="bi <?php echo $icon; ?>" style="font-size:24px;color:<?php echo $iconColor; ?>;"></i>
                    <?php echo $verdict; ?>
                </div>
                <?php if ($verdict !== 'ERROR'): ?>
                <div style="font-size:13px;color:var(--muted);">
                    Confidence: <strong style="color:<?php echo $color; ?>"><?php echo $confidence; ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <!-- MESSAGE -->
            <div class="card mb-3" style="border-color:<?php echo $border; ?>;background:<?php echo $bgColor; ?>;">
                <div class="card-body py-3">
                    <p style="font-size:14px;color:<?php echo $color; ?>;margin:0;font-weight:500;">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($checks)): ?>
            <!-- CHECKS PASSED -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-check2-all me-2"></i>Verification checks passed
                </div>
                <div class="card-body py-2">
                    <?php foreach ($checks as $check): ?>
                    <div class="check-item">
                        <div class="check-dot"></div>
                        <?php echo htmlspecialchars($check); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($details)): ?>
            <!-- MATCHED RECORD -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i>Matched database record
                </div>
                <div class="card-body py-2">
                    <div class="detail-row">
                        <span class="detail-label">Student</span>
                        <span class="detail-value"><?php echo htmlspecialchars($details['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Admission No.</span>
                        <span class="detail-value"><?php echo htmlspecialchars($details['admission']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">University</span>
                        <span class="detail-value"><?php echo htmlspecialchars($details['university']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Course</span>
                        <span class="detail-value"><?php echo htmlspecialchars($details['course']); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($extracted)): ?>
            <!-- OCR EXTRACTED TEXT PREVIEW -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-file-text me-2"></i>OCR extracted text preview
                </div>
                <div class="card-body py-3">
                    <div class="ocr-preview"><?php echo htmlspecialchars($extracted); ?></div>
                    <small style="color:var(--hint);font-size:11px;margin-top:6px;display:block;">
                        Showing first 300 characters extracted by OCR engine
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <!-- ACTION BUTTONS -->
            <div class="d-flex gap-3 justify-content-center">
                <a href="<?php echo $backLink; ?>" class="btn-cv-primary" style="width:auto;padding:11px 28px;">
                    ← Back to dashboard
                </a>
                <a href="index.php" class="btn-cv-ghost" style="width:auto;padding:11px 28px;">
                    Home
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>
</html>
