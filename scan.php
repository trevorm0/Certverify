<?php
/*
 * ============================================================
 * scan.php — QR Code Verification Page
 * ============================================================
 * This page is loaded when someone scans the QR code on a
 * printed or downloaded certificate. No login required.
 *
 * URL format: http://localhost/CertsVerify/scan.php?id=CV-2024-STR-00001
 *
 * Copy this file to: C:\xampp\htdocs\CertsVerify\scan.php
 * ============================================================
 */

$mysqli = require 'processes/db.php';

$certID  = trim($_GET['id'] ?? '');
$cert    = null;
$reg     = null;
$error   = '';

if ($certID === '') {
    $error = 'No certificate ID provided.';
} else {
    // Sanitize
    $safe = mysqli_real_escape_string($mysqli, $certID);

    // Fetch certificate + registrar in one query
    $result = mysqli_query($mysqli,
        "SELECT c.*, r.firstname as reg_first, r.lastname as reg_last,
                r.universityName as reg_uni, r.email as reg_email
         FROM certificate c
         LEFT JOIN registrar r ON c.registrar_id = r.id
         WHERE c.certID = '$safe'
         LIMIT 1"
    );

    if ($result && mysqli_num_rows($result) > 0) {
        $cert = mysqli_fetch_assoc($result);
    } else {
        $error = 'No certificate found with this ID. It may be invalid or forged.';
    }
}

