<?php
/*
 * ============================================================
 * generate_registrars_report.php
 * ============================================================
 * Generates Registrars_Report.pdf — only accessible to
 * authenticated SysAdmin users.
 * ============================================================
 */
session_start();
$required_role = 'SysAdmin';
require __DIR__ . '/auth_check.php';

$mysqli = require __DIR__ . '/db.php';
require_once __DIR__ . '/log_activity.php';
require_once __DIR__ . '/ReportPDF.php';

try {
    $sql = "SELECT * FROM registrar ORDER BY firstname ASC";
    $result = mysqli_query($mysqli, $sql);
    if (!$result) throw new Exception(mysqli_error($mysqli));

    $rows = [];
    $i = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $certCountRes = mysqli_query($mysqli,
            "SELECT COUNT(*) as c FROM certificate WHERE registrar_id='" . (int)$r['id'] . "'");
        $certCount = mysqli_fetch_assoc($certCountRes)['c'] ?? 0;

        $status = $r['status'] ?? 'approved';
        $isActive = ($r['is_active'] ?? 1) == 1;
        $statusLabel = $status === 'pending' ? 'Pending'
            : ($status === 'rejected' ? 'Rejected'
            : ($isActive ? 'Active' : 'Inactive'));

        $rows[] = [
            $i++,
            $r['firstname'] . ' ' . $r['lastname'],
            $r['email'],
            $r['universityName'],
            $r['phoneNumber'] ?? '-',
            $statusLabel,
            $certCount,
        ];
    }

    $pdf = new ReportPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage('L'); // Landscape — more columns fit comfortably
    $pdf->reportTitle = 'Registrars Report';

    $pdf->SectionTitle(
        'Registered Registrars',
        count($rows) . ' registrar record(s) in the system'
    );

    $pdf->RenderTable(
        ['#', 'Name', 'Email', 'University', 'Phone', 'Status', 'Certs'],
        [10, 45, 60, 75, 30, 25, 20],
        $rows
    );

    log_action($mysqli, 'SysAdmin', $_SESSION['user_id'] ?? 0,
        'DOWNLOAD_REPORT', 'Downloaded Registrars_Report.pdf');

    $pdf->Output('D', 'Registrars_Report.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "<script>
        alert('Failed to generate Registrars report: " . addslashes($e->getMessage()) . "');
        document.location='../admin/dashboard.php?tab=regs';
    </script>";
    exit;
}
?>
