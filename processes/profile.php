<?php
/*
 * ============================================================
 * profile.php — Account Settings for all roles
 * ============================================================
 * Accessible from the navbar dropdown of any dashboard.
 * Works for: Graduate, Employer, Registrar, SysAdmin
 * ============================================================
 */
session_start();

// Must be logged in as some role
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

$mysqli   = require '../processes/db.php';
$role     = $_SESSION['role'];
$user_id  = (int)$_SESSION['user_id'];

// ── Role → DB table + column config ──
$tableMap = [
    'Graduate'  => ['table'=>'graduate',  'name_col'=>'firstname,lastname', 'extra_cols'=>['universityName','admissionNumber']],
    'Employer'  => ['table'=>'employer',  'name_col'=>'firstname,lastname', 'extra_cols'=>['businessName']],
    'Registrar' => ['table'=>'registrar', 'name_col'=>'firstname,lastname', 'extra_cols'=>['universityName']],
    'SysAdmin'  => ['table'=>'sysadmin',  'name_col'=>'firstname,lastname', 'extra_cols'=>[]],
];
$cfg = $tableMap[$role] ?? null;
if (!$cfg) { header('Location: ../index.php'); exit; }

$table    = $cfg['table'];
$dashLink = [
    'Graduate'  => '../graduate/dashboard.php',
    'Employer'  => '../employer/dashboard.php',
    'Registrar' => '../registrar/dashboard.php',
    'SysAdmin'  => '../admin/dashboard.php',
][$role];

// ── Fetch current user data ──
$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM $table WHERE id=$user_id LIMIT 1"));
if (!$user) { header("Location: $dashLink"); exit; }

$msg      = '';
$msgType  = '';

// ── Handle form submissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Update profile info ──
    if ($action === 'update_profile') {
        $firstname   = trim(mysqli_real_escape_string($mysqli, $_POST['firstname']   ?? ''));
        $lastname    = trim(mysqli_real_escape_string($mysqli, $_POST['lastname']    ?? ''));
        $phone       = trim(mysqli_real_escape_string($mysqli, $_POST['phoneNumber'] ?? ''));

        if (empty($firstname) || empty($lastname)) {
            $msg = 'First name and last name are required.'; $msgType = 'error';
        } else {
            $set = "firstname='$firstname', lastname='$lastname', phoneNumber='$phone'";

            // Role-specific extra fields
            if ($role === 'Employer') {
                $biz = trim(mysqli_real_escape_string($mysqli, $_POST['businessName'] ?? ''));
                $set .= ", businessName='$biz'";
            }

            mysqli_query($mysqli, "UPDATE $table SET $set WHERE id=$user_id");

            // Update session so navbar reflects new name immediately
            $_SESSION['first_name']  = $firstname;
            $_SESSION['last_name']   = $lastname;

            // Also update the namespaced session
            if (isset($_SESSION['sessions'][$role])) {
                $_SESSION['sessions'][$role]['first_name'] = $firstname;
                $_SESSION['sessions'][$role]['last_name']  = $lastname;
            }

            $msg = 'Profile updated successfully.'; $msgType = 'success';
            // Refresh user data
            $user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM $table WHERE id=$user_id LIMIT 1"));
        }
    }

    // ── Change password ──
    if ($action === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new_pw   = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (!password_verify($current, $user['password'])) {
            $msg = 'Current password is incorrect.'; $msgType = 'error';
        } elseif (strlen($new_pw) < 8) {
            $msg = 'New password must be at least 8 characters.'; $msgType = 'error';
        } elseif ($new_pw !== $confirm) {
            $msg = 'New passwords do not match.'; $msgType = 'error';
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            mysqli_query($mysqli, "UPDATE $table SET password='$hash' WHERE id=$user_id");
            $msg = 'Password changed successfully.'; $msgType = 'success';
        }
    }
}

