<?php
session_start();

if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '' && $_SESSION['role'] === 'Registrar')) {
    header("Location: ../index.php"); exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registrar/dashboard.php"); exit;
}

// Reconnect AFTER files are received — prevents "MySQL server has gone away"
$mysqli = require __DIR__ . "/db.php";
require_once __DIR__ . "/log_activity.php";

mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS grade VARCHAR(50) DEFAULT NULL");
mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS uploadedAt DATETIME DEFAULT NULL");
mysqli_query($mysqli, "ALTER TABLE certificate MODIFY COLUMN startDate DATE NULL DEFAULT NULL");
mysqli_query($mysqli, "ALTER TABLE certificate MODIFY COLUMN endDate   DATE NULL DEFAULT NULL");

// ── Tool paths (same as ocr_verify.php) ──────────────────────────────────────
$gsExe        = "C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe";
$tesseractExe = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";

// ── University short codes ────────────────────────────────────────────────────
$uniCodes = [
    'University of Nairobi'                                  => 'UON',
    'Kenyatta University'                                    => 'KU',
    'Strathmore University'                                  => 'STR',
    'Jomo Kenyatta University of Agriculture and Technology' => 'JKUAT',
    'Moi University'                                         => 'MU',
    'Egerton University'                                     => 'EGU',
    'Maseno University'                                      => 'MSN',
    'Daystar University'                                     => 'DAY',
    'United States International University Africa'          => 'USIU',
    'Catholic University of East Africa'                     => 'CUEA',
];

// ── Fetch registrar's own record ──────────────────────────────────────────────
$regId     = (int)$_SESSION['user_id'];
$regResult = mysqli_query($mysqli, "SELECT * FROM registrar WHERE id='$regId' LIMIT 1");
$registrar = mysqli_fetch_assoc($regResult);
if (!$registrar) {
    $_SESSION['batch_upload_results'] = [['index'=>0,'file'=>'—','status'=>'ERROR',
        'message'=>'Could not find your registrar account. Please log out and back in.',
        'student'=>'—','cert_id'=>'—']];
    header("Location: ../registrar/dashboard.php?tab=batch"); exit;
}
$universityName = $registrar['universityName'];
$uniCode        = $uniCodes[$universityName] ?? strtoupper(substr($universityName, 0, 3));

// ── Helper: generate unique certID ───────────────────────────────────────────
function generateCertID($mysqli, $universityName, $uniCode, $year) {
    $uniEsc = mysqli_real_escape_string($mysqli, $universityName);
    $count  = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT COUNT(*) as c FROM certificate WHERE universityName='$uniEsc'"))['c'] + 1;
    $certID = "CV-{$year}-{$uniCode}-" . str_pad($count, 5, "0", STR_PAD_LEFT);
    while (true) {
        $cEsc = mysqli_real_escape_string($mysqli, $certID);
        if (mysqli_num_rows(mysqli_query($mysqli,
            "SELECT id FROM certificate WHERE certID='$cEsc' LIMIT 1")) === 0) break;
        $count++;
        $certID = "CV-{$year}-{$uniCode}-" . str_pad($count, 5, "0", STR_PAD_LEFT);
    }
    return $certID;
}

// ── Helper: run Ghostscript + Tesseract OCR on a PDF, return raw text ─────────
function runOCR($pdfTmpPath, $gsExe, $tesseractExe) {
    $tempDir = sys_get_temp_dir();
    $uid     = uniqid('cv_batch_');
    $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $uid . ".pdf";
    $imgFile = $tempDir . DIRECTORY_SEPARATOR . $uid . "_page.png";
    $txtBase = $tempDir . DIRECTORY_SEPARATOR . $uid . "_ocr";
    $txtFile = $txtBase . ".txt";

    copy($pdfTmpPath, $pdfPath);

    // Step 1: PDF → PNG via Ghostscript (300dpi, page 1 only)
    $cmd = "\"$gsExe\" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 "
         . "-dFirstPage=1 -dLastPage=1 "
         . "-sOutputFile=\"$imgFile\" \"$pdfPath\" 2>&1";
    exec($cmd, $gsOut, $gsCode);

    if ($gsCode !== 0 || !file_exists($imgFile)) {
        @unlink($pdfPath);
        return ['text'=>'', 'error'=>'Ghostscript failed: '.implode(' ',$gsOut)];
    }

    // Step 2: PNG → text via Tesseract
    exec("\"$tesseractExe\" \"$imgFile\" \"$txtBase\" 2>&1", $tOut, $tCode);

    $text = '';
    if (file_exists($txtFile)) {
        $text = file_get_contents($txtFile);
    }

    @unlink($pdfPath); @unlink($imgFile); @unlink($txtFile);

    if ($tCode !== 0 || trim($text) === '') {
        return ['text'=>'', 'error'=>'Tesseract OCR failed: '.implode(' ',$tOut)];
    }

    return ['text'=>$text, 'error'=>''];
}

