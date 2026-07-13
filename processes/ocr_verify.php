<?php
session_start();

// ── Access check ──
if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '')) {
    header("Location: ../index.php");
    exit;
}

$mysqli = require __DIR__ . "/db.php";
require_once __DIR__ . "/log_activity.php";

// ── Only accept POST with a file ──
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_FILES["cert_file"])) {
    header("Location: ../index.php");
    exit;
}

// ══════════════════════════════════════════════
// GRADUATE OWNERSHIP ENFORCEMENT
//
// If the user is a Graduate, we read their
// admission number from the database and pass it
// as a hard constraint into the matching logic.
// A graduate CANNOT verify someone else's cert.
// ══════════════════════════════════════════════
$userRole            = $_SESSION['role'] ?? '';
$expectedAdmission   = '';   // only set for graduates
$graduateOwnershipOn = false;

if ($userRole === 'Graduate') {
    // Get admission number straight from DB — do not trust POST data
    $gradQ    = "SELECT admissionNumber FROM graduate WHERE id='" . $_SESSION['user_id'] . "' LIMIT 1";
    $gradRes  = mysqli_query($mysqli, $gradQ);
    $gradData = mysqli_fetch_assoc($gradRes);

    $expectedAdmission   = trim($gradData['admissionNumber'] ?? '');
    $graduateOwnershipOn = true;
}

