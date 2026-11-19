<?php
$required_role = 'Employer';
require '../processes/auth_check.php';
?>

<?php
$mysqli = require '../processes/db.php';

// Create verification history table if it doesn't exist yet
mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS verif_history (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    employer_id  INT          NOT NULL,
    cert_id      VARCHAR(60),
    student_name VARCHAR(200),
    university   VARCHAR(200),
    course       VARCHAR(200),
    verdict      VARCHAR(30)  NOT NULL,
    confidence   VARCHAR(10),
    method       VARCHAR(20)  NOT NULL DEFAULT 'single',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employer (employer_id),
    INDEX idx_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cert_results = '';
$searched     = false;
$search_post  = [];
$cert_row     = null;
$multi_rows   = [];

if (isset($_POST['save']) && !empty($_POST['first_name']) && !empty($_POST['last_name'])) {
    $searched         = true;
    $first_name       = trim(mysqli_real_escape_string($mysqli, $_POST['first_name']));
    $last_name        = trim(mysqli_real_escape_string($mysqli, $_POST['last_name']));
    $university_name  = trim(mysqli_real_escape_string($mysqli, $_POST['university_name'] ?? ''));
    $admission_number = trim(mysqli_real_escape_string($mysqli, $_POST['admission_number'] ?? ''));
    $grad_year        = trim(mysqli_real_escape_string($mysqli, $_POST['grad_year'] ?? ''));
    $course_name      = trim(mysqli_real_escape_string($mysqli, $_POST['course_name'] ?? ''));
    $full_name        = $first_name . ' ' . $last_name;

    // Repopulate form after submit
    $search_post = [
        'first_name'       => $_POST['first_name'],
        'last_name'        => $_POST['last_name'],
        'university_name'  => $_POST['university_name'] ?? '',
        'admission_number' => $_POST['admission_number'] ?? '',
        'grad_year'        => $_POST['grad_year'] ?? '',
        'course_name'      => $_POST['course_name'] ?? '',
    ];

    // Build query — university, admission no., year and course are all optional filters
    $where = "WHERE studentNames='$full_name'";
    if (!empty($university_name)) $where .= " AND universityName='$university_name'";
    if (!empty($admission_number)) $where .= " AND admissionNumber='$admission_number'";
    if (!empty($grad_year))        $where .= " AND (startDate LIKE '%$grad_year%' OR endDate LIKE '%$grad_year%')";
    if (!empty($course_name))      $where .= " AND courseName LIKE '%$course_name%'";

    $cert_result = mysqli_query($mysqli, "SELECT * FROM certificate $where");
    $total       = mysqli_num_rows($cert_result);

    if ($total === 0) {
        $cert_results = 'NOT_FOUND';
        // Log failed search to history
        $emp_id = (int)$_SESSION['user_id'];
        $sn = mysqli_real_escape_string($mysqli, $full_name);
        mysqli_query($mysqli, "INSERT INTO verif_history (employer_id,student_name,verdict,method,created_at)
            VALUES ($emp_id,'$sn','NOT FOUND','search',NOW())");
    } elseif ($total === 1) {
        $cert_row     = mysqli_fetch_assoc($cert_result);
        $cert_results = 'FOUND';
        // Save to verification history
        $emp_id = (int)$_SESSION['user_id'];
        $sn  = mysqli_real_escape_string($mysqli, $cert_row['studentNames']);
        $uni = mysqli_real_escape_string($mysqli, $cert_row['universityName']);
        $crs = mysqli_real_escape_string($mysqli, $cert_row['courseName']);
        $cid = mysqli_real_escape_string($mysqli, $cert_row['certID'] ?? '');
        mysqli_query($mysqli, "INSERT INTO verif_history (employer_id,cert_id,student_name,university,course,verdict,confidence,method,created_at)
            VALUES ($emp_id,'$cid','$sn','$uni','$crs','VERIFIED','100%','search',NOW())");
    } else {
        $cert_results = 'MULTIPLE_FOUND';
        while ($r = mysqli_fetch_assoc($cert_result)) $multi_rows[] = $r;
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
    <title>Employer Dashboard — CertsVerify</title>
    <style>
        body { position: relative; min-height: 100vh; background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f0f7ff 100%); }
        .card { backdrop-filter: none; }

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
        /* Employer colour — cyan */
        .role-employer {
            background: #ecfeff;
            border-color: #a5f3fc;
            color: #155e75;
        }
        .role-employer .session-dot::before,
        .role-employer .session-dot::after { background: #06b6d4; }
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
                <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">

                    <!-- SESSION STATUS INDICATOR -->
                    <li class="nav-item">
                        <div class="session-indicator role-employer">
                            <div class="session-dot"></div>
                            Logged in as <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
                            &nbsp;·&nbsp;
                            <span style="opacity:0.75;font-weight:500;">Employer</span>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="nav-avatar"><?php echo strtoupper(substr($_SESSION['first_name'],0,1)); ?></div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?php echo htmlentities($_SESSION['first_name'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlentities($_SESSION['last_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div style="font-size:11px;color:var(--muted)"><?php echo htmlentities($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../processes/profile.php"><i class="bi bi-person-gear me-2"></i>Account Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../processes/logout_process.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- TABS -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-sm-10">

                <ul class="nav nav-tabs mb-4" id="verifyTabs">
                    <li class="nav-item">
                        <button class="nav-link active" id="tab-search" onclick="showTab('search')" style="font-size:13px;font-weight:600;">
                            <i class="bi bi-person-check me-2"></i>Confirm Graduate
                           
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-ocr" onclick="showTab('ocr')" style="font-size:13px;font-weight:600;">
                            <i class="bi bi-cpu me-2"></i>AI Scan (upload PDF)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab']==='batch' ? 'active' : ''; ?>" id="tab-batch" onclick="showTab('batch')" style="font-size:13px;font-weight:600;">
                            <i class="bi bi-files me-2"></i>Batch Verify
                            <span class="badge bg-primary ms-1" style="font-size:10px;"></span>

                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab']==='history' ? 'active' : ''; ?>" id="tab-history" onclick="showTab('history')" style="font-size:13px;font-weight:600;">
                            <i class="bi bi-clock-history me-2"></i>My History
                        </button>
                    </li>
                </ul>

                <!-- Tab 1: Confirm Graduate -->
                <div id="panel-search">
                    <div class="card p-4 p-md-5">

                        <!-- Icon + title -->
                        <div class="text-center mb-4">
                            <div style="width:56px;height:56px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                <i class="bi bi-person-check-fill" style="font-size:26px;color:#059669;"></i>
                            </div>
                            <h5 style="font-weight:700;color:var(--navy);margin-bottom:4px;">Confirm Graduate Details</h5>
                            <p style="font-size:13px;color:var(--muted);margin:0;">Enter the graduate's details to confirm they graduated and view their certificate.</p>
                        </div>

                        <form method="POST">
                            <!-- Row 1: First Name · Last Name · Admission No. · Graduation Year -->
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        First Name <span style="color:#dc2626;">*</span>
                                    </label>
                                    <input class="form-control" type="text" name="first_name"
                                           placeholder="e.g. Oliver"
                                           value="<?php echo htmlspecialchars($search_post['first_name'] ?? ''); ?>"
                                           required style="border-radius:8px;">
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        Last Name <span style="color:#dc2626;">*</span>
                                    </label>
                                    <input class="form-control" type="text" name="last_name"
                                           placeholder="e.g. Mwangi"
                                           value="<?php echo htmlspecialchars($search_post['last_name'] ?? ''); ?>"
                                           required style="border-radius:8px;">
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        Admission No. <span style="color:var(--muted);font-weight:400;">(Optional)</span>
                                    </label>
                                    <input class="form-control" type="text" name="admission_number"
                                           placeholder="e.g. ENG/IT/001/2021"
                                           value="<?php echo htmlspecialchars($search_post['admission_number'] ?? ''); ?>"
                                           style="border-radius:8px;">
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        Graduation Year <span style="color:var(--muted);font-weight:400;">(Optional)</span>
                                    </label>
                                    <input class="form-control" type="text" name="grad_year"
                                           placeholder="e.g. 2022"
                                           value="<?php echo htmlspecialchars($search_post['grad_year'] ?? ''); ?>"
                                           maxlength="4" style="border-radius:8px;">
                                </div>
                            </div>

                            <!-- Row 2: University · Course / Programme · Confirm button -->
                            <div class="row g-3 align-items-end">
                                <div class="col-sm-6 col-md-4">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        University <span style="color:var(--muted);font-weight:400;">(Optional)</span>
                                    </label>
                                    <?php
                                        // Pull universities dynamically from DB
                                        $uniList = mysqli_query($mysqli, "SELECT DISTINCT universityName FROM certificate ORDER BY universityName ASC");
                                    ?>
                                    <select name="university_name" class="form-control form-select" style="border-radius:8px;">
                                        <option value="">— Any University —</option>
                                        <?php while ($u = mysqli_fetch_assoc($uniList)): ?>
                                            <option value="<?php echo htmlspecialchars($u['universityName']); ?>"
                                                <?php echo ($search_post['university_name'] ?? '') === $u['universityName'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($u['universityName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">
                                        Course / Programme <span style="color:var(--muted);font-weight:400;">(Optional)</span>
                                    </label>
                                    <input class="form-control" type="text" name="course_name"
                                           placeholder="e.g. Computer Science"
                                           value="<?php echo htmlspecialchars($search_post['course_name'] ?? ''); ?>"
                                           style="border-radius:8px;">
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <button class="btn w-100" name="save" type="submit"
                                            style="background:#059669;color:#fff;font-weight:700;border-radius:8px;padding:10px 20px;font-size:14px;">
                                        <i class="bi bi-person-check me-2"></i>Confirm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Result section -->
                    <?php if ($searched): ?>
                    <div class="card mt-3 p-4 text-center">
                        <?php if ($cert_results === 'NOT_FOUND'): ?>
                            <i class="bi bi-person-fill-x" style="font-size:56px;color:#dc2626;"></i>
                            <p class="mt-3" style="font-weight:700;color:#dc2626;">No record found</p>
                            <p class="text-muted" style="font-size:13px;">
                                No certificate matching <strong><?php echo htmlspecialchars(($search_post['first_name'] ?? '') . ' ' . ($search_post['last_name'] ?? '')); ?></strong>
                                was found in the system.
                                <br>Try adding more details above or check the spelling of the name.
                            </p>

                        <?php elseif ($cert_results === 'MULTIPLE_FOUND'): ?>
                            <i class="bi bi-people-fill" style="font-size:56px;color:#d97706;"></i>
                            <p class="mt-3" style="font-weight:700;color:#d97706;"><?php echo count($multi_rows); ?> records found with this name</p>
                            <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
                                Add the <strong>Admission Number</strong>, <strong>Graduation Year</strong>, or <strong>Course</strong> above to narrow it down.
                            </p>
                            <!-- Show all matches as a mini table -->
                            <div class="table-responsive text-start">
                                <table class="table" style="font-size:13px;">
                                    <thead><tr><th>Name</th><th>University</th><th>Course</th><th>Admission No.</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($multi_rows as $mr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($mr['studentNames']); ?></td>
                                            <td><?php echo htmlspecialchars($mr['universityName']); ?></td>
                                            <td style="font-size:12px;"><?php echo htmlspecialchars($mr['courseName']); ?></td>
                                            <td><code style="font-size:11px;"><?php echo htmlspecialchars($mr['admissionNumber']); ?></code></td>
                                            <td>
                                                <a href="http://localhost/CertsVerify/serve_cert.php?id=<?php echo urlencode($mr['certID']); ?>"
                                                   target="_blank" class="btn btn-sm btn-primary" style="font-size:11px;padding:3px 10px;">
                                                    View Cert
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($cert_results === 'FOUND' && $cert_row): ?>
                            <i class="bi bi-patch-check-fill" style="font-size:56px;color:#059669;"></i>
                            <p class="mt-3" style="font-weight:700;color:#059669;font-size:16px;">Graduate Confirmed ✓</p>
                            <!-- Details card -->
                            <div class="card text-start mt-3 p-3" style="max-width:480px;margin:0 auto;">
                                <div style="font-size:13px;">
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span style="color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;">Student</span>
                                        <span style="font-weight:700;"><?php echo htmlspecialchars($cert_row['studentNames']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span style="color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;">University</span>
                                        <span><?php echo htmlspecialchars($cert_row['universityName']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span style="color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;">Course</span>
                                        <span style="text-align:right;max-width:60%;"><?php echo htmlspecialchars($cert_row['courseName']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span style="color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;">Admission No.</span>
                                        <span><?php echo htmlspecialchars($cert_row['admissionNumber']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2">
                                        <span style="color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;">Certificate ID</span>
                                        <span style="font-family:monospace;color:var(--blue);font-weight:700;"><?php echo htmlspecialchars($cert_row['certID']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <a href="http://localhost/CertsVerify/serve_cert.php?id=<?php echo urlencode($cert_row['certID']); ?>"
                               target="_blank" class="btn mt-3"
                               style="background:#059669;color:#fff;font-weight:700;border-radius:8px;padding:10px 28px;">
                                <i class="bi bi-file-earmark-pdf me-2"></i>View Certificate
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <!-- Empty state -->
                    <div class="text-center mt-4" style="padding:20px 0;">
                        <i class="bi bi-person-search" style="font-size:52px;color:var(--hint);"></i>
                        <p class="mt-3 text-muted" style="font-size:13px;">
                            Enter the graduate's first and last name above and click <strong>Confirm</strong>.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tab 2: OCR Upload -->
                <div id="panel-ocr" style="display:none;">
                    <div class="card p-4">
                        <div class="text-center mb-3">
                            <div style="width:48px;height:48px;background:var(--blue-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                <i class="bi bi-cpu" style="font-size:22px;color:var(--blue);"></i>
                            </div>
                            <h6 style="font-weight:700;color:var(--navy);">AI Certificate Scanner</h6>
                            <p style="font-size:12px;color:var(--muted);">Upload the candidate's certificate PDF and our OCR engine will scan and verify it automatically.</p>
                        </div>
                        <form method="POST" action="../processes/ocr_verify.php" enctype="multipart/form-data" id="singleOcrForm">
                            <div class="cv-form-group">
                                <label>Upload certificate PDF</label>
                                <input type="file" name="cert_file" class="form-control" accept=".pdf" required>
                                <small style="color:var(--hint);font-size:11px;margin-top:4px;display:block;">PDF only · Max 5MB</small>
                            </div>
                            <button type="submit" class="btn-cv-primary mt-2" id="singleOcrBtn">
                                <i class="bi bi-cpu me-2"></i>Scan &amp; Verify
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tab 3: Batch Verify -->
                <div id="panel-batch" style="display:<?php echo isset($_GET['tab']) && $_GET['tab']==='batch' ? 'block' : 'none'; ?>;">
                    <div class="card p-4">
                        <div class="text-center mb-3">
                            <div style="width:48px;height:48px;background:var(--blue-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                <i class="bi bi-files" style="font-size:22px;color:var(--blue);"></i>
                            </div>
                            <h6 style="font-weight:700;color:var(--navy);">Batch Certificate Verification</h6>
                            <p style="font-size:12px;color:var(--muted);">Upload multiple certificate PDFs at once. The system will scan and verify each one automatically.</p>
                        </div>
                        <form method="POST" action="../processes/ocr_verify_batch.php" enctype="multipart/form-data" id="batchOcrForm">
                            <div class="cv-form-group">
                                <label>Upload certificate PDFs</label>
                                <input type="file" name="cert_files[]" id="batchFileInput" class="form-control" accept=".pdf" multiple required>
                                <small style="color:var(--hint);font-size:11px;margin-top:4px;display:block;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Select multiple PDFs at once · Max 5MB each · Up to 10 files
                                </small>
                            </div>
                            <!-- File count indicator -->
                            <div id="fileCountBadge" style="display:none;margin-bottom:12px;">
                                <span class="badge bg-primary" id="fileCountText"></span>
                            </div>
                            <button type="submit" class="btn-cv-primary mt-2" id="batchOcrBtn">
                                <i class="bi bi-files me-2"></i>Verify All
                            </button>
                        </form>
                    </div>

                    <!-- Batch Results -->
                    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'batch' && !empty($_SESSION['batch_results'])): ?>
                        <?php
                            $results  = $_SESSION['batch_results'];
                            unset($_SESSION['batch_results']);
                            $total     = count($results);
                            $verified  = count(array_filter($results, fn($r) => $r['verdict'] === 'LEGITIMATE'));
                            $failed    = count(array_filter($results, fn($r) => in_array($r['verdict'], ['NOT FOUND','SUSPICIOUS'])));
                            $errors    = count(array_filter($results, fn($r) => $r['verdict'] === 'ERROR'));
                        ?>

                        <!-- Summary pills -->
                        <div class="d-flex gap-3 mt-4 mb-3 flex-wrap">
                            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 18px;text-align:center;min-width:80px;">
                                <div style="font-size:22px;font-weight:700;color:#059669;"><?php echo $verified; ?></div>
                                <div style="font-size:11px;color:#065f46;">Verified</div>
                            </div>
                            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 18px;text-align:center;min-width:80px;">
                                <div style="font-size:22px;font-weight:700;color:#dc2626;"><?php echo $failed; ?></div>
                                <div style="font-size:11px;color:#991b1b;">Failed</div>
                            </div>
                            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 18px;text-align:center;min-width:80px;">
                                <div style="font-size:22px;font-weight:700;color:#d97706;"><?php echo $errors; ?></div>
                                <div style="font-size:11px;color:#92400e;">Errors</div>
                            </div>
                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 18px;text-align:center;min-width:80px;">
                                <div style="font-size:22px;font-weight:700;color:#0369a1;"><?php echo $total; ?></div>
                                <div style="font-size:11px;color:#0c4a6e;">Total</div>
                            </div>
                        </div>

                        <!-- Results table -->
                        <div class="table-responsive mt-2">
                            <table class="table" style="font-size:13px;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>File</th>
                                        <th>Student</th>
                                        <th>University</th>
                                        <th>Cert ID</th>
                                        <th>Confidence</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($results as $i => $r): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($r['file']); ?>">
                                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                            <?php echo htmlspecialchars($r['file']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['details']['name'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($r['details']['university'] ?? '—'); ?></td>
                                        <td><code style="font-size:11px;"><?php echo htmlspecialchars($r['details']['cert_id'] ?? '—'); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($r['confidence']); ?></strong></td>
                                        <td>
                                            <?php if ($r['verdict'] === 'LEGITIMATE'): ?>
                                                <span class="badge" style="background:#dcfce7;color:#166534;font-size:11px;">
                                                    <i class="bi bi-patch-check-fill me-1"></i>Verified
                                                </span>
                                            <?php elseif ($r['verdict'] === 'SUSPICIOUS'): ?>
                                                <span class="badge" style="background:#fef9c3;color:#854d0e;font-size:11px;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Suspicious
                                                </span>
                                            <?php elseif ($r['verdict'] === 'NOT FOUND'): ?>
                                                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:11px;">
                                                    <i class="bi bi-x-circle-fill me-1"></i>Not Found
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#f3f4f6;color:#374151;font-size:11px;">
                                                    <i class="bi bi-exclamation-circle me-1"></i>Error
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($r['message'])): ?>
                                    <tr style="background:#fafafa;">
                                        <td colspan="7" style="font-size:11px;color:var(--muted);padding:4px 12px 8px;">
                                            <i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($r['message']); ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab 4: My Verification History -->
                <?php
                    $emp_id   = (int)$_SESSION['user_id'];
                    $hist_page     = max(1, (int)($_GET['hist_page'] ?? 1));
                    $hist_per_page = 15;
                    $hist_total    = (int)(mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as c FROM verif_history WHERE employer_id=$emp_id"))['c'] ?? 0);
                    $hist_pages    = max(1, ceil($hist_total / $hist_per_page));
                    $hist_page     = min($hist_page, $hist_pages);
                    $hist_offset   = ($hist_page - 1) * $hist_per_page;
                    $hist_result   = mysqli_query($mysqli, "SELECT * FROM verif_history WHERE employer_id=$emp_id ORDER BY created_at DESC LIMIT $hist_per_page OFFSET $hist_offset");
                ?>
                <div id="panel-history" style="display:<?php echo (isset($_GET['tab']) && $_GET['tab']==='history') ? 'block' : 'none'; ?>;">

                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 style="font-weight:700;color:var(--navy);margin:0;">My Verification History</h6>
                            <span style="font-size:12px;color:var(--muted);"><?php echo $hist_total; ?> verification(s) performed</span>
                        </div>
                    </div>

                    <?php if ($hist_total === 0): ?>
                        <div class="card p-5 text-center">
                            <i class="bi bi-clock-history" style="font-size:52px;color:var(--hint);"></i>
                            <p class="mt-3" style="font-weight:600;color:var(--navy);">No verifications yet</p>
                            <p style="font-size:13px;color:var(--muted);">Your verification history will appear here after you verify a certificate.</p>
                        </div>
                    <?php else: ?>
                        <div class="card mb-3"><div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0" style="font-size:13px;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student</th>
                                            <th>Certificate ID</th>
                                            <th>University</th>
                                            <th>Course</th>
                                            <th>Confidence</th>
                                            <th>Method</th>
                                            <th>Result</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i = $hist_offset + 1; while ($h = mysqli_fetch_assoc($hist_result)): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td style="font-weight:600;"><?php echo htmlspecialchars($h['student_name'] ?: '—'); ?></td>
                                            <td>
                                                <?php if (!empty($h['cert_id'])): ?>
                                                    <code style="font-size:11px;color:var(--blue);"><?php echo htmlspecialchars($h['cert_id']); ?></code>
                                                <?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?>
                                            </td>
                                            <td style="font-size:12px;"><?php echo htmlspecialchars($h['university'] ?: '—'); ?></td>
                                            <td style="font-size:11px;max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($h['course'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($h['course'] ?: '—'); ?>
                                            </td>
                                            <td style="font-weight:700;"><?php echo htmlspecialchars($h['confidence'] ?: '—'); ?></td>
                                            <td>
                                                <?php $m = $h['method'] ?? 'single'; ?>
                                                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;
                                                    background:<?php echo $m==='batch' ? '#f0fdf4' : ($m==='ocr' ? '#eff6ff' : '#fff7ed'); ?>;
                                                    color:<?php echo $m==='batch' ? '#166534' : ($m==='ocr' ? '#1e40af' : '#92400e'); ?>;">
                                                    <?php echo $m === 'batch' ? 'Batch' : ($m === 'ocr' ? 'AI Scan' : 'Search'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $v = $h['verdict'] ?? ''; ?>
                                                <?php if (in_array($v, ['LEGITIMATE','VERIFIED'])): ?>
                                                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;background:#dcfce7;color:#166534;">
                                                        <i class="bi bi-patch-check-fill me-1"></i>Verified
                                                    </span>
                                                <?php elseif ($v === 'NOT FOUND'): ?>
                                                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;background:#fee2e2;color:#991b1b;">
                                                        <i class="bi bi-x-circle-fill me-1"></i>Not Found
                                                    </span>
                                                <?php elseif ($v === 'SUSPICIOUS'): ?>
                                                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;background:#fef9c3;color:#854d0e;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Suspicious
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;background:#f3f4f6;color:#374151;">
                                                        <?php echo htmlspecialchars($v); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:11px;color:var(--muted);white-space:nowrap;">
                                                <?php echo date('d M Y H:i', strtotime($h['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div></div>

                        <!-- Pagination -->
                        <?php if ($hist_pages > 1): ?>
                        <nav class="mt-2">
                            <ul class="pagination justify-content-center" style="font-size:13px;">
                                <li class="page-item <?php echo $hist_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=history&hist_page=<?php echo $hist_page-1; ?>">«</a>
                                </li>
                                <?php for($p=1;$p<=$hist_pages;$p++): ?>
                                <li class="page-item <?php echo $p===$hist_page?'active':''; ?>">
                                    <a class="page-link" href="?tab=history&hist_page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $hist_page >= $hist_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=history&hist_page=<?php echo $hist_page+1; ?>">»</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>

                </div><!-- /panel-history -->

            </div>
        </div>
    </div>

    <script>    // Single OCR form
    document.getElementById('singleOcrForm').addEventListener('submit', function() {
        document.getElementById('singleOcrBtn').disabled = true;
    });

    // Batch OCR form
    document.getElementById('batchOcrForm').addEventListener('submit', function() {
        document.getElementById('batchOcrBtn').disabled = true;
    });

    function showTab(tab) {
        document.getElementById('panel-search').style.display  = tab === 'search'  ? 'block' : 'none';
        document.getElementById('panel-ocr').style.display     = tab === 'ocr'     ? 'block' : 'none';
        document.getElementById('panel-batch').style.display   = tab === 'batch'   ? 'block' : 'none';
        document.getElementById('panel-history').style.display = tab === 'history' ? 'block' : 'none';
        document.getElementById('tab-search').classList.toggle('active',  tab === 'search');
        document.getElementById('tab-ocr').classList.toggle('active',     tab === 'ocr');
        document.getElementById('tab-batch').classList.toggle('active',   tab === 'batch');
        document.getElementById('tab-history').classList.toggle('active', tab === 'history');
    }

    // Show file count when user selects files
    document.getElementById('batchFileInput').addEventListener('change', function () {
        const count   = this.files.length;
        const badge   = document.getElementById('fileCountBadge');
        const text    = document.getElementById('fileCountText');
        if (count > 0) {
            text.textContent = count + ' file' + (count > 1 ? 's' : '') + ' selected';
            badge.style.display = 'block';
            if (count > 10) {
                text.textContent = '⚠ Too many files — please select up to 10 at a time';
                text.closest('span').style.background = '#dc2626';
            }
        } else {
            badge.style.display = 'none';
        }
    });

    // Auto open batch tab if redirected back with ?tab=batch
    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'batch'): ?>
    showTab('batch');
    <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'history'): ?>
    showTab('history');
    <?php endif; ?>
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>
</html>