// ── Helper: parse OCR text into certificate fields ────────────────────────────
// Works for CertsVerify-format PDFs. Each field falls back to '' if not found.
function parseCertText($text) {
    // Normalise: collapse multiple spaces/newlines but keep line breaks for parsing
    $lines = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n/', $text)),
        fn($l) => $l !== ''
    ));

    // Also build a single normalised string for regex fallbacks
    $flat = preg_replace('/\s+/', ' ', $text);

    $f = [
        'student_name' => '',
        'admission_no' => '',
        'course'       => '',
        'grade'        => '',
        'start_date'   => '',
        'end_date'     => '',
    ];

    $gradeMap = [
        'FIRST CLASS HONOURS'                   => 'First Class Honours',
        'SECOND CLASS HONOURS (UPPER DIVISION)' => 'Second Class Honours (Upper Division)',
        'SECOND CLASS HONOURS (LOWER DIVISION)' => 'Second Class Honours (Lower Division)',
        'PASS'                                  => 'Pass',
        'DISTINCTION'                           => 'Distinction',
        'CREDIT'                                => 'Credit',
        'MERIT'                                 => 'Merit',
    ];

    foreach ($lines as $i => $line) {

        // Student name — line immediately after "This is to certify that"
        if (stripos($line, 'certify that') !== false && isset($lines[$i+1])) {
            $candidate = $lines[$i+1];
            // Must look like a name (not a label line)
            if (!preg_match('/admission|number|degree|university|certify/i', $candidate)) {
                $f['student_name'] = $candidate;
            }
        }

        // Admission number — line containing "Admission Number:"
        if (preg_match('/Admission\s*Number\s*[:\-]?\s*(.+)/i', $line, $m)) {
            $f['admission_no'] = trim($m[1]);
        }

        // Course — line after "requirements for the degree of"
        if (preg_match('/requirements for the degree of/i', $line) && isset($lines[$i+1])) {
            $course = trim($lines[$i+1]);
            // Wrap: some long courses split across two OCR lines
            if (isset($lines[$i+2])
                && !preg_match('/classification|admission|certify|period|jeremiah|registrar/i', $lines[$i+2])
                && strlen($lines[$i+2]) > 4) {
                $course .= ' ' . trim($lines[$i+2]);
            }
            $f['course'] = $course;
        }

        // Grade — line after "with the classification of"
        if (preg_match('/with the classification of/i', $line) && isset($lines[$i+1])) {
            $raw = strtoupper(trim($lines[$i+1]));
            // OCR sometimes adds stray chars — clean and match
            $clean = preg_replace('/[^A-Z\s()]/', '', $raw);
            $clean = trim(preg_replace('/\s+/', ' ', $clean));
            $f['grade'] = $gradeMap[$clean] ?? ucwords(strtolower($raw));
        }

        // Period of study — line after "Period of Study"
        if (preg_match('/Period\s*of\s*Study/i', $line) && isset($lines[$i+1])) {
            // OCR may render em-dash as —, –, or just - or even extra spaces
            $period = $lines[$i+1];
            $parts  = preg_split('/\s*[—–\-]{1,3}\s*/', $period, 2);
            if (count($parts) === 2) {
                $f['start_date'] = convertDate(trim($parts[0]));
                $f['end_date']   = convertDate(trim($parts[1]));
            }
        }
    }

    // Regex fallbacks on flat text for anything still missing
    if ($f['admission_no'] === '') {
        // Typical Kenyan format: UON/CS/2020/001 or EGU-BUS-2019-047
        if (preg_match('/\b([A-Z]{2,6}[\/\-][A-Z]{2,4}[\/\-]\d{4}[\/\-]\d{2,4})\b/', $flat, $m)) {
            $f['admission_no'] = $m[1];
        }
    }

    if ($f['start_date'] === '' || $f['end_date'] === '') {
        // Look for two dates like "01 September 2022" and "30 June 2026"
        preg_match_all('/\d{1,2}\s+(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}/i', $flat, $dm);
        if (count($dm[0]) >= 2) {
            $f['start_date'] = convertDate($dm[0][0]);
            $f['end_date']   = convertDate($dm[0][1]);
        }
    }

    return $f;
}