$roleLabels = [
    'Graduate'  => 'Graduate',
    'Employer'  => 'Employer',
    'Registrar' => 'University Registrar',
    'SysAdmin'  => 'System Administrator',
];
$roleLabel = $roleLabels[$role] ?? $role;
$initial   = strtoupper(substr($user['firstname'], 0, 1));
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
    <title>Account Settings — CertsVerify</title>
    <style>
        body { background: #f0f4ff; }

        .profile-wrap {
            max-width: 760px;
            margin: 40px auto;
            padding: 0 16px 60px;
        }

        /* ── Avatar card ── */
        .profile-avatar-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            color: #fff;
            margin-bottom: 24px;
        }
        .avatar-circle {
            width: 80px; height: 80px;
            background: rgba(255,255,255,.2);
            border: 3px solid rgba(255,255,255,.5);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; font-weight: 800;
            margin: 0 auto 14px;
        }
        .profile-avatar-card h4 { font-weight: 800; margin-bottom: 4px; }
        .profile-avatar-card .role-badge {
            display: inline-block;
            background: rgba(255,255,255,.2);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── Settings cards ── */
        .settings-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .settings-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-card-header .header-icon {
            width: 36px; height: 36px;
            background: #eff6ff;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #2563eb;
        }
        .settings-card-header h6 { font-weight: 700; color: #1e3a5f; margin: 0; }
        .settings-card-header p  { font-size: 12px; color: #6b7280; margin: 0; }
        .settings-card-body { padding: 24px; }

        /* ── Form controls ── */
        .settings-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
        .settings-input {
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            font-size: 14px;
            padding: 10px 14px;
            width: 100%;
            transition: border-color .2s;
        }
        .settings-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        .settings-input[readonly] {
            background: #f9fafb;
            color: #6b7280;
            cursor: default;
        }
        .btn-save {
            background: #2563eb; color: #fff;
            font-weight: 700; border: none;
            border-radius: 10px; padding: 10px 28px;
            font-size: 14px; cursor: pointer;
            transition: background .15s;
        }
        .btn-save:hover { background: #1d4ed8; }
        .btn-danger-outline {
            background: #fff; color: #dc2626;
            font-weight: 700; border: 1.5px solid #fecaca;
            border-radius: 10px; padding: 10px 28px;
            font-size: 14px; cursor: pointer;
        }
        .btn-danger-outline:hover { background: #fef2f2; }

        /* ── Alert messages ── */
        .profile-alert {
            border-radius: 10px; padding: 12px 18px;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px;
        }
        .profile-alert.success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
        .profile-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        /* ── pw eye toggle ── */
        .pw-wrap { position: relative; }
        .pw-wrap .settings-input { padding-right: 42px; }
        .pw-eye {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: 16px; padding: 0;
        }
        .pw-eye:hover { color: #2563eb; }

        /* Navbar */
        .cv-navbar { position: sticky; top: 0; z-index: 100; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="cv-navbar">
        <a href="<?php echo $dashLink; ?>" class="brand">
            <div class="brand-icon"><i class="bi bi-file-earmark-check"></i></div>
            <span>Certs<span>Verify</span></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:12px;color:var(--muted);">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                · <?php echo $roleLabel; ?>
            </span>
            <a href="<?php echo $dashLink; ?>"
               style="font-size:12px;font-weight:600;color:var(--blue);text-decoration:none;padding:6px 12px;background:#eff6ff;border-radius:8px;">
                <i class="bi bi-arrow-left me-1"></i>Back to dashboard
            </a>
        </div>
    </nav>

    <div class="profile-wrap">

        <!-- Alert -->
        <?php if ($msg): ?>
        <div class="profile-alert <?php echo $msgType; ?>">
            <i class="bi bi-<?php echo $msgType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- Avatar card -->
        <div class="profile-avatar-card">
            <div class="avatar-circle"><?php echo $initial; ?></div>
            <h4><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h4>
            <span class="role-badge"><i class="bi bi-shield-check me-1"></i><?php echo $roleLabel; ?></span>
        </div>

        <!-- ── Profile information ── -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="header-icon"><i class="bi bi-person"></i></div>
                <div>
                    <h6>Profile Information</h6>
                    <p>Update your name, phone number<?php echo $role === 'Employer' ? ', and business name' : ''; ?>.</p>
                </div>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="settings-label">First Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="firstname" class="settings-input"
                                   value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="settings-label">Last Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="lastname" class="settings-input"
                                   value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="settings-label">Phone Number</label>
                            <input type="text" name="phoneNumber" class="settings-input"
                                   placeholder="e.g. 0712345678"
                                   value="<?php echo htmlspecialchars($user['phoneNumber'] ?? ''); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="settings-label">Email Address</label>
                            <input type="email" class="settings-input" readonly
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   title="Email cannot be changed here">
                            <small style="font-size:11px;color:#9ca3af;margin-top:4px;display:block;">
                                <i class="bi bi-lock me-1"></i>Email cannot be changed
                            </small>
                        </div>

                        <?php if ($role === 'Employer'): ?>
                        <div class="col-12">
                            <label class="settings-label">Business / Organisation Name</label>
                            <input type="text" name="businessName" class="settings-input"
                                   value="<?php echo htmlspecialchars($user['businessName'] ?? ''); ?>">
                        </div>
                        <?php endif; ?>

                        <?php if ($role === 'Registrar' || $role === 'Graduate'): ?>
                        <div class="col-12">
                            <label class="settings-label">
                                <?php echo $role === 'Graduate' ? 'University' : 'University (assigned)'; ?>
                            </label>
                            <input type="text" class="settings-input" readonly
                                   value="<?php echo htmlspecialchars($user['universityName'] ?? ''); ?>">
                            <small style="font-size:11px;color:#9ca3af;margin-top:4px;display:block;">
                                <i class="bi bi-lock me-1"></i>Cannot be changed here
                            </small>
                        </div>
                        <?php endif; ?>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-check-lg me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Account info (read-only) ── -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="header-icon"><i class="bi bi-info-circle"></i></div>
                <div>
                    <h6>Account Details</h6>
                    <p>Your account information.</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="settings-label">Account Type</label>
                        <input type="text" class="settings-input" readonly value="<?php echo $roleLabel; ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="settings-label">Email Address</label>
                        <input type="text" class="settings-input" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <?php if (!empty($user['admissionNumber'])): ?>
                    <div class="col-sm-6">
                        <label class="settings-label">Admission Number</label>
                        <input type="text" class="settings-input" readonly value="<?php echo htmlspecialchars($user['admissionNumber']); ?>">
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['status'])): ?>
                    <div class="col-sm-6">
                        <label class="settings-label">Account Status</label>
                        <?php $s = $user['status'] ?? 'approved'; ?>
                        <input type="text" class="settings-input" readonly
                               value="<?php echo ucfirst($s); ?>"
                               style="color:<?php echo $s==='approved'?'#059669':($s==='pending'?'#d97706':'#dc2626'); ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Change password ── -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="header-icon" style="background:#fef2f2;"><i class="bi bi-lock" style="color:#dc2626;"></i></div>
                <div>
                    <h6>Change Password</h6>
                    <p>Use a strong password of at least 8 characters.</p>
                </div>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label class="settings-label">Current Password <span style="color:#dc2626;">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="current_password" id="pw_current" class="settings-input" required>
                                <button type="button" class="pw-eye" onclick="togglePw('pw_current',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="settings-label">New Password <span style="color:#dc2626;">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password" id="pw_new" class="settings-input"
                                       minlength="8" required>
                                <button type="button" class="pw-eye" onclick="togglePw('pw_new',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="settings-label">Confirm New Password <span style="color:#dc2626;">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="confirm_password" id="pw_confirm" class="settings-input"
                                       minlength="8" required>
                                <button type="button" class="pw-eye" onclick="togglePw('pw_confirm',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <!-- Password strength indicator -->
                        <div class="col-12">
                            <div id="pwStrengthBar" style="height:4px;border-radius:4px;background:#e5e7eb;margin-top:-8px;overflow:hidden;">
                                <div id="pwStrengthFill" style="height:100%;width:0;border-radius:4px;transition:all .3s;"></div>
                            </div>
                            <small id="pwStrengthLabel" style="font-size:11px;color:#9ca3af;display:block;margin-top:4px;"></small>
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-danger-outline">
                                <i class="bi bi-key me-2"></i>Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /profile-wrap -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
    // Password reveal toggle
    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    // Password strength meter
    document.getElementById('pw_new').addEventListener('input', function() {
        const pw    = this.value;
        const fill  = document.getElementById('pwStrengthFill');
        const label = document.getElementById('pwStrengthLabel');
        let score   = 0;
        if (pw.length >= 8)  score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        const levels = [
            { pct:'0%',   color:'#e5e7eb', text:'' },
            { pct:'25%',  color:'#dc2626', text:'Weak' },
            { pct:'50%',  color:'#d97706', text:'Fair' },
            { pct:'75%',  color:'#2563eb', text:'Good' },
            { pct:'100%', color:'#059669', text:'Strong' },
        ];
        fill.style.width      = levels[score].pct;
        fill.style.background = levels[score].color;
        label.textContent     = levels[score].text;
        label.style.color     = levels[score].color;
    });
    </script>
</body>
</html>
