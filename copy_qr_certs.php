<?php
/*
 * ============================================================
 * copy_qr_certs.php — Copy QR-fixed certs into uploads/
 * ============================================================
 * Run ONCE at:
 *   http://localhost/CertsVerify/copy_qr_certs.php
 *
 * What it does:
 *   For each certificate in the database, it finds the
 *   matching PDF in certs_clickable/ (which has the correct
 *   plain-text QR code), then copies it into uploads/ using
 *   the secure_filename so serve_cert.php serves it correctly.
 *
 * DELETE THIS FILE immediately after running.
 * ============================================================
 */

$mysqli = require 'processes/db.php';

$clickable_dir = __DIR__ . '/certs_clickable/';
$uploads_dir   = __DIR__ . '/uploads/';

$copied  = 0;
$skipped = 0;
$errors  = [];

$result = mysqli_query($mysqli, "SELECT certID, fileName, secure_filename FROM certificate");

while ($row = mysqli_fetch_assoc($result)) {
    $certID          = $row['certID'];
    $fileName        = $row['fileName'];
    $secure_filename = $row['secure_filename'];

    if (empty($secure_filename)) {
        $errors[] = "No secure_filename in DB for certID: $certID";
        continue;
    }

    // The certs_clickable file is named like: Collins_Wangari_CV-2019-MU-00001.pdf
    // fileName in DB is: Collins_Wangari_CV-2019-MU-00001 (no extension)
    $source = $clickable_dir . $fileName . '.pdf';
    $dest   = $uploads_dir . $secure_filename;

    if (!file_exists($source)) {
        // Try finding the file by certID pattern in case fileName differs
        $found = false;
        foreach (scandir($clickable_dir) as $f) {
            if (strpos($f, $certID) !== false && substr($f, -4) === '.pdf') {
                $source = $clickable_dir . $f;
                $found  = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "Source file not found for certID: $certID (looked for: $fileName.pdf)";
            continue;
        }
    }

    // Copy (overwrite) into uploads/ under the secure name
    if (copy($source, $dest)) {
        $copied++;
    } else {
        $errors[] = "Failed to copy: $certID → $secure_filename";
    }
}

// Output results
echo "<style>
    body { font-family: sans-serif; padding: 30px; background: #f8faff; }
    h2   { color: #1e3a5f; }
    .ok  { color: #059669; }
    .err { color: #dc2626; }
    .skip{ color: #d97706; }
    li   { margin-bottom: 4px; }
</style>";

echo "<h2>CertsVerify — QR Cert Copy Results</h2>";
echo "<p class='ok'>✅ Copied into uploads/: <strong>$copied</strong> files</p>";
echo "<p class='skip'>⏭ Skipped: <strong>$skipped</strong></p>";

if (!empty($errors)) {
    echo "<p class='err'>❌ Errors (" . count($errors) . "):</p><ul>";
    foreach ($errors as $e) echo "<li class='err'>$e</li>";
    echo "</ul>";
} else {
    echo "<p class='ok'>No errors — all certs copied successfully!</p>";
}

echo "<br><p style='color:#dc2626; font-weight:700; font-size:16px;'>
      ⚠️ DELETE THIS FILE NOW: copy_qr_certs.php</p>";
?>