// Convert "01 September 2022" → "2022-09-01" for MySQL DATE column
function convertDate($str) {
    $str = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $str));
    $ts  = strtotime($str);
    return ($ts && $ts > 0) ? date('Y-m-d', $ts) : '';
}

// ── Normalise PHP's jumbled $_FILES array ─────────────────────────────────────
$files = [];
if (!empty($_FILES['cert_files']['name'])) {
    foreach ($_FILES['cert_files']['name'] as $i => $name) {
        $files[] = [
            'name'     => $name,
            'tmp_name' => $_FILES['cert_files']['tmp_name'][$i],
            'error'    => $_FILES['cert_files']['error'][$i],
            'size'     => $_FILES['cert_files']['size'][$i],
        ];
    }
}

$total      = count($files);
$results    = [];
$year       = date('Y');
$uploadsDir = realpath(__DIR__ . "/../uploads") . DIRECTORY_SEPARATOR;
$finfo      = new finfo(FILEINFO_MIME_TYPE);

if ($total === 0) {
    $_SESSION['batch_upload_results'] = [['index'=>0,'file'=>'—','status'=>'ERROR',
        'message'=>'No files were received.','student'=>'—','cert_id'=>'—']];
    header("Location: ../registrar/dashboard.php?tab=batch"); exit;
}
if ($total > 20) {
    $_SESSION['batch_upload_results'] = [['index'=>0,'file'=>'—','status'=>'ERROR',
        'message'=>'Maximum 20 files allowed per batch.','student'=>'—','cert_id'=>'—']];
    header("Location: ../registrar/dashboard.php?tab=batch"); exit;
}

if (!file_exists($gsExe)) {
    $_SESSION['batch_upload_results'] = [['index'=>0,'file'=>'—','status'=>'ERROR',
        'message'=>'Ghostscript not found at: '.$gsExe.' — check the path in registrar_batch_upload.php.',
        'student'=>'—','cert_id'=>'—']];
    header("Location: ../registrar/dashboard.php?tab=batch"); exit;
}
if (!file_exists($tesseractExe)) {
    $_SESSION['batch_upload_results'] = [['index'=>0,'file'=>'—','status'=>'ERROR',
        'message'=>'Tesseract not found at: '.$tesseractExe.' — check the path in registrar_batch_upload.php.',
        'student'=>'—','cert_id'=>'—']];
    header("Location: ../registrar/dashboard.php?tab=batch"); exit;
}

