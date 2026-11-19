<?php
session_start();
$required_role = 'SysAdmin';
require '../processes/auth_check.php';

$mysqli = require '../processes/db.php';
require_once '../processes/log_activity.php';

// ── Add is_active column if missing ──
mysqli_query($mysqli, "ALTER TABLE registrar ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");

// ── Ensure contact_inquiries table + is_read column exist ──
mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS contact_inquiries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(180) NOT NULL,
    role       VARCHAR(40),
    subject    VARCHAR(120) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($mysqli, "ALTER TABLE contact_inquiries ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0");

// ── Handle POST actions ──
$actionMsg = ''; $actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    // DELETE EMPLOYER
    if ($action === 'delete_employer' && $user_id > 0) {
        if (mysqli_query($mysqli, "DELETE FROM employer WHERE id='$user_id'")) {
            $actionMsg = 'Employer deleted successfully.'; $actionType = 'success';
        } else { $actionMsg = 'Error: '.mysqli_error($mysqli); $actionType = 'danger'; }
    }
    if ($action === 'approve_employer' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE employer SET status='approved' WHERE id='$user_id'");
        $actionMsg = 'Employer approved. They can now log in and verify certificates.'; $actionType = 'success';
        log_action($mysqli, 'SysAdmin', $_SESSION['user_id'], 'APPROVE_EMPLOYER', "Approved employer ID: $user_id");
    }
    if ($action === 'reject_employer' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE employer SET status='rejected' WHERE id='$user_id'");
        $actionMsg = 'Employer application rejected.'; $actionType = 'warning';
        log_action($mysqli, 'SysAdmin', $_SESSION['user_id'], 'REJECT_EMPLOYER', "Rejected employer ID: $user_id");
    }
    // DELETE GRADUATE (only if no certificate)
    elseif ($action === 'delete_graduate' && $user_id > 0) {
        $adm = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT admissionNumber FROM graduate WHERE id='$user_id' LIMIT 1"))['admissionNumber'] ?? '';
        $safeAdm = mysqli_real_escape_string($mysqli, $adm);
        $certCount = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM certificate WHERE admissionNumber='$safeAdm'"))['c'];
        if ($certCount > 0) {
            $actionMsg = 'Cannot delete — this graduate has a certificate in the system. Delete the certificate first.';
            $actionType = 'danger';
        } else {
            if (mysqli_query($mysqli, "DELETE FROM graduate WHERE id='$user_id'")) {
                $actionMsg = 'Graduate deleted successfully.'; $actionType = 'success';
            } else { $actionMsg = 'Error: '.mysqli_error($mysqli); $actionType = 'danger'; }
        }
    }
    // DEACTIVATE / REACTIVATE REGISTRAR
    elseif ($action === 'toggle_registrar' && $user_id > 0) {
        $cur = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT is_active FROM registrar WHERE id='$user_id' LIMIT 1"));
        $newStatus = ($cur['is_active'] ?? 1) == 1 ? 0 : 1;
        $word = $newStatus ? 'reactivated' : 'deactivated';
        if (mysqli_query($mysqli,"UPDATE registrar SET is_active='$newStatus' WHERE id='$user_id'")) {
            $actionMsg = "Registrar $word. All their certificates remain intact."; $actionType = 'success';
        } else { $actionMsg = 'Error: '.mysqli_error($mysqli); $actionType = 'danger'; }
    }
    // MARK MESSAGE AS READ
    elseif ($action === 'mark_message_read' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE contact_inquiries SET is_read=1 WHERE id='$user_id'");
        $actionMsg = 'Message marked as read.'; $actionType = 'success';
    }
    // MARK MESSAGE AS UNREAD
    elseif ($action === 'mark_message_unread' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE contact_inquiries SET is_read=0 WHERE id='$user_id'");
        $actionMsg = 'Message marked as unread.'; $actionType = 'success';
    }
    // MARK ALL MESSAGES AS READ
    elseif ($action === 'mark_all_messages_read') {
        mysqli_query($mysqli, "UPDATE contact_inquiries SET is_read=1 WHERE is_read=0");
        $actionMsg = 'All messages marked as read.'; $actionType = 'success';
    }
    // DELETE MESSAGE
    elseif ($action === 'delete_message' && $user_id > 0) {
        if (mysqli_query($mysqli, "DELETE FROM contact_inquiries WHERE id='$user_id'")) {
            $actionMsg = 'Message deleted.'; $actionType = 'success';
        } else { $actionMsg = 'Error: '.mysqli_error($mysqli); $actionType = 'danger'; }
    }
}

// ── Stats ──
$total_certs      = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM certificate"))['c'];
$total_registrars = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM registrar"))['c'];
$total_graduates  = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM graduate"))['c'];
$total_employers  = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM employer"))['c'];
$total_unis       = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(DISTINCT universityName) as c FROM registrar"))['c'];
$total_users      = $total_graduates + $total_employers;
$total_messages_unread = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM contact_inquiries WHERE is_read=0"))['c'];
$activeTab        = $_GET['tab'] ?? 'certs';

// ── Certificates ──
$searchName = trim($_GET['search_name']??''); $searchUni=trim($_GET['search_uni']??'');
$searchAdm  = trim($_GET['search_adm']??'');  $searchID =trim($_GET['search_id']??'');
$certsPerPage=10; $certPage=max(1,(int)($_GET['cert_page']??1));
$where='WHERE 1=1'; $params=[]; $types='';
if($searchName!==''){$where.=" AND c.studentNames LIKE ?";   $params[]="%$searchName%";$types.='s';}
if($searchUni!=='') {$where.=" AND c.universityName LIKE ?"; $params[]="%$searchUni%"; $types.='s';}
if($searchAdm!=='') {$where.=" AND c.admissionNumber LIKE ?";$params[]="%$searchAdm%"; $types.='s';}
if($searchID!=='')  {$where.=" AND c.certID LIKE ?";         $params[]="%$searchID%";  $types.='s';}
$cs=$mysqli->prepare("SELECT COUNT(*) as c FROM certificate c $where");
if($types)$cs->bind_param($types,...$params); $cs->execute();
$totalMatchingCerts=$cs->get_result()->fetch_assoc()['c']; $cs->close();
$totalCertPages=max(1,ceil($totalMatchingCerts/$certsPerPage));
if($certPage>$totalCertPages)$certPage=$totalCertPages;
$certOffset=($certPage-1)*$certsPerPage;
$certStmt=$mysqli->prepare("SELECT c.*,r.firstname as reg_first,r.lastname as reg_last FROM certificate c LEFT JOIN registrar r ON c.registrar_id=r.id $where ORDER BY c.id DESC LIMIT ? OFFSET ?");
$certStmt->bind_param($types.'ii',...array_merge($params,[$certsPerPage,$certOffset]));
$certStmt->execute(); $certsResult=$certStmt->get_result();

