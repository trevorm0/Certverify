<?php
/*
 * ============================================================
 * generate_activity_log_report.php
 * ============================================================
 * Generates Activity_Log_Report.pdf — only accessible to
 * authenticated SysAdmin users.
 *
 * Includes every logged action, the role/user involved,
 * timestamp, and the activity description (detail).
 * ============================================================
 */
session_start();
$required_role = 'SysAdmin';
require __DIR__ . '/auth_check.php';

$mysqli = require __DIR__ . '/db.php';
require_once __DIR__ . '/log_activity.php';
require_once __DIR__ . '/ReportPDF.php';

try {
    // Cap at the most recent 1000 events so the PDF stays a reasonable size
    $sql = "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 1000";
    $result = mysqli_query($mysqli, $sql);
    if (!$result) throw new Exception(mysqli_error($mysqli));

    $rows = [];
    $i = 1;
    $totalEvents = 0;
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $i++,
            date('d M Y H:i', strtotime($r['created_at'])),
            $r['role'],
            $r['action'],
            $r['detail'],
            $r['ip_address'] ?? '-',
        ];
        $totalEvents++;
    }

    $totalAll = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as c FROM activity_log"))['c'] ?? 0;

    $pdf = new ReportPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage('L'); // Landscape for the wider detail column
    $pdf->reportTitle = 'Activity Log Report';

    $subtitle = $totalAll > 1000
        ? "Showing the {$totalEvents} most recent of {$totalAll} total events"
        : "{$totalAll} event(s) recorded in the system";

    $pdf->SectionTitle('System Activity Log', $subtitle);

    $pdf->RenderTable(
        ['#', 'Timestamp', 'Role', 'Action', 'Detail', 'IP Address'],
        [10, 30, 22, 35, 145, 28],
        $rows
    );

    log_action($mysqli, 'SysAdmin', $_SESSION['user_id'] ?? 0,
        'DOWNLOAD_REPORT', 'Downloaded Activity_Log_Report.pdf');

    $pdf->Output('D', 'Activity_Log_Report.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "<script>
        alert('Failed to generate Activity Log report: " . addslashes($e->getMessage()) . "');
        document.location='../admin/dashboard.php?tab=log';
    </script>";
    exit;
}
?>
