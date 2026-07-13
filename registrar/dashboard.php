<?php
session_start();
$required_role = 'Registrar';
require '../processes/auth_check.php';

// Pick up batch results if redirected back from processor
$batchResults = null;
if (isset($_GET['tab']) && $_GET['tab'] === 'batch' && !empty($_SESSION['batch_upload_results'])) {
    $batchResults = $_SESSION['batch_upload_results'];
    unset($_SESSION['batch_upload_results']);
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
        <title>Registrar Dashboard — CertsVerify</title>
        <style>
            .session-indicator {
                display: inline-flex; align-items: center; gap: 7px;
                border-radius: 20px; padding: 5px 12px 5px 8px;
                font-size: 12px; font-weight: 600; margin-right: 12px;
                white-space: nowrap; border: 1px solid;
            }
            .session-dot { position: relative; width: 8px; height: 8px; flex-shrink: 0; }
            .session-dot::before { content:''; position:absolute; inset:0; border-radius:50%; }
            .session-dot::after  { content:''; position:absolute; inset:-3px; border-radius:50%; opacity:0.4; animation:sessionPulse 2s ease-in-out infinite; }
            @keyframes sessionPulse { 0%,100%{transform:scale(1);opacity:0.4;} 50%{transform:scale(1.8);opacity:0;} }
            .role-registrar { background:#f0fdf4; border-color:#86efac; color:#166534; }
            .role-registrar .session-dot::before,
            .role-registrar .session-dot::after { background:#22c55e; }
        </style>
    </head>
    <body>

        
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-4">
                    <a href="../index.php" class="navbar-brand">Certs<span>Verify</span></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNavDropdown">
                        <ul class="navbar-nav me-auto"></ul>
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <div class="session-indicator role-registrar">
                                    <div class="session-dot"></div>
                                    Logged in as <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
                                    &nbsp;·&nbsp;
                                    <span style="opacity:0.75;font-weight:500;">Registrar</span>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                                    <div class="d-flex flex-row user-info">
                                        <div class="nav-avatar" style="margin-right:10px;"><?php echo strtoupper(substr($_SESSION['first_name'],0,1)); ?></div>
                                        <div class="d-flex flex-column justify-content-start ml-2">
                                            <span class="d-block font-weight-bold name"><?php echo htmlentities($_SESSION['first_name'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlentities($_SESSION['last_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="date text-black-50"><?php echo htmlentities($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </a>
                                <ul class="dropdown-menu">
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

        <div class="container-fluid px-4">
            <div class="col-md-12">
                <div class="py-4" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <h4 style="font-weight:800;color:var(--navy);margin:0;">Registrar Dashboard</h4>
                    <button type="button" id="addCertBtn"
                            class="btn btn-primary btn-sm"
                            style="width:auto;font-size:12px;padding:5px 14px;font-weight:600;"
                            data-bs-toggle="modal" data-bs-target="#addCertModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Certificate
                    </button>
                </div>

                <!-- Tab nav -->
                <div style="display:flex;gap:2px;border-bottom:2px solid #e5e7eb;margin-bottom:20px;">
                    <button id="tab-btn-certs" onclick="showRegTab('certs')"
                            style="padding:9px 20px;font-size:13px;font-weight:600;border:none;background:none;
                                   cursor:pointer;border-bottom:3px solid transparent;color:var(--muted);
                                   margin-bottom:-2px;transition:all .15s;">
                        <i class="bi bi-file-earmark-check me-1"></i>Certificates
                    </button>
                    <button id="tab-btn-batch" onclick="showRegTab('batch')"
                            style="padding:9px 20px;font-size:13px;font-weight:600;border:none;background:none;
                                   cursor:pointer;border-bottom:3px solid transparent;color:var(--muted);
                                   margin-bottom:-2px;transition:all .15s;">
                        <i class="bi bi-upload me-1"></i>Batch Upload
                    </button>
                </div>

                <!-- ══ CERTIFICATES PANEL ══ -->
                <div id="panel-certs">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Names</th>
                            <th>Admission Number</th>
                            <th>Course Name</th>
                            <th>University Name</th>
                            <th>Grade</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Uploaded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $mysqli = require_once "../processes/db.php";

                            // ── Ensure grade & uploadedAt columns exist on certificate table ──
                            mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS grade VARCHAR(50) DEFAULT NULL");
                            mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS uploadedAt DATETIME DEFAULT NULL");

                            // ── Allow NULL for date fields so batch rows show — instead of 0000-00-00 ──
                            mysqli_query($mysqli, "ALTER TABLE certificate MODIFY COLUMN startDate DATE NULL DEFAULT NULL");
                            mysqli_query($mysqli, "ALTER TABLE certificate MODIFY COLUMN endDate DATE NULL DEFAULT NULL");

                            // ── One-time backfill: give existing rows a synthetic uploadedAt ──
                            // (their real upload time was never recorded), staggered by id so
                            // older certificates appear to have been uploaded earlier.
                            mysqli_query($mysqli, "
                                UPDATE certificate
                                SET uploadedAt = DATE_SUB(NOW(), INTERVAL id MINUTE)
                                WHERE uploadedAt IS NULL
                            ");

                            // ── Get logged-in registrar's university ──
                            // All registrars from the same university share one certificate pool.
                            // A Strathmore registrar sees ALL Strathmore certificates regardless
                            // of which Strathmore registrar uploaded them.
                            $registrar_id = $_SESSION['user_id'];
                            $reg_data     = mysqli_fetch_assoc(mysqli_query($mysqli,
                                "SELECT universityName FROM registrar WHERE id='$registrar_id' LIMIT 1"
                            ));
                            $my_university = mysqli_real_escape_string($mysqli, $reg_data['universityName'] ?? '');

                            // Fetch all certificates for this university, joined with uploader name
                            $sql    = "SELECT c.*, r.firstname as reg_first, r.lastname as reg_last
                                       FROM certificate c
                                       LEFT JOIN registrar r ON c.registrar_id = r.id
                                       WHERE c.universityName = '$my_university'
                                       ORDER BY c.id DESC";
                            $result = mysqli_query($mysqli, $sql);
                            $i = 1;
                            while ($row = mysqli_fetch_array($result)):
                        ?>
                        <?php
                            $isIncomplete = empty($row['studentNames']) || empty($row['admissionNumber']) || empty($row['courseName']);
                            $trStyle = $isIncomplete ? 'background:#fffbeb;' : '';
                        ?>
                        <tr style="<?php echo $trStyle; ?>">
                            <td><?php echo $i; ?></td>
                            <td>
                                <?php if (empty($row['studentNames'])): ?>
                                    <span style="color:#d97706;font-size:11px;font-weight:600;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Not set
                                    </span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($row['studentNames']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo !empty($row['admissionNumber']) ? htmlspecialchars($row['admissionNumber']) : '<span style="color:var(--muted);">—</span>'; ?>
                            </td>
                            <td>
                                <?php echo !empty($row['courseName']) ? htmlspecialchars($row['courseName']) : '<span style="color:var(--muted);">—</span>'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['universityName']); ?></td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($row['grade'] ?: '—'); ?></td>
                            <td><?php echo !empty($row['startDate']) && $row['startDate'] !== '0000-00-00' ? date('d M Y', strtotime($row['startDate'])) : '<span style="color:var(--muted);">—</span>'; ?></td>
                            <td><?php echo !empty($row['endDate'])   && $row['endDate']   !== '0000-00-00' ? date('d M Y', strtotime($row['endDate']))   : '<span style="color:var(--muted);">—</span>'; ?></td>
                            <td style="font-size:12px;color:var(--muted);">
                                <?php echo htmlspecialchars(trim($row['reg_first'].' '.$row['reg_last'])); ?>
                                <?php if ((int)$row['registrar_id'] === (int)$registrar_id): ?>
                                <br><span style="background:#eff6ff;color:#2563eb;font-size:10px;font-weight:700;padding:1px 6px;border-radius:6px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Everyone can VIEW any cert from their university -->
                                <a class="text-success" href="#" style="margin-right:10px;"
                                   data-bs-toggle="modal"
                                   data-bs-target="#viewModal<?php echo $row['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ((int)$row['registrar_id'] === (int)$registrar_id): ?>
                                <!-- Only the uploader can EDIT or DELETE their own certs -->
                                <a class="text-warning" href="#" style="margin-right:10px;"
                                   data-bs-toggle="modal"
                                   data-bs-target="#editModal<?php echo $row['id']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a class="text-danger" href="#" style="margin-right:10px;"
                                   data-bs-toggle="modal"
                                   data-bs-target="#deleteModal<?php echo $row['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php else: ?>
                                <!-- Other registrars from same uni can only view -->
                                <span style="font-size:10px;color:var(--hint);" title="Uploaded by another registrar from your university">
                                    <i class="bi bi-lock" style="color:#9ca3af;"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- VIEW MODAL — unique ID per row -->
                        <div id="viewModal<?php echo $row['id']; ?>" class="modal fade" tabindex="-1"
                             data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">View Certificate Info</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control"
                                                           value="<?php echo htmlspecialchars($row['studentNames']); ?>" readonly>
                                                    <label>Student Names</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control"
                                                           value="<?php echo htmlspecialchars($row['admissionNumber']); ?>" readonly>
                                                    <label>Admission Number</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control"
                                                   value="<?php echo htmlspecialchars($row['courseName']); ?>" readonly>
                                            <label>Course</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control"
                                                   value="<?php echo htmlspecialchars($row['universityName']); ?>" readonly>
                                            <label>University Name</label>
                                        </div>
                                        <?php if (!empty($row['grade'])): ?>
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control"
                                                   value="<?php echo htmlspecialchars($row['grade']); ?>" readonly>
                                            <label>Classification</label>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['uploadedAt'])): ?>
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control"
                                                   value="<?php echo date('d M Y, H:i', strtotime($row['uploadedAt'])); ?>" readonly>
                                            <label>Uploaded At</label>
                                        </div>
                                        <?php endif; ?>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control"
                                                           value="<?php echo (!empty($row['startDate']) && $row['startDate'] !== '0000-00-00') ? date('d M Y', strtotime($row['startDate'])) : '—'; ?>" readonly>
                                                    <label>Start Date</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control"
                                                           value="<?php echo (!empty($row['endDate']) && $row['endDate'] !== '0000-00-00') ? date('d M Y', strtotime($row['endDate'])) : '—'; ?>" readonly>
                                                    <label>End Date</label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($row['certID'])): ?>
                                        <div class="mb-3 p-2" style="background:#eff6ff;border-radius:8px;text-align:center;">
                                            <small style="color:#6b7280;font-size:11px;">CERTIFICATE ID</small><br>
                                            <strong style="font-family:monospace;color:#2563eb;font-size:14px;">
                                                <?php echo htmlspecialchars($row['certID']); ?>
                                            </strong>
                                        </div>
                                        <?php endif; ?>
                                        <div class="text-center py-2">
                                            <i class="bi bi-file-earmark-pdf" style="font-size:80px;color:#059669;"></i><br>
                                            <a href="http://localhost/CertsVerify/serve_cert.php?id=<?php echo htmlspecialchars($row['certID']); ?>"
                                               target="_blank" class="btn btn-primary mt-2">
                                                <i class="bi bi-eye me-1"></i>View Certificate
                                            </a>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EDIT MODAL — unique ID per row -->
                        <div id="editModal<?php echo $row['id']; ?>" class="modal fade" tabindex="-1"
                             data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Certificate Info</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" enctype="multipart/form-data"
                                              action="../processes/registrar_dashboard_cert_processes.php">
                                            <input type="hidden" name="method" value="Update">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($row['fileName']); ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="student_names" class="form-control"
                                                               placeholder="Student Names"
                                                               value="<?php echo htmlspecialchars($row['studentNames']); ?>" required>
                                                        <label>Student Names</label>
                                                        <small class="text-danger" style="font-size:10px;">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="admission_number" class="form-control"
                                                               placeholder="Admission Number"
                                                               value="<?php echo htmlspecialchars($row['admissionNumber']); ?>" required>
                                                        <label>Admission Number</label>
                                                        <small class="text-danger" style="font-size:10px;">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <!-- University is locked to the registrar's own university -->
                                                        <input type="text" class="form-control"
                                                               value="<?php echo htmlspecialchars($row['universityName']); ?>"
                                                               readonly
                                                               style="background:#f0f9ff;color:var(--navy);font-weight:600;cursor:not-allowed;">
                                                        <label>University</label>
                                                        <input type="hidden" name="university_name" value="<?php echo htmlspecialchars($row['universityName']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <select class="form-select" name="course_name" required>
                                                            <option selected disabled>Choose Course</option>
                                                            <?php
                                                            $courses = ["Bachelor of Commerce","Bachelor of Tourism Management",
                                                                "Bachelor of Science in Hospitality and Hotel Management",
                                                                "Bachelor of Business Science: Financial Economics",
                                                                "Bachelor of Business Science: Actuarial Science",
                                                                "Bachelor of Science in Informatics and Computer Science",
                                                                "Bachelor of Business Information Technology",
                                                                "Bachelor of Science in Computer Networks and Cyber Security",
                                                                "Bachelor of Laws","Bachelor of Medicine and Surgery",
                                                                "Bachelor of Science in Nursing","Bachelor of Education",
                                                                "Bachelor of Science in Civil Engineering",
                                                                "Bachelor of Science in Electrical Engineering",
                                                                "Bachelor of Science in Mechanical Engineering",
                                                                "Bachelor of Arts in Communication",
                                                                "Bachelor of Science in Environmental Science",
                                                                "Bachelor of Science in Agriculture","Bachelor of Pharmacy",
                                                                "Bachelor of Science in Mathematics","Bachelor of Science in Statistics",
                                                                "Bachelor of Arts in Economics","Bachelor of Science in Architecture",
                                                                "Bachelor of Science in Food Science and Technology"];
                                                            foreach ($courses as $c):
                                                            ?>
                                                            <option value="<?php echo $c; ?>"
                                                                <?php echo $row['courseName']==$c ? 'selected' : ''; ?>>
                                                                <?php echo $c; ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label>Course</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <select class="form-select" name="grade" required>
                                                            <option value="" disabled <?php echo empty($row['grade']) ? 'selected' : ''; ?>>Select Grade / Classification</option>
                                                            <?php
                                                            $grades = ["First Class Honours","Second Class Honours (Upper Division)",
                                                                "Second Class Honours (Lower Division)","Pass","Distinction",
                                                                "Credit","Merit"];
                                                            foreach ($grades as $g):
                                                            ?>
                                                            <option value="<?php echo $g; ?>"
                                                                <?php echo (isset($row['grade']) && $row['grade']==$g) ? 'selected' : ''; ?>>
                                                                <?php echo $g; ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label>Grade / Classification</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="date" name="start_date" class="form-control"
                                                               value="<?php echo $row['startDate']; ?>" required>
                                                        <label>Start Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="date" name="end_date" class="form-control"
                                                               value="<?php echo $row['endDate']; ?>" required>
                                                        <label>End Date</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Replace certificate PDF (optional)</label>
                                                <input type="file" name="file_upload" class="form-control" accept=".pdf">
                                                <small class="text-muted" style="font-size:11px;">Leave empty to keep the existing PDF</small>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DELETE MODAL — unique ID per row -->
                        <div id="deleteModal<?php echo $row['id']; ?>" class="modal fade" tabindex="-1"
                             data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Certificate</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-danger" style="font-size:13px;">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Are you sure you want to delete the certificate for
                                            <strong><?php echo htmlspecialchars($row['studentNames']); ?></strong>?
                                            This action <strong>cannot be undone</strong>.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST" action="../processes/registrar_dashboard_cert_processes.php">
                                            <input type="hidden" name="method" value="Delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($row['fileName']); ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php $i++; endwhile; ?>
                    </tbody>
                </table>
                </div><!-- end panel-certs -->

                <!-- ══ BATCH UPLOAD PANEL ══ -->
                <div id="panel-batch" style="display:none;">

                    <div class="card p-4 mb-4" style="border-radius:14px;border:1px solid #e5e7eb;">
                        <div class="text-center mb-4">
                            <div style="width:52px;height:52px;background:var(--blue-light);border-radius:12px;
                                        display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <i class="bi bi-upload" style="font-size:24px;color:var(--blue);"></i>
                            </div>
                            <h6 style="font-weight:700;color:var(--navy);margin-bottom:4px;">Batch Certificate Upload</h6>
                            <p style="font-size:12px;color:var(--muted);margin:0;">
                                Select up to 20 PDF certificates at once. They are saved immediately —
                                use the <strong>Edit</strong> button on each row in the Certificates tab to fill in student details afterwards.
                            </p>
                        </div>

                        <form method="POST" action="../processes/registrar_batch_upload.php"
                              enctype="multipart/form-data" id="batchUploadForm">

                            <label style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;
                                          letter-spacing:.4px;display:block;margin-bottom:6px;">
                                <i class="bi bi-filetype-pdf me-1" style="color:#dc2626;"></i>
                                Select PDF Files (max 20)
                            </label>
                            <input type="file" name="cert_files[]" id="batchFileInput"
                                   class="form-control mb-1" accept=".pdf,application/pdf" multiple required>
                            <small style="font-size:11px;color:var(--muted);display:block;margin-bottom:14px;">
                                Hold <strong>Ctrl</strong> (or <strong>Cmd</strong> on Mac) to select multiple files at once · Max 5MB each
                            </small>

                            <!-- Live file list shown after picking -->
                            <div id="batchFileList" style="display:none;margin-bottom:16px;"></div>
                            <div id="batchFileError" style="display:none;color:#dc2626;font-size:12px;
                                                            font-weight:600;margin-bottom:10px;"></div>

                            <button type="submit" class="btn btn-primary" id="batchSubmitBtn" style="font-weight:600;">
                                <i class="bi bi-upload me-2"></i>Upload All
                            </button>
                        </form>
                    </div>

                    <!-- Results after redirect -->
                    <?php if ($batchResults): ?>
                    <?php
                        $ok      = count(array_filter($batchResults, fn($r) => $r['status'] === 'OK'));
                        $errors  = count(array_filter($batchResults, fn($r) => $r['status'] === 'ERROR'));
                        $total   = count($batchResults);
                    ?>
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 20px;text-align:center;min-width:80px;">
                            <div style="font-size:24px;font-weight:800;color:#059669;"><?php echo $ok; ?></div>
                            <div style="font-size:11px;color:#065f46;font-weight:600;">Uploaded</div>
                        </div>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 20px;text-align:center;min-width:80px;">
                            <div style="font-size:24px;font-weight:800;color:#dc2626;"><?php echo $errors; ?></div>
                            <div style="font-size:11px;color:#991b1b;font-weight:600;">Errors</div>
                        </div>
                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 20px;text-align:center;min-width:80px;">
                            <div style="font-size:24px;font-weight:800;color:#0369a1;"><?php echo $total; ?></div>
                            <div style="font-size:11px;color:#0c4a6e;font-weight:600;">Total</div>
                        </div>
                    </div>
                    <?php if ($ok > 0): ?>
                    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 16px;
                                font-size:12px;color:#166534;font-weight:600;margin-bottom:16px;">
                        <i class="bi bi-info-circle me-1"></i>
                        <?php echo $ok; ?> file<?php echo $ok!==1?'s were':' was'; ?> uploaded successfully.
                        Go to the <strong>Certificates</strong> tab and use the <strong>Edit</strong> button on each new row to fill in student details.
                    </div>
                    <?php endif; ?>
                    <div class="card mb-4" style="border-radius:12px;overflow:hidden;">
                        <div class="card-body p-0">
                            <table class="table mb-0" style="font-size:13px;">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>File</th>
                                        <th>Cert ID Assigned</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($batchResults as $r): ?>
                                <tr style="<?php echo $r['status']==='OK' ? 'background:#f0fdf4;' : 'background:#fef2f2;'; ?>">
                                    <td style="color:var(--muted);"><?php echo $r['index']; ?></td>
                                    <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                        title="<?php echo htmlspecialchars($r['file']); ?>">
                                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                        <?php echo htmlspecialchars($r['file']); ?>
                                    </td>
                                    <td>
                                        <?php if ($r['cert_id'] !== '—'): ?>
                                            <code style="font-size:12px;background:#e0e7ff;color:#3730a3;padding:2px 7px;border-radius:4px;">
                                                <?php echo htmlspecialchars($r['cert_id']); ?>
                                            </code>
                                        <?php else: ?>
                                            <span style="color:var(--muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status']==='OK'): ?>
                                            <span class="badge" style="background:#dcfce7;color:#166534;font-size:11px;">
                                                <i class="bi bi-check-circle-fill me-1"></i>Uploaded
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:11px;">
                                                <i class="bi bi-x-circle-fill me-1"></i>Error
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($r['message']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- end panel-batch -->

            </div>
        </div>

        <!-- ✅ ADD CERTIFICATE MODAL — outside the loop entirely -->
        <!-- This is the key fix. The modal ID is unique and not inside the while loop -->
        <!-- so clicking Cancel cannot accidentally trigger another row's modal -->
        <div id="addCertModal" class="modal fade" tabindex="-1"
             data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-file-earmark-plus me-2" style="color:var(--blue);"></i>
                            Add New Certificate
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- id="addCertForm" so JS can reset it on cancel -->
                        <form id="addCertForm" method="POST" enctype="multipart/form-data"
                              action="../processes/registrar_dashboard_cert_processes.php">
                            <input type="hidden" name="method" value="Create">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="student_names" class="form-control"
                                               placeholder="Student Names" required>
                                        <label>Student Names</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="admission_number" class="form-control"
                                               placeholder="e.g. UON/CS/2020/001" required>
                                        <label>Admission Number</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <!-- University is locked to the logged-in registrar's own university -->
                                        <input type="text" class="form-control"
                                               value="<?php echo htmlspecialchars($my_university); ?>"
                                               readonly
                                               style="background:#f0f9ff;color:var(--navy);font-weight:600;cursor:not-allowed;">
                                        <label>University</label>
                                        <input type="hidden" name="university_name" value="<?php echo htmlspecialchars($my_university); ?>">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" name="course_name" required>
                                            <option selected disabled value="">Choose Course</option>
                                            <option value="Bachelor of Commerce">Bachelor of Commerce</option>
                                            <option value="Bachelor of Tourism Management">Bachelor of Tourism Management</option>
                                            <option value="Bachelor of Science in Hospitality and Hotel Management">Bachelor of Science in Hospitality and Hotel Management</option>
                                            <option value="Bachelor of Business Science: Financial Economics">Bachelor of Business Science: Financial Economics</option>
                                            <option value="Bachelor of Business Science: Actuarial Science">Bachelor of Business Science: Actuarial Science</option>
                                            <option value="Bachelor of Science in Informatics and Computer Science">Bachelor of Science in Informatics and Computer Science</option>
                                            <option value="Bachelor of Business Information Technology">Bachelor of Business Information Technology</option>
                                            <option value="Bachelor of Science in Computer Networks and Cyber Security">Bachelor of Science in Computer Networks and Cyber Security</option>
                                            <option value="Bachelor of Laws">Bachelor of Laws</option>
                                            <option value="Bachelor of Medicine and Surgery">Bachelor of Medicine and Surgery</option>
                                            <option value="Bachelor of Science in Nursing">Bachelor of Science in Nursing</option>
                                            <option value="Bachelor of Education">Bachelor of Education</option>
                                            <option value="Bachelor of Science in Civil Engineering">Bachelor of Science in Civil Engineering</option>
                                            <option value="Bachelor of Science in Electrical Engineering">Bachelor of Science in Electrical Engineering</option>
                                            <option value="Bachelor of Science in Mechanical Engineering">Bachelor of Science in Mechanical Engineering</option>
                                            <option value="Bachelor of Arts in Communication">Bachelor of Arts in Communication</option>
                                            <option value="Bachelor of Science in Environmental Science">Bachelor of Science in Environmental Science</option>
                                            <option value="Bachelor of Science in Agriculture">Bachelor of Science in Agriculture</option>
                                            <option value="Bachelor of Pharmacy">Bachelor of Pharmacy</option>
                                            <option value="Bachelor of Science in Mathematics">Bachelor of Science in Mathematics</option>
                                            <option value="Bachelor of Science in Statistics">Bachelor of Science in Statistics</option>
                                            <option value="Bachelor of Arts in Economics">Bachelor of Arts in Economics</option>
                                            <option value="Bachelor of Science in Architecture">Bachelor of Science in Architecture</option>
                                            <option value="Bachelor of Science in Food Science and Technology">Bachelor of Science in Food Science and Technology</option>
                                        </select>
                                        <label>Course</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" name="grade" required>
                                            <option selected disabled value="">Select Grade / Classification</option>
                                            <option value="First Class Honours">First Class Honours</option>
                                            <option value="Second Class Honours (Upper Division)">Second Class Honours (Upper Division)</option>
                                            <option value="Second Class Honours (Lower Division)">Second Class Honours (Lower Division)</option>
                                            <option value="Pass">Pass</option>
                                            <option value="Distinction">Distinction</option>
                                            <option value="Credit">Credit</option>
                                            <option value="Merit">Merit</option>
                                        </select>
                                        <label>Grade / Classification</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" name="start_date" class="form-control" required>
                                        <label>Start Date</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" name="end_date" class="form-control" required>
                                        <label>End Date</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">
                                    Upload Certificate PDF
                                </label>
                                <input type="file" name="file" class="form-control" accept=".pdf" required>
                                <small class="text-muted" style="font-size:11px;">PDF only · Max 5MB</small>
                            </div>
                            <div class="modal-footer">
                                <!-- Cancel button resets the form AND closes modal -->
                                <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal"
                                        onclick="document.getElementById('addCertForm').reset();">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i>Upload Certificate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
        <script>
        // ── Tab switching ─────────────────────────────────────────────────────
        function showRegTab(tab) {
            document.getElementById('panel-certs').style.display = tab === 'certs' ? 'block' : 'none';
            document.getElementById('panel-batch').style.display = tab === 'batch' ? 'block' : 'none';
            document.getElementById('addCertBtn').style.display  = tab === 'certs' ? ''      : 'none';
            ['certs','batch'].forEach(function(t) {
                var btn = document.getElementById('tab-btn-' + t);
                btn.style.color        = t === tab ? 'var(--blue)'            : 'var(--muted)';
                btn.style.borderBottom = t === tab ? '3px solid var(--blue)'  : '3px solid transparent';
            });
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
        (function() {
            var p = new URLSearchParams(window.location.search);
            showRegTab(p.get('tab') === 'batch' ? 'batch' : 'certs');
        })();

        // ── Reset Add Certificate modal on close ──────────────────────────────
        document.getElementById('addCertModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addCertForm').reset();
        });

        // ── Batch: show file list preview + validate ──────────────────────────
        document.getElementById('batchFileInput').addEventListener('change', function() {
            var errEl  = document.getElementById('batchFileError');
            var listEl = document.getElementById('batchFileList');
            var files  = Array.from(this.files);
            errEl.style.display  = 'none';
            listEl.style.display = 'none';
            listEl.innerHTML     = '';

            if (files.length === 0) return;

            if (files.length > 20) {
                errEl.textContent   = 'Please select a maximum of 20 PDF files at once.';
                errEl.style.display = 'block';
                this.value = '';
                return;
            }

            var nonPdf = files.filter(function(f) {
                return !f.name.toLowerCase().endsWith('.pdf');
            });
            if (nonPdf.length > 0) {
                errEl.textContent   = 'All files must be PDFs: ' + nonPdf.map(function(f){return f.name;}).join(', ');
                errEl.style.display = 'block';
                this.value = '';
                return;
            }

            // Show a tidy list of selected files
            var html = '<div style="background:#f8faff;border:1px solid #e0e7ff;border-radius:10px;padding:10px 14px;margin-bottom:4px;">';
            html += '<div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">'
                  + files.length + ' file' + (files.length !== 1 ? 's' : '') + ' selected</div>';
            files.forEach(function(f, i) {
                var kb = (f.size / 1024).toFixed(0);
                html += '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;'
                      + (i < files.length-1 ? 'border-bottom:1px solid #e5e7eb;' : '') + '">'
                      + '<i class="bi bi-file-earmark-pdf" style="color:#dc2626;flex-shrink:0;"></i>'
                      + '<span style="font-size:12px;font-weight:600;color:var(--text);flex:1;word-break:break-all;">' + f.name + '</span>'
                      + '<span style="font-size:11px;color:var(--muted);white-space:nowrap;">' + kb + ' KB</span>'
                      + '</div>';
            });
            html += '</div>';
            listEl.innerHTML     = html;
            listEl.style.display = 'block';
        });

        // ── Disable submit button while uploading ─────────────────────────────
        document.getElementById('batchUploadForm').addEventListener('submit', function() {
            var btn = document.getElementById('batchSubmitBtn');
            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Uploading…';
        });
        </script>
    </body>
</html>