// ── Pending registrar approvals ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($action === 'approve_registrar' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE registrar SET status='approved' WHERE id='$user_id'");
        $actionMsg  = 'Registrar approved. They can now log in and upload certificates.';
        $actionType = 'success';
        log_action($mysqli, 'SysAdmin', $_SESSION['user_id'], 'APPROVE_REGISTRAR', "Approved registrar ID: $user_id");
    } elseif ($action === 'reject_registrar' && $user_id > 0) {
        mysqli_query($mysqli, "UPDATE registrar SET status='rejected' WHERE id='$user_id'");
        $actionMsg  = 'Registrar application rejected.';
        $actionType = 'danger';
        log_action($mysqli, 'SysAdmin', $_SESSION['user_id'], 'REJECT_REGISTRAR', "Rejected registrar ID: $user_id");
    }
}
$total_pending          = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as c FROM registrar WHERE status='pending'"))['c'];
$total_pending_employers= mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as c FROM employer WHERE status='pending'"))['c'];
$total_pending_all      = $total_pending + $total_pending_employers;
$pendingResult          = mysqli_query($mysqli, "SELECT * FROM registrar WHERE status='pending' ORDER BY id DESC");
$pendingEmpsResult      = mysqli_query($mysqli, "SELECT * FROM employer WHERE status='pending' ORDER BY id DESC");

// ── Registrars ──
$searchRegName=trim($_GET['search_reg_name']??''); $searchRegUni=trim($_GET['search_reg_uni']??'');
$regsPerPage=10; $regPage=max(1,(int)($_GET['reg_page']??1));
$regWhere='WHERE 1=1'; $regParams=[]; $regTypes='';
if($searchRegName!==''){$regWhere.=" AND CONCAT(firstname,' ',lastname) LIKE ?";$regParams[]="%$searchRegName%";$regTypes.='s';}
if($searchRegUni!=='') {$regWhere.=" AND universityName LIKE ?";               $regParams[]="%$searchRegUni%"; $regTypes.='s';}
$rcs=$mysqli->prepare("SELECT COUNT(*) as c FROM registrar $regWhere");
if($regTypes)$rcs->bind_param($regTypes,...$regParams); $rcs->execute();
$totalMatchingRegs=$rcs->get_result()->fetch_assoc()['c']; $rcs->close();
$totalRegPages=max(1,ceil($totalMatchingRegs/$regsPerPage));
if($regPage>$totalRegPages)$regPage=$totalRegPages;
$regOffset=($regPage-1)*$regsPerPage;
$regStmt=$mysqli->prepare("SELECT * FROM registrar $regWhere ORDER BY universityName ASC LIMIT ? OFFSET ?");
$regStmt->bind_param($regTypes.'ii',...array_merge($regParams,[$regsPerPage,$regOffset]));
$regStmt->execute(); $regsResult=$regStmt->get_result();

// ── Universities ──
$uniSearch=trim($_GET['search_uni_tab']??'');
$uniWhere=$uniSearch?"WHERE universityName LIKE '%".mysqli_real_escape_string($mysqli,$uniSearch)."%'":"";
$unisResult=mysqli_query($mysqli,"SELECT universityName,COUNT(*) as cert_count,(SELECT COUNT(*) FROM registrar r2 WHERE r2.universityName=r.universityName) as reg_count FROM registrar r $uniWhere GROUP BY universityName ORDER BY universityName ASC");

// ── Graduates ──
$searchGrad=trim($_GET['search_grad']??''); $gradPage=max(1,(int)($_GET['grad_page']??1)); $gradsPerPage=10;
$gradWhere='WHERE 1=1';
if($searchGrad!==''){$sg=mysqli_real_escape_string($mysqli,$searchGrad);$gradWhere.=" AND (CONCAT(firstname,' ',lastname) LIKE '%$sg%' OR email LIKE '%$sg%' OR admissionNumber LIKE '%$sg%')";}
$totalGrads=mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM graduate $gradWhere"))['c'];
$totalGradPages=max(1,ceil($totalGrads/$gradsPerPage));
if($gradPage>$totalGradPages)$gradPage=$totalGradPages;
$gradOffset=($gradPage-1)*$gradsPerPage;
$gradsResult=mysqli_query($mysqli,"SELECT * FROM graduate $gradWhere ORDER BY firstname ASC LIMIT $gradsPerPage OFFSET $gradOffset");

// ── Employers ──
$searchEmp=trim($_GET['search_emp']??''); $empPage=max(1,(int)($_GET['emp_page']??1)); $empsPerPage=10;
$empWhere='WHERE 1=1';
if($searchEmp!==''){$se=mysqli_real_escape_string($mysqli,$searchEmp);$empWhere.=" AND (CONCAT(firstname,' ',lastname) LIKE '%$se%' OR email LIKE '%$se%')";}
$totalEmps=mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM employer $empWhere"))['c'];
$totalEmpPages=max(1,ceil($totalEmps/$empsPerPage));
if($empPage>$totalEmpPages)$empPage=$totalEmpPages;
$empOffset=($empPage-1)*$empsPerPage;
$empsResult=mysqli_query($mysqli,"SELECT * FROM employer $empWhere ORDER BY firstname ASC LIMIT $empsPerPage OFFSET $empOffset");

// ── Messages (contact form inquiries) ──
$searchMsg=trim($_GET['search_msg']??''); $msgPage=max(1,(int)($_GET['msg_page']??1)); $msgsPerPage=10;
$msgWhere='WHERE 1=1';
if($searchMsg!==''){$sm=mysqli_real_escape_string($mysqli,$searchMsg);$msgWhere.=" AND (name LIKE '%$sm%' OR email LIKE '%$sm%' OR subject LIKE '%$sm%' OR message LIKE '%$sm%')";}
$totalMsgs=mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM contact_inquiries $msgWhere"))['c'];
$totalMsgPages=max(1,ceil($totalMsgs/$msgsPerPage));
if($msgPage>$totalMsgPages)$msgPage=$totalMsgPages;
$msgOffset=($msgPage-1)*$msgsPerPage;
$msgsResult=mysqli_query($mysqli,"SELECT * FROM contact_inquiries $msgWhere ORDER BY is_read ASC, created_at DESC LIMIT $msgsPerPage OFFSET $msgOffset");

// Viewing a single message marks it as read automatically
$viewMsgId = (int)($_GET['view_msg'] ?? 0);
$viewMsg   = null;
if ($viewMsgId > 0) {
    $vm = mysqli_query($mysqli, "SELECT * FROM contact_inquiries WHERE id='$viewMsgId' LIMIT 1");
    $viewMsg = $vm ? mysqli_fetch_assoc($vm) : null;
    if ($viewMsg && (int)$viewMsg['is_read'] === 0) {
        mysqli_query($mysqli, "UPDATE contact_inquiries SET is_read=1 WHERE id='$viewMsgId'");
        $viewMsg['is_read'] = 1;
        $total_messages_unread = max(0, $total_messages_unread - 1);
    }
}

