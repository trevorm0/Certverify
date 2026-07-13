<?php
session_start();

if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '' && $_SESSION['role'] === 'Employer')) {
    header("Location: ../index.php");
    exit;
}

$mysqli = require __DIR__ . "/db.php";
require_once __DIR__ . "/log_activity.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_FILES["cert_files"])) {
    header("Location: ../employer/dashboard.php");
    exit;
}

$gsExe        = "C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe";
$tesseractExe = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";

// ── Helper: run single cert through OCR + matching ──────────────────────────
function verify_single_cert($filePath, $mysqli, $gsExe, $tesseractExe) {

    $tempDir = sys_get_temp_dir();
    $pdfPath = $tempDir . DIRECTORY_SEPARATOR . "cv_batch_" . uniqid() . ".pdf";
    $imgFile = $pdfPath . "_page.png";
    $txtFile = $pdfPath . "_ocr";

    copy($filePath, $pdfPath);

    // Ghostscript: PDF → image
    $cmd = "\"$gsExe\" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"$imgFile\" \"$pdfPath\" 2>&1";
    exec($cmd, $out, $rc);

    if ($rc !== 0 || !file_exists($imgFile)) {
        @unlink($pdfPath);
        return ['verdict' => 'ERROR', 'confidence' => '0%',
                'message' => 'Could not process PDF — ensure it is not encrypted.',
                'checks' => [], 'details' => []];
    }

    // Tesseract: image → text
    exec("\"$tesseractExe\" \"$imgFile\" \"$txtFile\" 2>&1", $ocrOut, $ocrRc);
    $ocrTextFile = $txtFile . ".txt";

    if ($ocrRc !== 0 || !file_exists($ocrTextFile)) {
        @unlink($pdfPath); @unlink($imgFile);
        return ['verdict' => 'ERROR', 'confidence' => '0%',
                'message' => 'OCR failed: ' . implode(' ', $ocrOut),
                'checks' => [], 'details' => []];
    }

    $extractedText  = file_get_contents($ocrTextFile);
    $normalizedText = strtolower(preg_replace('/\s+/', ' ', $extractedText));

    @unlink($pdfPath); @unlink($imgFile); @unlink($ocrTextFile);

    // Name-first matching
    $allCerts    = mysqli_query($mysqli, "SELECT * FROM certificate");
    $matchedCert = null;
    while ($cert = mysqli_fetch_assoc($allCerts)) {
        $nameParts = explode(" ", strtolower(trim($cert['studentNames'])));
        $nameFound = 0;
        foreach ($nameParts as $part) {
            if (strlen($part) > 2 && str_contains($normalizedText, $part)) $nameFound++;
        }
        if ($nameFound >= count($nameParts)) { $matchedCert = $cert; break; }
    }

    if (!$matchedCert) {
        return ['verdict' => 'NOT FOUND', 'confidence' => '0%',
                'message' => 'No matching record found in the database.',
                'checks' => [], 'details' => []];
    }

    // Field scoring
    $checksPerformed = [];
    $score           = 0;

    $checksPerformed[] = "Student name matched"; $score += 3;

    $uniWords = explode(" ", strtolower($matchedCert['universityName']));
    $uniFound = 0;
    foreach ($uniWords as $word) { if (strlen($word) > 3 && str_contains($normalizedText, $word)) $uniFound++; }
    if ($uniFound >= max(1, count($uniWords) - 2)) { $score += 2; $checksPerformed[] = "University name matched"; }

    $admNo = strtolower(trim($matchedCert['admissionNumber']));
    if ($admNo !== '' && str_contains($normalizedText, $admNo)) { $score += 2; $checksPerformed[] = "Admission number matched"; }

    $courseWords = explode(" ", strtolower($matchedCert['courseName']));
    $courseFound = 0;
    foreach ($courseWords as $word) { if (strlen($word) > 4 && str_contains($normalizedText, $word)) $courseFound++; }
    if ($courseFound >= 2) { $score += 2; $checksPerformed[] = "Course name matched"; }

    $dbCertID      = strtolower(trim($matchedCert['certID'] ?? ''));
    $certIDMatched = false;
    if ($dbCertID !== '') {
        if (str_contains($normalizedText, $dbCertID)) {
            $score += 3; $certIDMatched = true; $checksPerformed[] = "Certificate ID matched";
        } else {
            $score -= 5; $checksPerformed[] = "⚠ Certificate ID not found";
        }
    }

    $isLegitimate = ($score >= 10 && count($checksPerformed) >= 4 && $certIDMatched);

    if ($isLegitimate) {
        $verdict    = 'LEGITIMATE';
        $confidence = min(99, round(($score / 12) * 100)) . "%";
        $message    = "Certificate matches records for " . $matchedCert['studentNames'] . " from " . $matchedCert['universityName'] . ".";
    } else {
        $verdict    = 'SUSPICIOUS';
        $confidence = max(0, round(($score / 12) * 100)) . "%";
        $message    = !$certIDMatched && $dbCertID !== ''
            ? "Certificate ID does not match. This certificate may have been altered."
            : "Some details matched but the certificate could not be fully verified.";
    }

    return [
        'verdict'    => $verdict,
        'confidence' => $confidence,
        'message'    => $message,
        'checks'     => $checksPerformed,
        'details'    => [
            'name'       => $matchedCert['studentNames'],
            'admission'  => $matchedCert['admissionNumber'],
            'university' => $matchedCert['universityName'],
            'course'     => $matchedCert['courseName'],
            'cert_id'    => $matchedCert['certID'] ?? '—',
        ]
    ];
}