// ══════════════════════════════════════════════
// STEP 1: Validate uploaded file
// ══════════════════════════════════════════════
$file = $_FILES["cert_file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'File upload failed. Please try again.',
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file["tmp_name"]);
if ($mime !== "application/pdf") {
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'Only PDF files are accepted.',
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

if ($file["size"] > 5242880) {
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'File is too large. Maximum size is 5MB.',
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

// ══════════════════════════════════════════════
// STEP 2: Save PDF to temp folder
// ══════════════════════════════════════════════
$tempDir = sys_get_temp_dir();
$pdfPath = $tempDir . DIRECTORY_SEPARATOR . "cv_ocr_" . uniqid() . ".pdf";
$imgFile = $pdfPath . "_page.png";
$txtFile = $pdfPath . "_ocr";

move_uploaded_file($file["tmp_name"], $pdfPath);

// ══════════════════════════════════════════════
// STEP 3: Convert PDF → image via Ghostscript
// ══════════════════════════════════════════════
$gsExe = "C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe";

if (!file_exists($gsExe)) {
    @unlink($pdfPath);
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'Ghostscript not found.',
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

$cmd = "\"$gsExe\" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"$imgFile\" \"$pdfPath\" 2>&1";
exec($cmd, $output, $returnCode);

if ($returnCode !== 0 || !file_exists($imgFile)) {
    @unlink($pdfPath);
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'Could not process the PDF. Make sure the file is not encrypted.',
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

// ══════════════════════════════════════════════
// STEP 4: Run Tesseract OCR
// ══════════════════════════════════════════════
$tesseractExe = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";
exec("\"$tesseractExe\" \"$imgFile\" \"$txtFile\" 2>&1", $ocrOutput, $ocrReturn);

$ocrTextFile = $txtFile . ".txt";

if ($ocrReturn !== 0 || !file_exists($ocrTextFile)) {
    @unlink($pdfPath);
    @unlink($imgFile);
    $_SESSION['ocr_result'] = [
        'verdict' => 'ERROR',
        'message' => 'OCR processing failed. Error: ' . implode(' ', $ocrOutput),
        'details' => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

$extractedText = file_get_contents($ocrTextFile);

@unlink($pdfPath);
@unlink($imgFile);
@unlink($ocrTextFile);

// ══════════════════════════════════════════════
// STEP 5: Normalize OCR text
// ══════════════════════════════════════════════
$normalizedText = strtolower(preg_replace('/\s+/', ' ', $extractedText));

// ══════════════════════════════════════════════
// STEP 6: GRADUATE OWNERSHIP PRE-CHECK
//
// Before doing any name matching, verify that
// the graduate's OWN admission number appears
// in the uploaded document.
//
// If it doesn't — reject immediately.
// This means a graduate CANNOT upload someone
// else's certificate and get it verified.
// ══════════════════════════════════════════════
if ($graduateOwnershipOn && $expectedAdmission !== '') {
    $admInDoc = str_contains($normalizedText, strtolower($expectedAdmission));

    if (!$admInDoc) {
        $_SESSION['ocr_result'] = [
            'verdict'    => 'ACCESS DENIED',
            'confidence' => '0%',
            'message'    => 'The admission number on this document does not match your account ('
                          . $expectedAdmission
                          . '). You can only verify your own certificate.',
            'checks'     => [],
            'extracted'  => substr($extractedText, 0, 300),
            'details'    => []
        ];
        header("Location: ../verify_result.php");
        exit;
    }
}

// ══════════════════════════════════════════════
// STEP 7: NAME-FIRST MATCHING
// ══════════════════════════════════════════════
$allCerts    = mysqli_query($mysqli, "SELECT * FROM certificate");
$matchedCert = null;

while ($cert = mysqli_fetch_assoc($allCerts)) {
    $nameParts = explode(" ", strtolower(trim($cert['studentNames'])));
    $nameFound = 0;

    foreach ($nameParts as $part) {
        if (strlen($part) > 2 && str_contains($normalizedText, $part)) {
            $nameFound++;
        }
    }

    if ($nameFound >= count($nameParts)) {
        $matchedCert = $cert;
        break;
    }
}

if (!$matchedCert) {
    $_SESSION['ocr_result'] = [
        'verdict'    => 'NOT FOUND',
        'confidence' => '0%',
        'message'    => 'No matching certificate record was found in our database.',
        'checks'     => [],
        'extracted'  => substr($extractedText, 0, 300),
        'details'    => []
    ];
    header("Location: ../verify_result.php");
    exit;
}

// ══════════════════════════════════════════════
// STEP 8: GRADUATE OWNERSHIP POST-CHECK
//
// Double-check: the matched certificate's
// admission number must match the graduate's
// own admission number from their account.
//
// This catches edge cases where two students
// share the same name.
// ══════════════════════════════════════════════
if ($graduateOwnershipOn && $expectedAdmission !== '') {
    if (strtolower(trim($matchedCert['admissionNumber'])) !== strtolower($expectedAdmission)) {
        $_SESSION['ocr_result'] = [
            'verdict'    => 'ACCESS DENIED',
            'confidence' => '0%',
            'message'    => 'This certificate does not belong to your account. You can only verify your own certificate.',
            'checks'     => [],
            'extracted'  => substr($extractedText, 0, 300),
            'details'    => []
        ];
        header("Location: ../verify_result.php");
        exit;
    }
}

// ══════════════════════════════════════════════
// STEP 9: VERIFY REMAINING FIELDS
// ══════════════════════════════════════════════
$checksPerformed = [];
$score           = 0;

// Name matched
$checksPerformed[] = "Student name matched";
$score += 3;

// University name
$uniWords = explode(" ", strtolower($matchedCert['universityName']));
$uniFound = 0;
foreach ($uniWords as $word) {
    if (strlen($word) > 3 && str_contains($normalizedText, $word)) {
        $uniFound++;
    }
}
if ($uniFound >= max(1, count($uniWords) - 2)) {
    $score += 2;
    $checksPerformed[] = "University name matched";
}

// Admission number
$admNo = strtolower(trim($matchedCert['admissionNumber']));
if ($admNo !== '' && str_contains($normalizedText, $admNo)) {
    $score += 2;
    $checksPerformed[] = "Admission number matched";
}

// Course name
$courseWords = explode(" ", strtolower($matchedCert['courseName']));
$courseFound = 0;
foreach ($courseWords as $word) {
    if (strlen($word) > 4 && str_contains($normalizedText, $word)) {
        $courseFound++;
    }
}
if ($courseFound >= 2) {
    $score += 2;
    $checksPerformed[] = "Course name matched";
}

// ══════════════════════════════════════════════
// STEP 10: CERTIFICATE ID — HARD REQUIREMENT
// ══════════════════════════════════════════════
$dbCertID      = strtolower(trim($matchedCert['certID'] ?? ''));
$certIDMatched = false;

if ($dbCertID !== '') {
    if (str_contains($normalizedText, $dbCertID)) {
        $score        += 3;
        $certIDMatched = true;
        $checksPerformed[] = "Certificate ID matched";
    } else {
        $score        -= 5;
        $certIDMatched = false;
        $checksPerformed[] = "⚠ Certificate ID not found on document";
    }
}

// ══════════════════════════════════════════════
// STEP 11: DECIDE VERDICT
// ══════════════════════════════════════════════
$isLegitimate = (
    $score >= 10
    && count($checksPerformed) >= 4
    && $certIDMatched === true
);

if ($isLegitimate) {
    $verdict    = 'LEGITIMATE';
    $confidence = min(99, round(($score / 12) * 100)) . "%";
    $message    = "The certificate matches our records for "
                . $matchedCert['studentNames']
                . " from " . $matchedCert['universityName'] . ".";
} else {
    $verdict    = 'SUSPICIOUS';
    $confidence = max(0, round(($score / 12) * 100)) . "%";
    $message    = !$certIDMatched && $dbCertID !== ''
        ? "The Certificate ID on this document does not match our records. This certificate may have been altered or forged."
        : "Some details were found but the certificate could not be fully verified. It may have been altered.";
}

// ══════════════════════════════════════════════
// STEP 12: Store result and redirect
// ══════════════════════════════════════════════
$_SESSION['ocr_result'] = [
    'verdict'    => $verdict,
    'confidence' => $confidence,
    'message'    => $message,
    'checks'     => $checksPerformed,
    'extracted'  => substr($extractedText, 0, 300),
    'details'    => [
        'name'       => $matchedCert['studentNames'],
        'admission'  => $matchedCert['admissionNumber'],
        'university' => $matchedCert['universityName'],
        'course'     => $matchedCert['courseName'],
        'cert_id'    => $matchedCert['certID'] ?? '—',
    ]
];

// Log the verification attempt
$verdict_log = $_SESSION['ocr_result']['verdict'] ?? 'UNKNOWN';
$cert_log    = $_SESSION['ocr_result']['details']['cert_id'] ?? 'unknown';
$name_log    = $_SESSION['ocr_result']['details']['name'] ?? 'unknown';
log_action($mysqli, $_SESSION['role'] ?? 'Unknown', $_SESSION['user_id'] ?? 0,
    'CERT_VERIFY', "Result: $verdict_log | Cert: $cert_log | Student: $name_log");

// Save to employer verification history
if (($_SESSION['role'] ?? '') === 'Employer' && !empty($_SESSION['user_id'])) {
    $emp_id  = (int)$_SESSION['user_id'];
    $details = $_SESSION['ocr_result']['details'] ?? [];
    $cid  = mysqli_real_escape_string($mysqli, $details['cert_id']    ?? '');
    $sn   = mysqli_real_escape_string($mysqli, $details['name']       ?? '');
    $uni  = mysqli_real_escape_string($mysqli, $details['university'] ?? '');
    $crs  = mysqli_real_escape_string($mysqli, $details['course']     ?? '');
    $verd = mysqli_real_escape_string($mysqli, $_SESSION['ocr_result']['verdict']    ?? '');
    $conf = mysqli_real_escape_string($mysqli, $_SESSION['ocr_result']['confidence'] ?? '');
    mysqli_query($mysqli, "INSERT INTO verif_history (employer_id,cert_id,student_name,university,course,verdict,confidence,method,created_at)
        VALUES ($emp_id,'$cid','$sn','$uni','$crs','$verd','$conf','ocr',NOW())");
}

header("Location: ../verify_result.php");
exit;
?>