// Grade colour mapping
$gradeColors = [
    'First Class Honours'            => ['bg' => '#f0fdf4', 'border' => '#86efac', 'text' => '#166534', 'badge' => '#059669'],
    'Second Class Honours (Upper)'   => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e40af', 'badge' => '#2563eb'],
    'Second Class Honours (Lower)'   => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e', 'badge' => '#d97706'],
    'Pass'                           => ['bg' => '#f9fafb', 'border' => '#e5e7eb', 'text' => '#374151', 'badge' => '#6b7280'],
];
$grade      = $cert['grade']   ?? 'Not recorded';
$gradeStyle = $gradeColors[$grade] ?? $gradeColors['Pass'];
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
    <title>Certificate Verification — CertsVerify</title>
    <style>
        body {
            background: #f0f4ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
        }

        .scan-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 32px rgba(37,99,235,0.12);
            padding: 36px 40px;
            width: 100%;
            max-width: 520px;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: #2563eb;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px;
        }

        .brand-name {
            font-size: 20px; font-weight: 800; color: #0f172a;
        }

        .brand-name span { color: #2563eb; }

        /* Verdict banners */
        .verdict-legit {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 24px;
        }

        .verdict-error {
            background: #fef2f2;
            border: 1.5px solid #fca5a5;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 24px;
        }

        .verdict-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }

        .verdict-icon.green { background: #dcfce7; }
        .verdict-icon.red   { background: #fee2e2; }

        .verdict-title {
            font-size: 15px; font-weight: 800;
        }

        .verdict-sub {
            font-size: 12px; margin-top: 2px;
        }

        /* Detail rows */
        .detail-section {
            margin-bottom: 20px;
        }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 12px;
        }

        .detail-row:last-child { border-bottom: none; }

        .detail-key {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            flex-shrink: 0;
            width: 130px;
        }

        .detail-val {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            flex: 1;
        }

        .certid-mono {
            font-family: monospace;
            background: #eff6ff;
            color: #2563eb;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        .grade-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid;
        }

        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }

        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            margin-top: 20px;
        }

        .scan-time {
            text-align: center;
            font-size: 11px;
            color: #d1d5db;
            margin-top: 8px;
        }

        /* Scan animation */
        .scan-anim {
            width: 60px; height: 60px;
            border: 3px solid #2563eb;
            border-radius: 12px;
            position: relative;
            margin: 0 auto 20px;
            overflow: hidden;
        }

        .scan-line {
            position: absolute;
            left: 0; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, #2563eb, transparent);
            animation: scanLine 1.5s ease-in-out 2 forwards;
        }

        @keyframes scanLine {
            0%   { top: 0; opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .scan-corner {
            position: absolute;
            width: 12px; height: 12px;
            border-color: #1e3a5f;
            border-style: solid;
        }

        .tl { top: 4px; left: 4px;  border-width: 3px 0 0 3px; }
        .tr { top: 4px; right: 4px; border-width: 3px 3px 0 0; }
        .bl { bottom: 4px; left: 4px;  border-width: 0 0 3px 3px; }
        .br { bottom: 4px; right: 4px; border-width: 0 3px 3px 0; }
    </style>
</head>
<body>

<div class="scan-card">

    <!-- Brand -->
    <div class="brand-row">
        <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
        <div class="brand-name">Certs<span>Verify</span></div>
    </div>

    <!-- Scan animation (shows briefly before result) -->
    <div class="scan-anim" id="scanAnim">
        <div class="scan-line"></div>
        <div class="scan-corner tl"></div>
        <div class="scan-corner tr"></div>
        <div class="scan-corner bl"></div>
        <div class="scan-corner br"></div>
    </div>

    <div id="resultSection" style="display:none;">

        <?php if ($cert): ?>

        <!-- LEGITIMATE VERDICT -->
        <div class="verdict-legit">
            <div class="verdict-icon green">✅</div>
            <div>
                <div class="verdict-title" style="color:#166534;">Certificate Verified</div>
                <div class="verdict-sub" style="color:#166534;">
                    This certificate is authentic and matches our records.
                </div>
            </div>
        </div>

        <!-- STUDENT DETAILS -->
        <div class="detail-section">
            <div class="section-label">Student information</div>

            <div class="detail-row">
                <div class="detail-key">Full name</div>
                <div class="detail-val"><?php echo htmlspecialchars($cert['studentNames']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Admission no.</div>
                <div class="detail-val"><?php echo htmlspecialchars($cert['admissionNumber']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-key">University</div>
                <div class="detail-val"><?php echo htmlspecialchars($cert['universityName']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Course</div>
                <div class="detail-val"><?php echo htmlspecialchars($cert['courseName']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Period</div>
                <div class="detail-val">
                    <?php
                    $start = $cert['startDate'] ? date('M Y', strtotime($cert['startDate'])) : '—';
                    $end   = $cert['endDate']   ? date('M Y', strtotime($cert['endDate']))   : '—';
                    echo htmlspecialchars("$start – $end");
                    ?>
                </div>
            </div>

            <!-- GRADE -->
            <div class="detail-row">
                <div class="detail-key">Classification</div>
                <div class="detail-val">
                    <span class="grade-badge"
                          style="background:<?php echo $gradeStyle['bg']; ?>;
                                 border-color:<?php echo $gradeStyle['border']; ?>;
                                 color:<?php echo $gradeStyle['text']; ?>;">
                        <?php echo htmlspecialchars($grade); ?>
                    </span>
                </div>
            </div>

        </div>

        <hr class="divider">

        <!-- CERTIFICATE DETAILS -->
        <div class="detail-section">
            <div class="section-label">Certificate record</div>

            <div class="detail-row">
                <div class="detail-key">Certificate ID</div>
                <div class="detail-val">
                    <span class="certid-mono"><?php echo htmlspecialchars($cert['certID']); ?></span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Uploaded by</div>
                <div class="detail-val">
                    <?php echo htmlspecialchars(($cert['reg_first'] ?? '') . ' ' . ($cert['reg_last'] ?? '')); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Upload date</div>
                <div class="detail-val">
                    <?php echo htmlspecialchars($cert['uploadedAt'] ?? '—'); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-key">Institution</div>
                <div class="detail-val"><?php echo htmlspecialchars($cert['reg_uni'] ?? '—'); ?></div>
            </div>

        </div>

        <hr class="divider">

        <!-- VERIFIED AT -->
        <div style="text-align:center;">
            <a href="http://localhost/CertsVerify/" class="btn btn-primary btn-sm"
               style="border-radius:20px;padding:8px 24px;font-size:12px;">
                <i class="bi bi-house me-2"></i>Go to CertsVerify
            </a>
        </div>

        <?php else: ?>

        <!-- ERROR / NOT FOUND -->
        <div class="verdict-error">
            <div class="verdict-icon red">❌</div>
            <div>
                <div class="verdict-title" style="color:#991b1b;">Certificate Not Found</div>
                <div class="verdict-sub" style="color:#991b1b;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        </div>

        <div style="text-align:center;padding:16px 0;color:#6b7280;font-size:13px;">
            <i class="bi bi-exclamation-triangle me-2" style="color:#d97706;"></i>
            The QR code you scanned does not match any record in our database.
            This certificate may have been forged or altered.
            <br><br>
            <strong>Certificate ID scanned:</strong>
            <span class="certid-mono"><?php echo htmlspecialchars($certID ?: '(none)'); ?></span>
        </div>

        <div style="text-align:center;margin-top:16px;">
            <a href="http://localhost/CertsVerify/" class="btn btn-primary btn-sm"
               style="border-radius:20px;padding:8px 24px;font-size:12px;">
                <i class="bi bi-house me-2"></i>Go to CertsVerify
            </a>
        </div>

        <?php endif; ?>

    </div><!-- end resultSection -->

    <div class="footer-note">
        Verified by CertsVerify · Academic Certificate Verification System
    </div>
    <div class="scan-time">
        Scanned: <?php echo date('d M Y, H:i:s'); ?>
    </div>

</div>

<script>
    // Show scan animation for 2 seconds then reveal result
    setTimeout(function () {
        document.getElementById('scanAnim').style.display   = 'none';
        document.getElementById('resultSection').style.display = 'block';
    }, 2000);
</script>

</body>
</html>