function pageUrl($np){$c=$_GET;foreach($np as $k=>$v)$c[$k]=$v;return'?'.http_build_query($c,'','&amp;');}
function renderPagination($cp,$tp,$pp){
    if($tp<=1)return;
    $s=max(1,$cp-2);$e=min($tp,$s+4);
    echo '<div class="d-flex align-items-center gap-1">';
    echo '<a class="'.($cp<=1?'page-btn disabled':'page-btn').'" href="'.pageUrl([$pp=>$cp-1]).'"><i class="bi bi-chevron-left" style="font-size:10px;"></i></a>';
    for($p=$s;$p<=$e;$p++){echo '<a class="'.($p===$cp?'page-btn active':'page-btn').'" href="'.pageUrl([$pp=>$p]).'">'.$p.'</a>';}
    echo '<a class="'.($cp>=$tp?'page-btn disabled':'page-btn').'" href="'.pageUrl([$pp=>$cp+1]).'"><i class="bi bi-chevron-right" style="font-size:10px;"></i></a>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Admin Dashboard — CertsVerify</title>
    <style>
        .search-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:16px;}
        .pagination-bar{display:flex;align-items:center;justify-content:space-between;padding:10px 0;font-size:12px;color:var(--muted);}
        .page-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--text);font-size:12px;font-weight:600;text-decoration:none;transition:all 0.2s;margin:0 1px;}
        .page-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
        .page-btn.active{background:var(--blue);border-color:var(--blue);color:#fff;}
        .page-btn.disabled{opacity:0.4;pointer-events:none;}
        .cert-id-badge{background:var(--blue-light);color:var(--blue);font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;font-family:monospace;white-space:nowrap;}
        .stat-card-link{display:block;text-decoration:none;border-radius:var(--radius-lg);transition:all 0.2s;}
        .stat-card-link:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(37,99,235,0.15);}
        .click-hint{font-size:10px;color:var(--hint);margin-top:6px;display:flex;align-items:center;justify-content:center;gap:3px;}
        .stat-card-link:hover .click-hint{color:var(--blue);}
        .badge-active{background:#f0fdf4;color:#166534;border:1px solid #86efac;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;}
        .badge-inactive{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;}
        .session-indicator{display:inline-flex;align-items:center;gap:7px;border-radius:20px;padding:5px 12px 5px 8px;font-size:12px;font-weight:600;margin-right:12px;white-space:nowrap;border:1px solid;}
        .session-dot{position:relative;width:8px;height:8px;flex-shrink:0;}
        .session-dot::before{content:'';position:absolute;inset:0;border-radius:50%;}
        .session-dot::after{content:'';position:absolute;inset:-3px;border-radius:50%;opacity:0.4;animation:sessionPulse 2s ease-in-out infinite;}
        @keyframes sessionPulse{0%,100%{transform:scale(1);opacity:0.4;}50%{transform:scale(1.8);opacity:0;}}
        .role-admin{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
        .role-admin .session-dot::before,.role-admin .session-dot::after{background:#ef4444;}
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a href="../index.php" class="navbar-brand">Certs<span>Verify</span></a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto"></ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <div class="session-indicator role-admin">
                            <div class="session-dot"></div>
                            Logged in as <?php echo htmlspecialchars($_SESSION['first_name']??''); ?>
                            &nbsp;·&nbsp;<span style="opacity:0.75;font-weight:500;">System Admin</span>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="nav-avatar"><?php echo strtoupper(substr($_SESSION['first_name'],0,1)); ?></div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?php echo htmlentities($_SESSION['first_name']); ?> <?php echo htmlentities($_SESSION['last_name']); ?></div>
                                    <div style="font-size:11px;color:var(--muted)">System Admin</div>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../processes/logout_process.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <?php if($actionMsg): ?>
        <div class="alert alert-<?php echo $actionType; ?> alert-dismissible fade show" style="font-size:13px;">
            <i class="bi bi-<?php echo $actionType==='success'?'check-circle':'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($actionMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="mb-4">
            <h4 style="font-weight:800;color:var(--navy);">System overview</h4>
            <p style="font-size:13px;color:var(--muted);">Click any card to view the full list</p>
        </div>

        <!-- STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <a href="<?php echo pageUrl(['tab'=>'certs','cert_page'=>1]); ?>" class="stat-card-link">
                    <div class="card text-center py-3" style="border-top:3px solid #2563eb;">
                        <div style="font-size:30px;font-weight:800;color:#1e3a5f;"><?php echo $total_certs; ?></div>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;"><i class="bi bi-file-earmark-check me-1" style="color:#2563eb;"></i>Certificates</div>
                        <div class="click-hint"><i class="bi bi-arrow-right-circle"></i> View all</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo pageUrl(['tab'=>'unis']); ?>" class="stat-card-link">
                    <div class="card text-center py-3" style="border-top:3px solid #059669;">
                        <div style="font-size:30px;font-weight:800;color:#1e3a5f;"><?php echo $total_unis; ?></div>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;"><i class="bi bi-building me-1" style="color:#059669;"></i>Universities</div>
                        <div class="click-hint"><i class="bi bi-arrow-right-circle"></i> View all</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo pageUrl(['tab'=>'regs','reg_page'=>1]); ?>" class="stat-card-link">
                    <div class="card text-center py-3" style="border-top:3px solid #d97706;">
                        <div style="font-size:30px;font-weight:800;color:#1e3a5f;"><?php echo $total_registrars; ?></div>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;"><i class="bi bi-person-badge me-1" style="color:#d97706;"></i>Registrars</div>
                        <div class="click-hint"><i class="bi bi-arrow-right-circle"></i> View all</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo pageUrl(['tab'=>'grads','grad_page'=>1]); ?>" class="stat-card-link">
                    <div class="card text-center py-3" style="border-top:3px solid #7c3aed;">
                        <div style="font-size:30px;font-weight:800;color:#1e3a5f;"><?php echo $total_users; ?></div>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;"><i class="bi bi-people me-1" style="color:#7c3aed;"></i>Users</div>
                        <div class="click-hint"><i class="bi bi-arrow-right-circle"></i> View all</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs mb-0">
            <?php
            $tabs=[
                ['key'=>'pending','label'=>'Pending Approvals','icon'=>'clock-history',  'count'=>$total_pending_all],
                ['key'=>'certs',  'label'=>'Certificates',     'icon'=>'file-earmark-check','count'=>$total_certs],
                ['key'=>'unis',   'label'=>'Universities',      'icon'=>'building',          'count'=>$total_unis],
                ['key'=>'regs',   'label'=>'Registrars',        'icon'=>'person-badge',      'count'=>$total_registrars],
                ['key'=>'grads',  'label'=>'Graduates',         'icon'=>'mortarboard',       'count'=>$total_graduates],
                ['key'=>'emps',   'label'=>'Employers',         'icon'=>'briefcase',         'count'=>$total_employers],
                ['key'=>'log',    'label'=>'Activity Log',      'icon'=>'journal-text',      'count'=>''],
                ['key'=>'messages','label'=>'Messages',         'icon'=>'envelope',          'count'=>$total_messages_unread],
                ['key'=>'backup', 'label'=>'Backup',            'icon'=>'database-down',     'count'=>''],

            ];
            foreach($tabs as $t):
                $badgeStyle = (($t['key']==='pending' || $t['key']==='messages') && $t['count']>0)
                    ? 'background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;'
                    : 'background:var(--blue-light);color:var(--blue);';
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab===$t['key']?'active':''; ?>"
                   href="<?php echo pageUrl(['tab'=>$t['key']]); ?>"
                   style="font-size:13px;font-weight:600;">
                    <i class="bi bi-<?php echo $t['icon']; ?> me-1"></i><?php echo $t['label']; ?>
                    <span style="<?php echo $badgeStyle; ?>font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;margin-left:4px;"><?php echo $t['count']; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- PENDING APPROVALS TAB -->
        <?php if($activeTab==='pending'):?>
        <?php if($total_pending_all===0):?>
        <div class="text-center py-5 mt-3">
            <i class="bi bi-check-circle" style="font-size:56px;color:#059669;"></i>
            <p class="mt-3" style="font-weight:600;color:var(--navy);font-size:16px;">No pending approvals</p>
            <p style="font-size:13px;color:var(--muted);">All registrar and employer applications have been reviewed.</p>
        </div>
        <?php else:?>

        <!-- Pending Registrars -->
        <?php if($total_pending > 0):?>
        <div class="alert alert-warning mt-3" style="font-size:12px;padding:12px 16px;border-radius:10px;">
            <i class="bi bi-clock-history me-2"></i>
            <strong><?php echo $total_pending;?> registrar(s)</strong> waiting for approval.
            They cannot log in or upload certificates until approved.
        </div>
        <div class="card mb-4"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>University applied for</th><th>Phone</th><th>Action</th></tr></thead>
                <tbody>
                    <?php $i=1; while($row=mysqli_fetch_assoc($pendingResult)):?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;color:var(--text);"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['universityName']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['phoneNumber']??'—');?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_registrar">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                    <button type="submit" class="btn btn-success btn-sm" style="font-size:11px;padding:4px 12px;"
                                            onclick="return confirm('Approve <?php echo addslashes(htmlspecialchars($row['firstname'].' '.$row['lastname']));?> as registrar for <?php echo addslashes(htmlspecialchars($row['universityName']));?>?')">
                                        <i class="bi bi-check-lg me-1"></i>Approve
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject_registrar">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:4px 12px;"
                                            onclick="return confirm('Reject this registrar application?')">
                                        <i class="bi bi-x-lg me-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
        </div></div>
        <?php endif;?>

        <!-- Pending Employers -->
        <?php if($total_pending_employers > 0):?>
        <div class="alert mt-3" style="font-size:12px;padding:12px 16px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;">
            <i class="bi bi-briefcase me-2" style="color:#1d4ed8;"></i>
            <strong><?php echo $total_pending_employers;?> employer(s)</strong> waiting for approval.
            They cannot log in or verify certificates until approved.
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Business</th><th>Phone</th><th>Action</th></tr></thead>
                <tbody>
                    <?php $i=1; while($row=mysqli_fetch_assoc($pendingEmpsResult)):?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['businessName']??'—');?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['phoneNumber']??'—');?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_employer">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                    <button type="submit" class="btn btn-success btn-sm" style="font-size:11px;padding:4px 12px;"
                                            onclick="return confirm('Approve <?php echo addslashes(htmlspecialchars($row['firstname'].' '.$row['lastname']));?> from <?php echo addslashes(htmlspecialchars($row['businessName']??''));?>?')">
                                        <i class="bi bi-check-lg me-1"></i>Approve
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject_employer">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:4px 12px;"
                                            onclick="return confirm('Reject this employer application?')">
                                        <i class="bi bi-x-lg me-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
        </div></div>
        <?php endif;?>
        <?php endif;?>

        <!-- CERTS TAB -->
        <?php elseif($activeTab==='certs'): ?>
        <div class="search-card mt-3">
            <form method="GET"><input type="hidden" name="tab" value="certs"><input type="hidden" name="cert_page" value="1">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Student name</label><input type="text" name="search_name" class="form-control" placeholder="e.g. Alex John" value="<?php echo htmlspecialchars($searchName); ?>"></div>
                    <div class="col-md-3"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">University</label><input type="text" name="search_uni" class="form-control" placeholder="e.g. Strathmore" value="<?php echo htmlspecialchars($searchUni); ?>"></div>
                    <div class="col-md-2"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Admission no.</label><input type="text" name="search_adm" class="form-control" placeholder="e.g. 149541" value="<?php echo htmlspecialchars($searchAdm); ?>"></div>
                    <div class="col-md-2"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Certificate ID</label><input type="text" name="search_id" class="form-control" placeholder="e.g. CV-2024-STR" value="<?php echo htmlspecialchars($searchID); ?>"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'certs','search_name'=>'','search_uni'=>'','search_adm'=>'','search_id'=>'','cert_page'=>1]); ?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="pagination-bar">
            <span>Showing <strong><?php echo $totalMatchingCerts>0?$certOffset+1:0;?></strong>–<strong><?php echo min($certOffset+$certsPerPage,$totalMatchingCerts);?></strong> of <strong><?php echo $totalMatchingCerts;?></strong> certificates</span>
            <?php renderPagination($certPage,$totalCertPages,'cert_page');?>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Certificate ID</th><th>Student</th><th>Admission no.</th><th>Course</th><th>University</th><th>Uploaded by</th><th>View</th></tr></thead>
                <tbody>
                    <?php $i=$certOffset+1;while($row=$certsResult->fetch_assoc()):?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td><span class="cert-id-badge"><?php echo htmlspecialchars($row['certID']??'—');?></span></td>
                        <td style="font-weight:600;color:var(--text);"><?php echo htmlspecialchars($row['studentNames']);?></td>
                        <td><?php echo htmlspecialchars($row['admissionNumber']);?></td>
                        <td style="font-size:12px;max-width:160px;"><?php echo htmlspecialchars($row['courseName']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['universityName']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars(trim($row['reg_first'].' '.$row['reg_last']));?></td>
                        <td><a href="http://localhost/CertsVerify/serve_cert.php?id=<?php echo htmlspecialchars($row['certID']);?>" target="_blank" class="btn btn-primary btn-sm" style="font-size:11px;padding:4px 10px;"><i class="bi bi-eye me-1"></i>View</a></td>
                    </tr>
                    <?php endwhile;?>
                    <?php if($totalMatchingCerts===0):?><tr><td colspan="8" class="text-center py-4" style="color:var(--muted);">No certificates found.</td></tr><?php endif;?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar"><span>Page <strong><?php echo $certPage;?></strong> of <strong><?php echo $totalCertPages;?></strong></span><?php renderPagination($certPage,$totalCertPages,'cert_page');?></div>

        <!-- UNIS TAB -->
        <?php elseif($activeTab==='unis'):?>
        <div class="d-flex justify-content-between align-items-center mt-3 mb-2 flex-wrap gap-2">
            <div></div>
            <a href="../processes/generate_universities_report.php" target="_blank"
               class="btn btn-sm" style="font-size:12px;font-weight:600;padding:7px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download Universities Report
            </a>
        </div>
        <div class="search-card mt-1">
            <form method="GET"><input type="hidden" name="tab" value="unis">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Search university</label><input type="text" name="search_uni_tab" class="form-control" placeholder="e.g. Nairobi" value="<?php echo htmlspecialchars($uniSearch);?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'unis','search_uni_tab'=>'']);?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>University</th><th>Registrars</th><th>Certificates</th></tr></thead>
                <tbody>
                    <?php $i=1;while($row=mysqli_fetch_assoc($unisResult)):?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;"><i class="bi bi-building me-2" style="color:#059669;"></i><?php echo htmlspecialchars($row['universityName']);?></td>
                        <td><a href="<?php echo pageUrl(['tab'=>'regs','search_reg_uni'=>urlencode($row['universityName']),'reg_page'=>1]);?>" style="color:#d97706;font-weight:700;text-decoration:none;"><?php echo $row['reg_count'];?> registrar(s) <i class="bi bi-arrow-right ms-1" style="font-size:10px;"></i></a></td>
                        <td><a href="<?php echo pageUrl(['tab'=>'certs','search_uni'=>urlencode($row['universityName']),'cert_page'=>1]);?>" style="color:#2563eb;font-weight:700;text-decoration:none;"><?php echo $row['cert_count'];?> certificate(s) <i class="bi bi-arrow-right ms-1" style="font-size:10px;"></i></a></td>
                    </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
        </div></div>

        <!-- REGISTRARS TAB -->
        <?php elseif($activeTab==='regs'):?>
        <div class="d-flex justify-content-between align-items-center mt-3 mb-1 flex-wrap gap-2">
            <div></div>
            <a href="../processes/generate_registrars_report.php" target="_blank"
               class="btn btn-sm" style="font-size:12px;font-weight:600;padding:7px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download Registrars Report
            </a>
        </div>
        <div class="alert alert-info mt-1" style="font-size:12px;padding:10px 16px;border-radius:10px;">
            <i class="bi bi-info-circle me-2"></i>
            Registrars are <strong>deactivated</strong> rather than deleted to preserve all certificate records.
            A deactivated registrar cannot log in but all their certificates remain fully accessible.
        </div>
        <div class="search-card">
            <form method="GET"><input type="hidden" name="tab" value="regs"><input type="hidden" name="reg_page" value="1">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Name</label><input type="text" name="search_reg_name" class="form-control" placeholder="e.g. Jane Waweru" value="<?php echo htmlspecialchars($searchRegName);?>"></div>
                    <div class="col-md-5"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">University</label><input type="text" name="search_reg_uni" class="form-control" placeholder="e.g. Kenyatta" value="<?php echo htmlspecialchars($searchRegUni);?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'regs','search_reg_name'=>'','search_reg_uni'=>'','reg_page'=>1]);?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="pagination-bar">
            <span>Showing <strong><?php echo $totalMatchingRegs>0?$regOffset+1:0;?></strong>–<strong><?php echo min($regOffset+$regsPerPage,$totalMatchingRegs);?></strong> of <strong><?php echo $totalMatchingRegs;?></strong> registrars</span>
            <?php renderPagination($regPage,$totalRegPages,'reg_page');?>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>University</th><th>Phone</th><th>Status</th><th>Certs</th><th>Action</th></tr></thead>
                <tbody>
                    <?php $i=$regOffset+1;while($row=$regsResult->fetch_assoc()):
                        $certCount=mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM certificate WHERE registrar_id='{$row['id']}'"))['c'];
                        $isActive=($row['is_active']??1)==1;?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['universityName']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['phoneNumber']);?></td>
                        <td><?php if($isActive):?><span class="badge-active"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Active</span><?php else:?><span class="badge-inactive"><i class="bi bi-circle me-1" style="font-size:7px;"></i>Inactive</span><?php endif;?></td>
                        <td><span style="background:var(--blue-light);color:var(--blue);font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;"><?php echo $certCount;?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_registrar">
                                <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                <button type="submit" class="btn btn-sm <?php echo $isActive?'btn-warning':'btn-success';?>" style="font-size:11px;padding:3px 10px;"
                                        onclick="return confirm('<?php echo $isActive?'Deactivate':'Reactivate';?> this registrar?')">
                                    <i class="bi bi-<?php echo $isActive?'slash-circle':'check-circle';?> me-1"></i>
                                    <?php echo $isActive?'Deactivate':'Reactivate';?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    <?php if($totalMatchingRegs===0):?><tr><td colspan="8" class="text-center py-4" style="color:var(--muted);">No registrars found.</td></tr><?php endif;?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar"><span>Page <strong><?php echo $regPage;?></strong> of <strong><?php echo $totalRegPages;?></strong></span><?php renderPagination($regPage,$totalRegPages,'reg_page');?></div>

        <!-- GRADUATES TAB -->
        <?php elseif($activeTab==='grads'):?>
        <div class="alert alert-warning mt-3" style="font-size:12px;padding:10px 16px;border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Graduates with an uploaded certificate are <strong>protected</strong> and cannot be deleted — delete their certificate first.
        </div>
        <div class="search-card">
            <form method="GET"><input type="hidden" name="tab" value="grads"><input type="hidden" name="grad_page" value="1">
                <div class="row g-2 align-items-end">
                    <div class="col-md-7"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Search by name, email or admission no.</label><input type="text" name="search_grad" class="form-control" placeholder="e.g. Brian or KU/CS/2021/001" value="<?php echo htmlspecialchars($searchGrad);?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'grads','search_grad'=>'','grad_page'=>1]);?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="pagination-bar">
            <span>Showing <strong><?php echo $totalGrads>0?$gradOffset+1:0;?></strong>–<strong><?php echo min($gradOffset+$gradsPerPage,$totalGrads);?></strong> of <strong><?php echo $totalGrads;?></strong> graduates</span>
            <?php renderPagination($gradPage,$totalGradPages,'grad_page');?>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>University</th><th>Admission no.</th><th>Has Cert</th><th>Action</th></tr></thead>
                <tbody>
                    <?php $i=$gradOffset+1;while($row=mysqli_fetch_assoc($gradsResult)):
                        $safeAdm=mysqli_real_escape_string($mysqli,$row['admissionNumber']);
                        $hasCert=mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT COUNT(*) as c FROM certificate WHERE admissionNumber='$safeAdm'"))['c']>0;?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['universityName']??'—');?></td>
                        <td style="font-size:12px;font-family:monospace;"><?php echo htmlspecialchars($row['admissionNumber']);?></td>
                        <td><?php if($hasCert):?><span style="background:#f0fdf4;color:#166634;border:1px solid #86efac;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;"><i class="bi bi-check-circle me-1"></i>Yes</span><?php else:?><span style="background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;"><i class="bi bi-x-circle me-1"></i>No</span><?php endif;?></td>
                        <td>
                            <?php if(!$hasCert):?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_graduate">
                                <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:3px 10px;"
                                        onclick="return confirm('Delete <?php echo addslashes(htmlspecialchars($row['firstname'].' '.$row['lastname']));?>? This cannot be undone.')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                            <?php else:?>
                            <button class="btn btn-secondary btn-sm" disabled style="font-size:11px;padding:3px 10px;" title="Delete certificate first">
                                <i class="bi bi-lock me-1"></i>Protected
                            </button>
                            <?php endif;?>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    <?php if($totalGrads===0):?><tr><td colspan="7" class="text-center py-4" style="color:var(--muted);">No graduates found.</td></tr><?php endif;?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar"><span>Page <strong><?php echo $gradPage;?></strong> of <strong><?php echo $totalGradPages;?></strong></span><?php renderPagination($gradPage,$totalGradPages,'grad_page');?></div>

        <!-- EMPLOYERS TAB -->
        <?php elseif($activeTab==='emps'):?>
        <div class="d-flex justify-content-between align-items-center mt-3 mb-1 flex-wrap gap-2">
            <div></div>
            <a href="../processes/generate_employers_report.php" target="_blank"
               class="btn btn-sm" style="font-size:12px;font-weight:600;padding:7px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download Employers Report
            </a>
        </div>
        <div class="alert alert-info mt-1" style="font-size:12px;padding:10px 16px;border-radius:10px;">
            <i class="bi bi-info-circle me-2"></i>
            Employers have no linked records and can be safely deleted at any time.
        </div>
        <div class="search-card">
            <form method="GET"><input type="hidden" name="tab" value="emps"><input type="hidden" name="emp_page" value="1">
                <div class="row g-2 align-items-end">
                    <div class="col-md-7"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Search by name or email</label><input type="text" name="search_emp" class="form-control" placeholder="e.g. James or james@company.com" value="<?php echo htmlspecialchars($searchEmp);?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'emps','search_emp'=>'','emp_page'=>1]);?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="pagination-bar">
            <span>Showing <strong><?php echo $totalEmps>0?$empOffset+1:0;?></strong>–<strong><?php echo min($empOffset+$empsPerPage,$totalEmps);?></strong> of <strong><?php echo $totalEmps;?></strong> employers</span>
            <?php renderPagination($empPage,$totalEmpPages,'emp_page');?>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php $i=$empOffset+1;while($row=mysqli_fetch_assoc($empsResult)):?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['phoneNumber']??'—');?></td>
                        <td>
                            <?php $es = $row['status'] ?? 'approved'; ?>
                            <?php if($es==='approved'):?>
                                <span style="background:#f0fdf4;color:#166534;border:1px solid #86efac;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;">✅ Approved</span>
                            <?php elseif($es==='pending'):?>
                                <span style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;">⏳ Pending</span>
                            <?php else:?>
                                <span style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;">❌ Rejected</span>
                            <?php endif;?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_employer">
                                <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:3px 10px;"
                                        onclick="return confirm('Delete <?php echo addslashes(htmlspecialchars($row['firstname'].' '.$row['lastname']));?>? This cannot be undone.')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    <?php if($totalEmps===0):?><tr><td colspan="5" class="text-center py-4" style="color:var(--muted);">No employers found.</td></tr><?php endif;?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar"><span>Page <strong><?php echo $empPage;?></strong> of <strong><?php echo $totalEmpPages;?></strong></span><?php renderPagination($empPage,$totalEmpPages,'emp_page');?></div>

        <!-- ACTIVITY LOG TAB -->
        <?php elseif($activeTab==='log'):?>
        <?php
            $log_page     = max(1, (int)($_GET['log_page'] ?? 1));
            $log_per_page = 20;
            $log_total    = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as c FROM activity_log"))['c'] ?? 0;
            $log_pages    = max(1, ceil($log_total / $log_per_page));
            $log_page     = min($log_page, $log_pages);
            $log_offset   = ($log_page - 1) * $log_per_page;
            $log_result   = mysqli_query($mysqli, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT $log_per_page OFFSET $log_offset");
        ?>
        <div class="d-flex justify-content-between align-items-center mt-3 mb-2 flex-wrap gap-2">
            <h6 style="font-weight:700;color:var(--navy);margin:0;">System Activity Log</h6>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span style="font-size:11px;color:var(--muted);">
                    Last updated: <strong><?php echo date('H:i:s'); ?></strong>
                    · Auto-refreshes every 30s
                </span>
                <a href="?tab=log&log_page=<?php echo $log_page; ?>"
                   class="btn btn-sm"
                   style="font-size:11px;padding:3px 10px;background:#f0f4ff;color:var(--blue);border:1px solid #c7d7fd;border-radius:6px;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh now
                </a>
                <a href="../processes/generate_activity_log_report.php" target="_blank"
                   class="btn btn-sm" style="font-size:11px;font-weight:600;padding:3px 12px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Download Activity Log Report
                </a>
                <span style="font-size:12px;color:var(--muted);"><?php echo number_format($log_total); ?> total events</span>
            </div>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table mb-0" style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="width:150px;">Time</th>
                        <th style="width:90px;">Role</th>
                        <th style="width:140px;">Action</th>
                        <th>Detail</th>
                        <th style="width:110px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($log_total === 0):?>
                    <tr><td colspan="5" class="text-center py-4" style="color:var(--muted);">No activity recorded yet.</td></tr>
                <?php else: while($log = mysqli_fetch_assoc($log_result)):
                    $action_colors = [
                        'LOGIN'              => ['bg'=>'#f0fdf4','color'=>'#166534'],
                        'LOGIN_FAILED'       => ['bg'=>'#fef2f2','color'=>'#991b1b'],
                        'CERT_VERIFY'        => ['bg'=>'#eff6ff','color'=>'#1e40af'],
                        'BATCH_VERIFY'       => ['bg'=>'#f0f9ff','color'=>'#0369a1'],
                        'APPROVE_REGISTRAR'  => ['bg'=>'#f0fdf4','color'=>'#166534'],
                        'REJECT_REGISTRAR'   => ['bg'=>'#fef2f2','color'=>'#991b1b'],
                        'APPROVE_EMPLOYER'   => ['bg'=>'#f0fdf4','color'=>'#166534'],
                        'REJECT_EMPLOYER'    => ['bg'=>'#fef2f2','color'=>'#991b1b'],
                    ];
                    $ac = $action_colors[$log['action']] ?? ['bg'=>'#f9fafb','color'=>'#374151'];
                ?>
                    <tr>
                        <td style="color:var(--muted);white-space:nowrap;">
                            <?php echo date('d M Y H:i', strtotime($log['created_at'])); ?>
                        </td>
                        <td>
                            <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;background:#f1f5f9;color:#475569;">
                                <?php echo htmlspecialchars($log['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;background:<?php echo $ac['bg'];?>;color:<?php echo $ac['color'];?>;">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td style="color:var(--text);"><?php echo htmlspecialchars($log['detail']); ?></td>
                        <td style="color:var(--muted);font-family:monospace;font-size:11px;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar">
            <span>Page <strong><?php echo $log_page;?></strong> of <strong><?php echo $log_pages;?></strong></span>
            <?php renderPagination($log_page, $log_pages, 'log_page'); ?>
        </div>

        <!-- MESSAGES TAB -->
        <?php elseif($activeTab==='messages'):?>

        <?php if($viewMsg):?>
        <!-- Single message view -->
        <div class="d-flex justify-content-between align-items-center mt-3 mb-2 flex-wrap gap-2">
            <a href="<?php echo pageUrl(['tab'=>'messages','view_msg'=>null]); ?>" class="btn btn-sm" style="font-size:12px;font-weight:600;padding:6px 14px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;">
                <i class="bi bi-arrow-left me-1"></i>Back to all messages
            </a>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h6 style="font-weight:800;color:var(--navy);margin-bottom:2px;"><?php echo htmlspecialchars($viewMsg['subject']);?></h6>
                        <span style="font-size:12px;color:var(--muted);">
                            <?php echo date('d M Y, H:i', strtotime($viewMsg['created_at']));?>
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_message_unread">
                            <input type="hidden" name="user_id" value="<?php echo $viewMsg['id'];?>">
                            <button type="submit" class="btn btn-sm" style="font-size:11px;font-weight:600;padding:5px 12px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:8px;">
                                <i class="bi bi-envelope me-1"></i>Mark as unread
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this message? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete_message">
                            <input type="hidden" name="user_id" value="<?php echo $viewMsg['id'];?>">
                            <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px;font-weight:600;padding:5px 12px;border-radius:8px;">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">From</div>
                        <div style="font-size:14px;font-weight:600;color:var(--text);"><?php echo htmlspecialchars($viewMsg['name']);?></div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">Email</div>
                        <div style="font-size:14px;font-weight:600;color:var(--text);">
                            <a href="mailto:<?php echo htmlspecialchars($viewMsg['email']);?>" style="color:var(--blue);text-decoration:none;"><?php echo htmlspecialchars($viewMsg['email']);?></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">Role</div>
                        <div style="font-size:14px;font-weight:600;color:var(--text);"><?php echo htmlspecialchars($viewMsg['role'] ?: '—');?></div>
                    </div>
                </div>
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Message</div>
                <div style="font-size:14px;color:var(--text);line-height:1.7;white-space:pre-wrap;background:#f8faff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;">
<?php echo htmlspecialchars($viewMsg['message']);?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Messages list -->
        <div class="d-flex justify-content-between align-items-center mt-3 mb-1 flex-wrap gap-2">
            <div></div>
            <?php if($total_messages_unread > 0):?>
            <form method="POST">
                <input type="hidden" name="action" value="mark_all_messages_read">
                <button type="submit" class="btn btn-sm" style="font-size:12px;font-weight:600;padding:7px 16px;background:#f0fdf4;color:#166534;border:1px solid #86efac;border-radius:8px;">
                    <i class="bi bi-check2-all me-1"></i>Mark all as read
                </button>
            </form>
            <?php endif;?>
        </div>
        <div class="search-card">
            <form method="GET"><input type="hidden" name="tab" value="messages"><input type="hidden" name="msg_page" value="1">
                <div class="row g-2 align-items-end">
                    <div class="col-md-7"><label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px;">Search by name, email, or subject</label><input type="text" name="search_msg" class="form-control" placeholder="e.g. Jane or jane@example.com" value="<?php echo htmlspecialchars($searchMsg);?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                    <div class="col-md-1"><a href="<?php echo pageUrl(['tab'=>'messages','search_msg'=>'','msg_page'=>1]);?>" class="btn btn-dark w-100"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>
        <div class="pagination-bar">
            <span>Showing <strong><?php echo $totalMsgs>0?$msgOffset+1:0;?></strong>–<strong><?php echo min($msgOffset+$msgsPerPage,$totalMsgs);?></strong> of <strong><?php echo $totalMsgs;?></strong> messages
            <?php if($total_messages_unread>0):?> · <strong style="color:#991b1b;"><?php echo $total_messages_unread;?> unread</strong><?php endif;?></span>
            <?php renderPagination($msgPage,$totalMsgPages,'msg_page');?>
        </div>
        <div class="card mb-3"><div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th style="width:36px;"></th><th>Name</th><th>Email</th><th>Role</th><th>Subject</th><th>Received</th><th style="width:140px;">Action</th></tr></thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($msgsResult)):
                        $isUnread = (int)$row['is_read'] === 0;
                    ?>
                    <tr style="<?php echo $isUnread ? 'background:#eff6ff;font-weight:600;' : '';?>">
                        <td>
                            <?php if($isUnread):?>
                                <span title="Unread" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;"></span>
                            <?php endif;?>
                        </td>
                        <td><?php echo htmlspecialchars($row['name']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['email']);?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['role'] ?: '—');?></td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($row['subject']);?></td>
                        <td style="font-size:12px;color:var(--muted);white-space:nowrap;"><?php echo date('d M Y, H:i', strtotime($row['created_at']));?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo pageUrl(['tab'=>'messages','view_msg'=>$row['id']]);?>" class="btn btn-sm" style="font-size:11px;padding:3px 10px;background:#eff6ff;color:var(--blue);border:1px solid #c7d7fd;border-radius:6px;">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <form method="POST" onsubmit="return confirm('Delete this message? This cannot be undone.')">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id'];?>">
                                    <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px;padding:3px 8px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    <?php if($totalMsgs===0):?><tr><td colspan="7" class="text-center py-4" style="color:var(--muted);">No messages yet. Submissions from the public Contact Us form will appear here.</td></tr><?php endif;?>
                </tbody>
            </table>
        </div></div>
        <div class="pagination-bar"><span>Page <strong><?php echo $msgPage;?></strong> of <strong><?php echo $totalMsgPages;?></strong></span><?php renderPagination($msgPage,$totalMsgPages,'msg_page');?></div>
        <?php endif;?>

        <!-- ══════════════════════════════════════════════════════
             BACKUP TAB
        ══════════════════════════════════════════════════════ -->
        <?php elseif($activeTab==='backup'):

        // ── Ensure backup_log table exists ────────────────────────────────────
        mysqli_query($mysqli,"CREATE TABLE IF NOT EXISTS backup_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            backup_type VARCHAR(30) NOT NULL,
            filename    VARCHAR(255) NOT NULL,
            file_size   VARCHAR(30) DEFAULT '',
            admin_id    INT DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ── Fetch last 20 backup entries ──────────────────────────────────────
        $backupHistory = mysqli_query($mysqli,
            "SELECT b.*, CONCAT(s.firstname,' ',s.lastname) as admin_name
             FROM backup_log b
             LEFT JOIN sysadmin s ON b.admin_id = s.id
             ORDER BY b.created_at DESC LIMIT 20");
        ?>

        <div class="mt-4">
            <!-- Header row -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                <div>
                    <h5 style="font-weight:800;color:var(--navy);margin:0;">Database Backup</h5>
                    <p style="font-size:12px;color:var(--muted);margin:4px 0 0;">
                        Download a full SQL dump of <strong>verifyDB</strong> or a ZIP of all uploaded certificate PDFs.
                        Keep backups in a safe location — they contain sensitive student data.
                    </p>
                </div>
            </div>

            <!-- Action cards -->
            <div class="row g-3 mb-4">

                <!-- SQL Dump -->
                <div class="col-md-6">
                    <div class="card h-100" style="border-radius:14px;border:1px solid #e5e7eb;">
                        <div class="card-body p-4">
                            <div style="width:48px;height:48px;background:#eff6ff;border-radius:12px;
                                        display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                                <i class="bi bi-database-down" style="font-size:22px;color:var(--blue);"></i>
                            </div>
                            <h6 style="font-weight:700;color:var(--navy);margin-bottom:6px;">Full Database Dump</h6>
                            <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">
                                Downloads a complete <code>.sql</code> file containing all tables and data —
                                certificates, registrars, graduates, employers, activity logs, and messages.
                                Use this to restore the system in phpMyAdmin if the database is lost.
                            </p>
                            <a href="../processes/admin_backup.php?type=sql"
                               style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;
                                      padding:9px 18px;background:var(--blue);color:#fff;border-radius:9px;
                                      text-decoration:none;">
                                <i class="bi bi-download"></i> Download SQL Backup
                            </a>
                        </div>
                    </div>
                </div>

                <!-- PDF ZIP -->
                <div class="col-md-6">
                    <div class="card h-100" style="border-radius:14px;border:1px solid #e5e7eb;">
                        <div class="card-body p-4">
                            <div style="width:48px;height:48px;background:#f0fdf4;border-radius:12px;
                                        display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                                <i class="bi bi-file-zip" style="font-size:22px;color:#059669;"></i>
                            </div>
                            <h6 style="font-weight:700;color:var(--navy);margin-bottom:6px;">Certificate PDFs Archive</h6>
                            <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">
                                Downloads a <code>.zip</code> file containing every certificate PDF in the
                                <code>/uploads/</code> folder. Use this alongside the SQL dump so you can
                                restore both the database records and the actual files.
                            </p>
                            <a href="../processes/admin_backup.php?type=zip"
                               style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;
                                      padding:9px 18px;background:#059669;color:#fff;border-radius:9px;
                                      text-decoration:none;">
                                <i class="bi bi-download"></i> Download PDFs ZIP
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How to restore notice -->
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px 20px;margin-bottom:28px;">
                <div style="font-size:12px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">
                    <i class="bi bi-info-circle me-1"></i>How to restore from a backup
                </div>
                <ol style="font-size:12px;color:#78350f;margin:0;padding-left:18px;line-height:2;">
                    <li>Open <strong>phpMyAdmin</strong> and select the <strong>verifyDB</strong> database.</li>
                    <li>Click the <strong>Import</strong> tab, choose your <code>.sql</code> file, and click Go.</li>
                    <li>Copy the <code>/uploads/</code> folder contents from your PDFs ZIP back into <code>CertsVerify/uploads/</code>.</li>
                    <li>Restart XAMPP and confirm the system is working normally.</li>
                </ol>
            </div>

            <!-- Backup history -->
            <h6 style="font-weight:700;color:var(--navy);margin-bottom:12px;">
                <i class="bi bi-clock-history me-2"></i>Recent Backup History
            </h6>
            <div class="card" style="border-radius:12px;overflow:hidden;">
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Downloaded By</th>
                                <th>Date &amp; Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $bNum = 0;
                        while($bl = mysqli_fetch_assoc($backupHistory)):
                            $bNum++;
                            $isSql = $bl['backup_type'] === 'sql';
                        ?>
                        <tr>
                            <td style="color:var(--muted);"><?php echo $bNum;?></td>
                            <td>
                                <?php if($isSql):?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;
                                             color:var(--blue);border:1px solid #c7d7fd;border-radius:6px;
                                             padding:3px 9px;font-size:11px;font-weight:600;">
                                    <i class="bi bi-database"></i> SQL Dump
                                </span>
                                <?php else:?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;
                                             color:#059669;border:1px solid #86efac;border-radius:6px;
                                             padding:3px 9px;font-size:11px;font-weight:600;">
                                    <i class="bi bi-file-zip"></i> PDFs ZIP
                                </span>
                                <?php endif;?>
                            </td>
                            <td style="font-family:monospace;font-size:12px;"><?php echo htmlspecialchars($bl['filename']);?></td>
                            <td style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($bl['file_size']);?></td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($bl['admin_name'] ?? 'Admin');?></td>
                            <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                                <?php echo date('d M Y, H:i', strtotime($bl['created_at']));?>
                            </td>
                        </tr>
                        <?php endwhile;?>
                        <?php if($bNum===0):?>
                        <tr>
                            <td colspan="6" class="text-center py-4" style="color:var(--muted);">
                                No backups have been downloaded yet.
                            </td>
                        </tr>
                        <?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- end backup tab -->

=======
>>>>>>> 83b8309e27daf35fea26f2e49a099ecc1b4a435b
        <?php endif;?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
<!-- Auto-refresh activity log every 30 seconds when on that tab -->
<?php if($activeTab === 'log'): ?>
<script>
    setTimeout(function() {
        window.location.href = '?tab=log&log_page=<?php echo $log_page; ?>';
    }, 30000); // 30 seconds

    // Countdown display
    let seconds = 30;
    const countdownEl = document.createElement('span');
    countdownEl.style.cssText = 'font-size:11px;color:#9ca3af;margin-left:6px;';
    const refreshBtn = document.querySelector('a[href*="tab=log"]');
    if (refreshBtn) refreshBtn.parentNode.appendChild(countdownEl);

    setInterval(function() {
        seconds--;
        if (seconds > 0) countdownEl.textContent = '(' + seconds + 's)';
    }, 1000);
</script>
<?php endif; ?>
<!-- Report download feedback toast -->
<div id="reportToast" style="display:none;position:fixed;bottom:24px;right:24px;z-index:99999;
     background:#1e3a5f;color:#fff;padding:14px 20px;border-radius:10px;font-size:13px;
     font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.25);max-width:320px;">
    <i class="bi bi-check-circle-fill me-2" style="color:#4ade80;"></i>
    <span id="reportToastMsg">Report download started…</span>
</div>
<script>
function showReportToast(message, isError) {
    const toast = document.getElementById('reportToast');
    const msg   = document.getElementById('reportToastMsg');
    const icon  = toast.querySelector('i');
    msg.textContent = message;
    icon.className  = isError
        ? 'bi bi-exclamation-circle-fill me-2'
        : 'bi bi-check-circle-fill me-2';
    icon.style.color = isError ? '#f87171' : '#4ade80';
    toast.style.background = isError ? '#991b1b' : '#1e3a5f';
    toast.style.display = 'block';
    clearTimeout(window._reportToastTimer);
    window._reportToastTimer = setTimeout(() => { toast.style.display = 'none'; }, 4000);
}

// Attach to every report download link
document.querySelectorAll('a[href*="generate_"][href*="_report.php"]').forEach(function(link) {
    link.addEventListener('click', function() {
        showReportToast('Generating report — your download will begin shortly…', false);
    });
});
</script>
</body>
</html>
