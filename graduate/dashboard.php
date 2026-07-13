<?php
// ── Access control ──

$required_role = 'Graduate';
require '../processes/auth_check.php';

$mysqli = require '../processes/db.php';

// ── Get the logged-in graduate's full details from the database ──
$grad_query  = "SELECT * FROM graduate WHERE id='" . $_SESSION['user_id'] . "' LIMIT 1";
$grad_result = mysqli_query($mysqli, $grad_query);
$grad_data   = mysqli_fetch_assoc($grad_result);

$my_admission_number = trim($grad_data['admissionNumber'] ?? '');
$my_full_name        = trim(($grad_data['firstname'] ?? '') . ' ' . ($grad_data['lastname'] ?? ''));

// ── Search result ──
$cert_results = '';
$cert_row     = null;

if (isset($_POST['save'])) {
    $search = trim($_POST["search"] ?? '');

    if ($search === '') {
        $cert_results = 'EMPTY';
    } else if ($search !== $my_admission_number) {
        $cert_results = 'NOT_YOURS';
    } else {
        $cert_check = "SELECT * FROM certificate WHERE admissionNumber='$my_admission_number' LIMIT 1";
        $cert_res   = mysqli_query($mysqli, $cert_check);
        $cert_row   = mysqli_fetch_assoc($cert_res);

        if ($cert_row) {
            $cert_results = 'FOUND';
        } else {
            $cert_results = 'NOT_UPLOADED';
        }
    }
}
?>
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
    <title>Graduate Dashboard — CertsVerify</title>
    <style>
        /* Background photo */
        body { position: relative; min-height: 100vh; }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url('../assets/img/campus.jpg') center / cover no-repeat;
            opacity: 0.35;
            z-index: -1;
        }
        .card { backdrop-filter: blur(2px); }

        /* Admission number badge */
        .adm-badge {
            background: var(--blue-light);
            border: 1px solid var(--border-blue);
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            color: var(--navy);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* ── SESSION INDICATOR ── */
        .session-indicator {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 20px;
            padding: 5px 12px 5px 8px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
            white-space: nowrap;
            border: 1px solid;
        }
        .session-dot {
            position: relative;
            width: 8px;
            height: 8px;
            flex-shrink: 0;
        }
        .session-dot::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
        }
        .session-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            opacity: 0.4;
            animation: sessionPulse 2s ease-in-out infinite;
        }
        @keyframes sessionPulse {
            0%, 100% { transform: scale(1);   opacity: 0.4; }
            50%       { transform: scale(1.8); opacity: 0;   }
        }
        /* Graduate colour — purple */
        .role-graduate {
            background: #faf5ff;
            border-color: #d8b4fe;
            color: #6b21a8;
        }
        .role-graduate .session-dot::before,
        .role-graduate .session-dot::after { background: #a855f7; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a href="../index.php" class="navbar-brand">Certs<span>Verify</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto"></ul>
                <ul class="navbar-nav align-items-center">

                    <!-- SESSION STATUS INDICATOR -->
                    <li class="nav-item">
                        <div class="session-indicator role-graduate">
                            <div class="session-dot"></div>
                            Logged in as <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
                            &nbsp;·&nbsp;
                            <span style="opacity:0.75;font-weight:500;">Graduate</span>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="nav-avatar">
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text)">
                                        <?php echo htmlentities($_SESSION['first_name']); ?>
                                        <?php echo htmlentities($_SESSION['last_name']); ?>
                                    </div>
                                    <div style="font-size:11px;color:var(--muted)">Graduate</div>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../processes/profile.php"><i class="bi bi-person-gear me-2"></i>Account Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="../processes/logout_process.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width:720px;">

        <!-- Welcome + admission number info -->
        <div class="mb-4">
            <h5 style="font-weight:800;color:var(--navy);">
                Welcome, <?php echo htmlentities($grad_data['firstname']); ?>
            </h5>
            <p style="font-size:13px;color:var(--muted);margin-bottom:10px;">
                You can only verify your own certificate.
            </p>
            <?php if ($my_admission_number): ?>
            <div class="adm-badge">
                <i class="bi bi-person-badge" style="color:var(--blue);font-size:16px;"></i>
                Your admission number:
                <span style="font-family:monospace;font-size:14px;color:var(--blue);">
                    <?php echo htmlspecialchars($my_admission_number); ?>
                </span>
            </div>
            <?php else: ?>
            <div class="alert alert-warning" style="font-size:13px;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Your account does not have an admission number set.
                Please contact your university registrar.
            </div>
            <?php endif; ?>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs mb-4" id="verifyTabs">
            <li class="nav-item">
                <button class="nav-link active" id="tab-search" onclick="showTab('search')"
                        style="font-size:13px;font-weight:600;">
                    <i class="bi bi-search me-2"></i>Search my certificate
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-ocr" onclick="showTab('ocr')"
                        style="font-size:13px;font-weight:600;">
                    <i class="bi bi-cpu me-2"></i>AI Scan (upload PDF)
                </button>
            </li>
        </ul>

        <!-- TAB 1: Search -->
        <div id="panel-search">
            <div class="card p-4 mb-4">
                <h6 style="font-weight:700;color:var(--blue);margin-bottom:12px;">
                    Search for your certificate
                </h6>
                <form method="POST">
                    <div class="cv-form-group">
                        <label>Your admission number</label>
                        <input type="text"
                               name="search"
                               class="cv-input"
                               placeholder="<?php echo htmlspecialchars($my_admission_number ?: 'Enter your admission number'); ?>"
                               value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>"
                               required>
                        <small style="font-size:11px;color:var(--hint);margin-top:4px;display:block;">
                            <i class="bi bi-info-circle me-1"></i>
                            You can only search using your own admission number.
                        </small>
                    </div>
                    <button type="submit" name="save" class="btn-cv-primary">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Search result -->
            <div class="text-center py-3">
                <?php if ($cert_results === ''): ?>
                    <i class="bi bi-search" style="font-size:60px;color:var(--hint);"></i>
                    <p class="mt-2" style="font-size:13px;color:var(--muted);">
                        Enter your admission number above to find your certificate.
                    </p>

                <?php elseif ($cert_results === 'NOT_YOURS'): ?>
                    <i class="bi bi-shield-x" style="font-size:60px;color:#dc2626;"></i>
                    <p class="mt-2" style="color:#dc2626;font-weight:600;">Access denied</p>
                    <p style="font-size:13px;color:var(--muted);">
                        You can only search for your own admission number:
                        <strong><?php echo htmlspecialchars($my_admission_number); ?></strong>
                    </p>

                <?php elseif ($cert_results === 'NOT_UPLOADED'): ?>
                    <i class="bi bi-file-earmark-x" style="font-size:60px;color:#d97706;"></i>
                    <p class="mt-2" style="color:#d97706;font-weight:600;">Certificate not yet uploaded</p>
                    <p style="font-size:13px;color:var(--muted);">
                        Your admission number was found in the system but your certificate
                        has not been uploaded yet. Please contact your university registrar.
                    </p>

                <?php elseif ($cert_results === 'FOUND' && $cert_row): ?>
                    <?php
                        // Count how many times this cert has been verified by employers
                        $cert_id_safe   = mysqli_real_escape_string($mysqli, $cert_row['certID'] ?? '');
                        $adm_safe       = mysqli_real_escape_string($mysqli, $cert_row['admissionNumber'] ?? '');
                        $verif_count    = 0;
                        $last_verif     = null;

                        // Check via activity_log (always exists) for employer verifications
                        if ($cert_id_safe) {
                            $vq = mysqli_query($mysqli,
                                "SELECT COUNT(*) as cnt, MAX(created_at) as last_check
                                 FROM activity_log
                                 WHERE action IN ('CERT_VERIFY','BATCH_VERIFY')
                                 AND role = 'Employer'
                                 AND detail LIKE '%$cert_id_safe%'"
                            );
                            if ($vq) {
                                $vr          = mysqli_fetch_assoc($vq);
                                $verif_count = (int)($vr['cnt'] ?? 0);
                                $last_verif  = $vr['last_check'] ?? null;
                            }
                        }
                    ?>
                    <i class="bi bi-patch-check-fill" style="font-size:60px;color:#059669;"></i>
                    <p class="mt-2" style="color:#059669;font-weight:700;font-size:16px;">
                        Certificate found ✓
                    </p>
                    <div class="card text-start mt-3 p-3">
                        <div style="font-size:13px;">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Student</span>
                                <span style="color:var(--text);font-weight:600;"><?php echo htmlspecialchars($cert_row['studentNames']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Admission No.</span>
                                <span style="color:var(--text);"><?php echo htmlspecialchars($cert_row['admissionNumber']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">University</span>
                                <span style="color:var(--text);"><?php echo htmlspecialchars($cert_row['universityName']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Course</span>
                                <span style="color:var(--text);text-align:right;max-width:60%;"><?php echo htmlspecialchars($cert_row['courseName']); ?></span>
                            </div>
                            <?php if (!empty($cert_row['certID'])): ?>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Certificate ID</span>
                                <span style="font-family:monospace;color:var(--blue);font-weight:700;"><?php echo htmlspecialchars($cert_row['certID']); ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Verification Status -->
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Verification Status</span>
                                <?php if ($verif_count > 0): ?>
                                    <span style="background:#f0fdf4;color:#166534;border:1px solid #86efac;font-size:11px;font-weight:700;padding:2px 10px;border-radius:8px;">
                                        <i class="bi bi-patch-check-fill me-1"></i>Verified <?php echo $verif_count; ?> time<?php echo $verif_count > 1 ? 's' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:11px;font-weight:700;padding:2px 10px;border-radius:8px;">
                                        <i class="bi bi-clock me-1"></i>Not yet verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($last_verif): ?>
                            <div class="d-flex justify-content-between py-2">
                                <span style="color:var(--muted);font-weight:600;text-transform:uppercase;font-size:11px;">Last Checked</span>
                                <span style="font-size:12px;color:var(--text);"><?php echo date('d M Y, H:i', strtotime($last_verif)); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="http://localhost/CertsVerify/serve_cert.php?id=<?php echo htmlspecialchars($cert_row['certID']); ?>"
                               target="_blank" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf me-2"></i>View Certificate
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: AI Scan -->
        <div id="panel-ocr" style="display:none;">
            <div class="card p-4">
                <div class="text-center mb-3">
                    <div style="width:48px;height:48px;background:var(--blue-light);border-radius:10px;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <i class="bi bi-cpu" style="font-size:22px;color:var(--blue);"></i>
                    </div>
                    <h6 style="font-weight:700;color:var(--navy);">AI Certificate Scanner</h6>
                    <p style="font-size:12px;color:var(--muted);">
                        Upload your certificate PDF. The system will scan it and verify it
                        against your account. <strong>Only your own certificate will be accepted.</strong>
                    </p>
                    <?php if ($my_admission_number): ?>
                    <div style="font-size:12px;color:var(--blue);font-weight:600;">
                        <i class="bi bi-lock me-1"></i>
                        Verifying for admission number:
                        <span style="font-family:monospace;"><?php echo htmlspecialchars($my_admission_number); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="../processes/ocr_verify.php" enctype="multipart/form-data" id="gradOcrForm">
                    <input type="hidden" name="expected_admission" value="<?php echo htmlspecialchars($my_admission_number); ?>">
                    <input type="hidden" name="user_role" value="Graduate">
                    <div class="cv-form-group">
                        <label>Upload your certificate PDF</label>
                        <input type="file" name="cert_file" class="form-control" accept=".pdf" required>
                        <small style="color:var(--hint);font-size:11px;margin-top:4px;display:block;">PDF only · Max 5MB</small>
                    </div>
                    <button type="submit" class="btn-cv-primary mt-2">
                        <i class="bi bi-cpu me-2"></i>Scan &amp; Verify
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
    function showTab(tab) {
        document.getElementById('panel-search').style.display = tab === 'search' ? 'block' : 'none';
        document.getElementById('panel-ocr').style.display    = tab === 'ocr'    ? 'block' : 'none';
        document.getElementById('tab-search').classList.toggle('active', tab === 'search');
        document.getElementById('tab-ocr').classList.toggle('active',    tab === 'ocr');
    }
    </script>
</body>
</html>
