<?php
session_start();

// Raise limits for large backup operations
ini_set('memory_limit',    '256M');
ini_set('max_execution_time', '300');

// Only SysAdmin can access this
if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '' && $_SESSION['role'] === 'SysAdmin')) {
    header("Location: ../admin/login.php");
    exit;
}

$mysqli = require __DIR__ . "/db.php";

// Ensure backup_log table exists
mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS backup_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    backup_type VARCHAR(30) NOT NULL,
    filename    VARCHAR(255) NOT NULL,
    file_size   VARCHAR(30) DEFAULT '',
    admin_id    INT DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$type    = $_GET['type'] ?? '';
$adminId = (int)($_SESSION['user_id'] ?? 0);

if (!in_array($type, ['sql', 'zip'])) {
    header("Location: ../admin/dashboard.php?tab=backup");
    exit;
}

// ── Helper: human-readable file size ─────────────────────────────────────────
function humanSize($bytes) {
    if ($bytes >= 1048576) return round($bytes/1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes/1024, 1)    . ' KB';
    return $bytes . ' B';
}

// ════════════════════════════════════════════════════════════
// SQL DUMP
// ════════════════════════════════════════════════════════════
if ($type === 'sql') {

    $filename = 'verifyDB_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $output   = '';

    // Header
    $output .= "-- CertsVerify Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: verifyDB\n";
    $output .= "-- -----------------------------------------------\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tables = [];
    $tRes   = mysqli_query($mysqli, "SHOW TABLES");
    while ($tRow = mysqli_fetch_row($tRes)) {
        $tables[] = $tRow[0];
    }

    foreach ($tables as $table) {
        // CREATE TABLE statement
        $createRes = mysqli_query($mysqli, "SHOW CREATE TABLE `$table`");
        $createRow = mysqli_fetch_row($createRes);
        $output   .= "-- Table: $table\n";
        $output   .= "DROP TABLE IF EXISTS `$table`;\n";
        $output   .= $createRow[1] . ";\n\n";

        // Data rows
        $dataRes = mysqli_query($mysqli, "SELECT * FROM `$table`");
        $numCols = mysqli_num_fields($dataRes);

        if (mysqli_num_rows($dataRes) > 0) {
            $output .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            while ($dataRow = mysqli_fetch_row($dataRes)) {
                $vals = [];
                for ($i = 0; $i < $numCols; $i++) {
                    if ($dataRow[$i] === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = "'" . mysqli_real_escape_string($mysqli, $dataRow[$i]) . "'";
                    }
                }
                $rows[] = '(' . implode(', ', $vals) . ')';
            }
            $output .= implode(",\n", $rows) . ";\n\n";
        }
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Log this backup
    $fnEsc   = mysqli_real_escape_string($mysqli, $filename);
    $sizeStr = humanSize(strlen($output));
    $szEsc   = mysqli_real_escape_string($mysqli, $sizeStr);
    mysqli_query($mysqli,
        "INSERT INTO backup_log (backup_type,filename,file_size,admin_id,created_at)
         VALUES ('sql','$fnEsc','$szEsc',$adminId,NOW())");

    // Stream the file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($output));
    header('Cache-Control: no-cache');
    echo $output;
    exit;
}

// ════════════════════════════════════════════════════════════
// PDF ZIP  — pure PHP, no ZipArchive extension required
// ════════════════════════════════════════════════════════════
if ($type === 'zip') {

    $uploadsDir = realpath(__DIR__ . "/../uploads");
    $filename   = 'verifyDB_certificates_' . date('Y-m-d_H-i-s') . '.zip';

    if (!$uploadsDir || !is_dir($uploadsDir)) {
        die("Uploads folder not found at: " . __DIR__ . "/../uploads");
    }

    // ── Pure-PHP ZIP builder ──────────────────────────────────────────────────
    // Builds a valid ZIP file in memory without requiring php_zip extension.
    function buildZip(array $files) {
        // $files = [ 'name_inside_zip' => 'content_string', ... ]
        $centralDir = '';
        $localParts = '';
        $offset     = 0;

        foreach ($files as $name => $data) {
            $crc    = crc32($data);
            $size   = strlen($data);
            $dosTime = dosTime(time());

            // Local file header
            $local  = "\x50\x4b\x03\x04";   // signature
            $local .= "\x14\x00";             // version needed
            $local .= "\x00\x00";             // flags
            $local .= "\x00\x00";             // compression (stored)
            $local .= pack('V', $dosTime);    // mod time+date
            $local .= pack('V', $crc);        // CRC-32
            $local .= pack('V', $size);       // compressed size
            $local .= pack('V', $size);       // uncompressed size
            $local .= pack('v', strlen($name));
            $local .= pack('v', 0);           // extra field length
            $local .= $name;
            $local .= $data;

            // Central directory entry
            $cd  = "\x50\x4b\x01\x02";       // signature
            $cd .= "\x3f\x00";                // version made by
            $cd .= "\x14\x00";               // version needed
            $cd .= "\x00\x00";               // flags
            $cd .= "\x00\x00";               // compression
            $cd .= pack('V', $dosTime);
            $cd .= pack('V', $crc);
            $cd .= pack('V', $size);
            $cd .= pack('V', $size);
            $cd .= pack('v', strlen($name));
            $cd .= pack('v', 0);             // extra
            $cd .= pack('v', 0);             // comment
            $cd .= pack('v', 0);             // disk start
            $cd .= pack('v', 0);             // int attrs
            $cd .= pack('V', 0);             // ext attrs
            $cd .= pack('V', $offset);       // local header offset
            $cd .= $name;

            $centralDir .= $cd;
            $localParts .= $local;
            $offset     += strlen($local);
        }

        $cdSize   = strlen($centralDir);
        $numFiles = count($files);

        // End of central directory record
        $eocd  = "\x50\x4b\x05\x06";
        $eocd .= "\x00\x00";                  // disk number
        $eocd .= "\x00\x00";                  // disk with CD start
        $eocd .= pack('v', $numFiles);        // entries on disk
        $eocd .= pack('v', $numFiles);        // total entries
        $eocd .= pack('V', $cdSize);          // CD size
        $eocd .= pack('V', $offset);          // CD offset
        $eocd .= "\x00\x00";                  // comment length

        return $localParts . $centralDir . $eocd;
    }

    function dosTime($unix) {
        $t = getdate($unix);
        return (($t['year'] - 1980) << 25)
             | ($t['mon']          << 21)
             | ($t['mday']         << 16)
             | ($t['hours']        << 11)
             | ($t['minutes']      << 5)
             | ($t['seconds']      >> 1);
    }

    // Collect all PDFs from uploads folder
    $fileEntries = [];
    $pdfs        = glob($uploadsDir . DIRECTORY_SEPARATOR . '*.pdf');
    $count       = 0;
    foreach ($pdfs as $pdf) {
        $fileEntries[basename($pdf)] = file_get_contents($pdf);
        $count++;
    }

    // Add README
    $readme  = "CertsVerify -- Certificate PDFs Backup\r\n";
    $readme .= "Generated: " . date('Y-m-d H:i:s') . "\r\n";
    $readme .= "Total files: $count\r\n\r\n";
    $readme .= "To restore: copy all PDF files back into CertsVerify/uploads/\r\n";
    $fileEntries['README.txt'] = $readme;

    $zipData = buildZip($fileEntries);
    $sizeStr = humanSize(strlen($zipData));

    // Log
    $fnEsc = mysqli_real_escape_string($mysqli, $filename);
    $szEsc = mysqli_real_escape_string($mysqli, $sizeStr);
    mysqli_query($mysqli,
        "INSERT INTO backup_log (backup_type,filename,file_size,admin_id,created_at)
         VALUES ('zip','$fnEsc','$szEsc',$adminId,NOW())");

    // Stream
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($zipData));
    header('Cache-Control: no-cache');
    echo $zipData;
    exit;
}
?>