// ── Process each uploaded file ───────────────────────────────────────────────
$files       = $_FILES['cert_files'];
$fileCount   = count($files['name']);
$batchResults = [];

if (!file_exists($gsExe)) {
    $_SESSION['batch_results'] = [['file' => 'N/A', 'verdict' => 'ERROR',
        'confidence' => '0%', 'message' => 'Ghostscript not found on this server.', 'checks' => [], 'details' => []]];
    header("Location: ../employer/dashboard.php?tab=batch");
    exit;
}

for ($i = 0; $i < $fileCount; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        $batchResults[] = ['file' => $files['name'][$i], 'verdict' => 'ERROR',
            'confidence' => '0%', 'message' => 'Upload failed for this file.', 'checks' => [], 'details' => []];
        continue;
    }

    // Validate PDF
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($files['tmp_name'][$i]);
    if ($mime !== 'application/pdf') {
        $batchResults[] = ['file' => $files['name'][$i], 'verdict' => 'ERROR',
            'confidence' => '0%', 'message' => 'Not a valid PDF file.', 'checks' => [], 'details' => []];
        continue;
    }
    if ($files['size'][$i] > 5242880) {
        $batchResults[] = ['file' => $files['name'][$i], 'verdict' => 'ERROR',
            'confidence' => '0%', 'message' => 'File exceeds 5MB limit.', 'checks' => [], 'details' => []];
        continue;
    }

    $result         = verify_single_cert($files['tmp_name'][$i], $mysqli, $gsExe, $tesseractExe);
    $result['file'] = $files['name'][$i];
    $batchResults[] = $result;
}

$_SESSION['batch_results'] = $batchResults;

// Log the batch verification
$verified_count = count(array_filter($batchResults, fn($r) => $r['verdict'] === 'LEGITIMATE'));
$total_count    = count($batchResults);
log_action($mysqli, $_SESSION['role'] ?? 'Employer', $_SESSION['user_id'] ?? 0,
    'BATCH_VERIFY', "Batch of $total_count cert(s) — $verified_count verified. Files: " .
    implode(', ', array_column($batchResults, 'file')));

// Save each result to employer verification history
if (($_SESSION['role'] ?? '') === 'Employer' && !empty($_SESSION['user_id'])) {
    $emp_id = (int)$_SESSION['user_id'];
    foreach ($batchResults as $r) {
        $cid  = mysqli_real_escape_string($mysqli, $r['details']['cert_id']    ?? '');
        $sn   = mysqli_real_escape_string($mysqli, $r['details']['name']       ?? $r['file']);
        $uni  = mysqli_real_escape_string($mysqli, $r['details']['university'] ?? '');
        $crs  = mysqli_real_escape_string($mysqli, $r['details']['course']     ?? '');
        $verd = mysqli_real_escape_string($mysqli, $r['verdict']    ?? '');
        $conf = mysqli_real_escape_string($mysqli, $r['confidence'] ?? '');
        mysqli_query($mysqli, "INSERT INTO verif_history (employer_id,cert_id,student_name,university,course,verdict,confidence,method,created_at)
            VALUES ($emp_id,'$cid','$sn','$uni','$crs','$verd','$conf','batch',NOW())");
    }
}

header("Location: ../employer/dashboard.php?tab=batch");
exit;
?>
