<?php
/*
 * ============================================================
 * generate_employers_report.php
 * ============================================================
 * Generates Employers_Report.pdf — only accessible to
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
    $sql = "SELECT * FROM employer ORDER BY firstname ASC";
    $result = mysqli_query($mysqli, $sql);
    if (!$result) throw new Exception(mysqli_error($mysqli));

    $rows = [];
    $i = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $status = $r['status'] ?? 'approved';
        $statusLabel = $status === 'pending' ? 'Pending'
            : ($status === 'rejected' ? 'Rejected' : 'Approved');

        $rows[] = [
            $i++,
            $r['firstname'] . ' ' . $r['lastname'],
            $r['email'],
            $r['businessName'] ?? '-',
            $r['phoneNumber'] ?? '-',
            $statusLabel,
        ];
    }

    $pdf = new ReportPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->reportTitle = 'Employers Report';

    $pdf->SectionTitle(
        'Registered Employers',
        count($rows) . ' employer record(s) in the system'
    );

    $pdf->RenderTable(
        ['#', 'Name', 'Email', 'Business', 'Phone', 'Status'],
        [12, 40, 55, 45, 28, 20],
        $rows
    );

    log_action($mysqli, 'SysAdmin', $_SESSION['user_id'] ?? 0,
        'DOWNLOAD_REPORT', 'Downloaded Employers_Report.pdf');

    $pdf->Output('D', 'Employers_Report.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "<script>
        alert('Failed to generate Employers report: " . addslashes($e->getMessage()) . "');
        document.location='../admin/dashboard.php?tab=emps';
    </script>";
    exit;
}
?>
