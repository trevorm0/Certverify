<?php
/*
 * ============================================================
 * rename_pdfs.php — Rename existing PDFs to secure filenames
 * ============================================================
 * Run ONCE at:
 * http://localhost/CertsVerify/rename_pdfs.php
 *
 * This script:
 * 1. Reads every certificate from the database
 * 2. Finds the old PDF file in uploads/
 * 3. Renames it to the secure_filename (random hash)
 * 4. Updates the database record
 *
 * DELETE THIS FILE immediately after running.
 * ============================================================
 */

$mysqli = require 'processes/db.php';

$uploads_dir = __DIR__ . '/uploads/';
$renamed     = 0;
$skipped     = 0;
$errors      = [];

$result = mysqli_query($mysqli, "SELECT * FROM certificate");

while ($row = mysqli_fetch_assoc($result)) {
    $old_name    = $row['fileName'];       // e.g. Alex_John_CV-2024-STR-00001
    $secure_name = $row['secure_filename']; // e.g. a3f9b2c1...pdf

    if (empty($secure_name)) {
        $errors[] = "No secure filename for certID: {$row['certID']}";
        continue;
    }

    // Old file path — try with .pdf extension
    $old_path = $uploads_dir . $old_name . '.pdf';
    $new_path = $uploads_dir . $secure_name;

    // Skip if already renamed (new file exists)
    if (file_exists($new_path)) {
        $skipped++;
        continue;
    }

    // Rename on disk
    if (file_exists($old_path)) {
        if (rename($old_path, $new_path)) {
            $renamed++;
        } else {
            $errors[] = "Could not rename: $old_name → $secure_name";
        }
    } else {
        $errors[] = "Old file not found: $old_path";
        $skipped++;
    }
}

// Show results
echo "<style>body{font-family:sans-serif;padding:30px;background:#f8faff;}
      h2{color:#1e3a5f;} .ok{color:#059669;} .err{color:#dc2626;} .skip{color:#d97706;}</style>";

echo "<h2>CertsVerify — PDF Rename Results</h2>";
echo "<p class='ok'>✅ Renamed: <strong>$renamed</strong> files</p>";
echo "<p class='skip'>⏭ Skipped (already renamed): <strong>$skipped</strong> files</p>";

if (!empty($errors)) {
    echo "<p class='err'>❌ Errors:</p><ul>";
    foreach ($errors as $e) {
        echo "<li class='err'>$e</li>";
    }
    echo "</ul>";
}

echo "<br><p style='color:#dc2626;font-weight:700;font-size:16px;'>
      ⚠️ DELETE THIS FILE NOW: rename_pdfs.php</p>";
?>