// ── Process each file ─────────────────────────────────────────────────────────
foreach ($files as $idx => $file) {
    $fileNum  = $idx + 1;
    $origName = $file['name'];

    // Upload error check
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'Upload error (code '.$file['error'].').',
            'student'=>'—','cert_id'=>'—'];
        continue;
    }

    // Size check (5MB)
    if ($file['size'] > 5242880) {
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'File exceeds 5MB limit.',
            'student'=>'—','cert_id'=>'—'];
        continue;
    }

    // MIME check
    if ($finfo->file($file['tmp_name']) !== 'application/pdf') {
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'Only PDF files are allowed.',
            'student'=>'—','cert_id'=>'—'];
        continue;
    }

    // ── OCR: PDF → image → text ───────────────────────────────────────────────
    $ocr = runOCR($file['tmp_name'], $gsExe, $tesseractExe);
    if ($ocr['error'] !== '') {
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'OCR failed: '.$ocr['error'],
            'student'=>'—','cert_id'=>'—'];
        continue;
    }

    // ── Parse extracted text into fields ──────────────────────────────────────
    $parsed          = parseCertText($ocr['text']);
    $studentName     = $parsed['student_name'];
    $admissionNumber = $parsed['admission_no'];
    $courseName      = $parsed['course'];
    $grade           = $parsed['grade'];
    $startDate       = $parsed['start_date'];
    $endDate         = $parsed['end_date'];

    // Duplicate admission number check
    if ($admissionNumber !== '') {
        $admEsc = mysqli_real_escape_string($mysqli, $admissionNumber);
        $dup    = mysqli_query($mysqli, "SELECT id FROM certificate WHERE admissionNumber='$admEsc' LIMIT 1");
        if (mysqli_num_rows($dup) > 0) {
            $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'SKIPPED',
                'message'=>"Admission number '$admissionNumber' already exists — skipped.",
                'student'=>$studentName,'cert_id'=>'—'];
            continue;
        }
    }

    // ── Save PDF file ─────────────────────────────────────────────────────────
    $baseName = preg_replace('/[^\w\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $dest     = $uploadsDir . $baseName . ".pdf";
    $n = 1;
    while (file_exists($dest)) { $dest = $uploadsDir . $baseName . "($n).pdf"; $n++; }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'Could not save file to uploads folder.',
            'student'=>$studentName,'cert_id'=>'—'];
        continue;
    }

    // ── Generate certID and INSERT with all extracted fields ──────────────────
    $certID = generateCertID($mysqli, $universityName, $uniCode, $year);
    $uniEsc = mysqli_real_escape_string($mysqli, $universityName);
    $snEsc  = mysqli_real_escape_string($mysqli, $studentName);
    $admEsc = mysqli_real_escape_string($mysqli, $admissionNumber);
    $cnEsc  = mysqli_real_escape_string($mysqli, $courseName);
    $grEsc  = mysqli_real_escape_string($mysqli, $grade);
    $sdEsc  = mysqli_real_escape_string($mysqli, $startDate);
    $edEsc  = mysqli_real_escape_string($mysqli, $endDate);
    $fnEsc  = mysqli_real_escape_string($mysqli, $baseName);
    $cidEsc = mysqli_real_escape_string($mysqli, $certID);

    $sdSql = $sdEsc !== '' ? "'$sdEsc'" : 'NULL';
    $edSql = $edEsc !== '' ? "'$edEsc'" : 'NULL';

    $sql = "INSERT INTO certificate
                (certID, studentNames, admissionNumber, universityName, courseName,
                 grade, startDate, endDate, uploadedAt, fileName, registrar_id)
            VALUES
                ('$cidEsc','$snEsc','$admEsc','$uniEsc','$cnEsc',
                 '$grEsc', $sdSql, $edSql, NOW(), '$fnEsc', $regId)";

    if (mysqli_query($mysqli, $sql)) {
        // Flag any fields that OCR couldn't read
        $missing = [];
        if ($studentName     === '') $missing[] = 'student name';
        if ($admissionNumber === '') $missing[] = 'admission number';
        if ($courseName      === '') $missing[] = 'course';
        if ($grade           === '') $missing[] = 'grade';
        if ($startDate       === '') $missing[] = 'start date';
        if ($endDate         === '') $missing[] = 'end date';

        $note = empty($missing)
            ? 'All fields extracted and saved successfully.'
            : 'Uploaded — could not read: '.implode(', ', $missing).'. Use Edit to complete.';

        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'OK',
            'message'=>$note,
            'student'=>$studentName ?: '(unread)','cert_id'=>$certID];
    } else {
        @unlink($dest);
        $results[] = ['index'=>$fileNum,'file'=>$origName,'status'=>'ERROR',
            'message'=>'Database error: '.mysqli_error($mysqli),
            'student'=>$studentName,'cert_id'=>'—'];
    }
}

// ── Log batch ────────────────────────────────────────────────────────────────
$ok    = count(array_filter($results, fn($r) => $r['status'] === 'OK'));
log_action($mysqli, 'Registrar', $regId, 'BATCH_UPLOAD',
    "Batch of $total file(s) — $ok uploaded with OCR field extraction.");

$_SESSION['batch_upload_results'] = $results;
header("Location: ../registrar/dashboard.php?tab=batch");
exit;
