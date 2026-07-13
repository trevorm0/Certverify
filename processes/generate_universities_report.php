<?php
/*
 * ============================================================
 * generate_universities_report.php
 * ============================================================
 * Generates Universities_Report.pdf — only accessible to
 * authenticated SysAdmin users.
 * ============================================================
 */
session_start();
$required_role = 'SysAdmin';
require __DIR__ . '/auth_check.php';   // kicks out anyone not logged in as admin

$mysqli = require __DIR__ . '/db.php';
require_once __DIR__ . '/log_activity.php';
require_once __DIR__ . '/ReportPDF.php';

try {
    // Pull every university with registrar + certificate counts
    $sql = "
        SELECT
            u.universityName,
            (SELECT COUNT(*) FROM registrar r WHERE r.universityName = u.universityName) AS reg_count,
            (SELECT COUNT(*) FROM certificate c WHERE c.universityName = u.universityName) AS cert_count
        FROM (SELECT DISTINCT universityName FROM registrar) u
        ORDER BY u.universityName ASC
    ";
    $result = mysqli_query($mysqli, $sql);
    if (!$result) throw new Exception(mysqli_error($mysqli));

    $rows = [];
    $i = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $i++,
            $r['universityName'],
            $r['reg_count'],
            $r['cert_count'],
        ];
    }

    $pdf = new ReportPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->reportTitle = 'Universities Report';

    $pdf->SectionTitle(
        'Registered Universities',
        count($rows) . ' university record(s) in the system'
    );

    $pdf->RenderTable(
        ['#', 'University Name', 'Registrars', 'Certificates'],
        [15, 110, 32, 33],
        $rows
    );

    // Log the download
    log_action($mysqli, 'SysAdmin', $_SESSION['user_id'] ?? 0,
        'DOWNLOAD_REPORT', 'Downloaded Universities_Report.pdf');

    // Stream the PDF to the browser as a download
    $pdf->Output('D', 'Universities_Report.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "<script>
        alert('Failed to generate Universities report: " . addslashes($e->getMessage()) . "');
        document.location='../admin/dashboard.php?tab=unis';
    </script>";
    exit;
}
?>
