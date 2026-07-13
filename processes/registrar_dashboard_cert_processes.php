<?php
$mysqli = require __DIR__ . "/db.php";
session_start();

// ── Ensure grade & uploadedAt columns exist on certificate table ──
mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS grade VARCHAR(50) DEFAULT NULL");
mysqli_query($mysqli, "ALTER TABLE certificate ADD COLUMN IF NOT EXISTS uploadedAt DATETIME DEFAULT NULL");

// ── One-time backfill for rows that predate this column ──
mysqli_query($mysqli, "
    UPDATE certificate
    SET uploadedAt = DATE_SUB(NOW(), INTERVAL id MINUTE)
    WHERE uploadedAt IS NULL
");

$method = $_POST["method"];

// ── University short codes for certID generation ──
$uniCodes = [
    'University of Nairobi'                                          => 'UON',
    'Kenyatta University'                                            => 'KU',
    'Strathmore University'                                          => 'STR',
    'Jomo Kenyatta University of Agriculture and Technology'         => 'JKUAT',
    'Moi University'                                                 => 'MU',
    'Egerton University'                                             => 'EGU',
    'Maseno University'                                              => 'MSN',
    'Daystar University'                                             => 'DAY',
    'United States International University Africa'                  => 'USIU',
    'Catholic University of East Africa'                             => 'CUEA',
];

// ── Helper: generate a unique certID ──
function generateCertID($mysqli, $universityName, $year, $uniCodes) {
    // Get university code — fallback to first 3 letters
    $uniCode = $uniCodes[$universityName] ?? strtoupper(substr($universityName, 0, 3));

    // Count existing certs for this university to get next number
    $countResult = mysqli_query($mysqli, "SELECT COUNT(*) as c FROM certificate WHERE universityName='$universityName'");
    $count       = mysqli_fetch_assoc($countResult)['c'] + 1;

    // Format: CV-2024-JKUAT-00001
    $certID = "CV-" . $year . "-" . $uniCode . "-" . str_pad($count, 5, "0", STR_PAD_LEFT);

    // Make sure it's unique — if taken keep incrementing
    while (true) {
        $check = mysqli_query($mysqli, "SELECT id FROM certificate WHERE certID='$certID' LIMIT 1");
        if (mysqli_num_rows($check) === 0) break;
        $count++;
        $certID = "CV-" . $year . "-" . $uniCode . "-" . str_pad($count, 5, "0", STR_PAD_LEFT);
    }

    return $certID;
}

// ════════════════════════════════════════════
// CREATE — Upload new certificate
// ════════════════════════════════════════════
if ($method === 'Create') {

    $student_names    = $_POST["student_names"];
    $admission_number = $_POST["admission_number"];
    $university_name  = $_POST["university_name"];
    $course_name      = $_POST["course_name"];
    $grade             = $_POST["grade"] ?? '';
    $start_date       = $_POST["start_date"];
    $end_date         = $_POST["end_date"];

    // Check registrar exists for this university
    $registrar_query = "SELECT * FROM registrar WHERE universityName='$university_name' LIMIT 1";
    $result          = mysqli_query($mysqli, $registrar_query);
    $registrar       = mysqli_fetch_assoc($result);

    if ($registrar) {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo "<script>alert('Post request method required!');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        if (empty($_FILES)) {
            echo "<script>alert('File input is empty! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
            echo "<script>alert('File upload error! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        if ($_FILES["file"]["size"] > 5242880) {
            echo "<script>alert('File exceeds 5MB limit! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        $finfo          = new finfo(FILEINFO_MIME_TYPE);
        $extension_type = $finfo->file($_FILES["file"]["tmp_name"]);
        $allowed        = ["application/pdf"];

        if (!in_array($_FILES["file"]["type"], $allowed)) {
            echo "<script>alert('Invalid file type! Only PDF allowed.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        // ── Duplicate checks ──────────────────────────────────────────────────
        // 1. Same admission number already in the database?
        $admEsc = mysqli_real_escape_string($mysqli, $admission_number);
        $dupAdm = mysqli_query($mysqli, "SELECT certID FROM certificate WHERE admissionNumber='$admEsc' LIMIT 1");
        if (mysqli_num_rows($dupAdm) > 0) {
            $existing = mysqli_fetch_assoc($dupAdm)['certID'];
            echo "<script>alert('Duplicate detected!\\n\\nA certificate with admission number \\\"$admission_number\\\" already exists in the system (Cert ID: $existing).\\n\\nIf you need to update this certificate, please use the Edit button on the existing record instead.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        // 2. Same student name + same course already exists?
        $snEsc  = mysqli_real_escape_string($mysqli, $student_names);
        $cnEsc  = mysqli_real_escape_string($mysqli, $course_name);
        $dupCert = mysqli_query($mysqli, "SELECT certID FROM certificate WHERE studentNames='$snEsc' AND courseName='$cnEsc' LIMIT 1");
        if (mysqli_num_rows($dupCert) > 0) {
            $existing = mysqli_fetch_assoc($dupCert)['certID'];
            echo "<script>alert('Duplicate detected!\\n\\nA certificate for \\\"$student_names\\\" in \\\"$course_name\\\" already exists in the system (Cert ID: $existing).\\n\\nIf you need to update this certificate, please use the Edit button on the existing record instead.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        // Build filename from student details
        $fileName = $student_names . "_" . $admission_number . "_" . $university_name . "_" . $course_name;
        $fileName = preg_replace("/[^\w-]/", "_", $fileName);
        $pathinfo = pathinfo($_FILES["file"]["name"]);

        // Avoid duplicate filenames
        $filename    = $fileName . "." . $pathinfo["extension"];
        $destination = realpath("../") . "/uploads/" . $filename;
        $i = 1;
        while (file_exists($destination)) {
            $filename    = $fileName . "($i)." . $pathinfo["extension"];
            $destination = realpath("../") . "/uploads/" . $filename;
            $i++;
        }

        // Generate certID
        $year   = date('Y');
        $certID = generateCertID($mysqli, $university_name, $year, $uniCodes);

        // Save file
        move_uploaded_file($_FILES["file"]["tmp_name"], $destination);

        // Insert record with certID
        $grade_esc = mysqli_real_escape_string($mysqli, $grade);
        $sql = "INSERT INTO certificate
                    (certID, studentNames, admissionNumber, universityName, courseName, grade, startDate, endDate, uploadedAt, fileName, registrar_id)
                VALUES
                    ('$certID', '$student_names', '$admission_number', '$university_name', '$course_name', '$grade_esc', '$start_date', '$end_date', NOW(), '$fileName', '{$registrar['id']}')";

        if (mysqli_query($mysqli, $sql)) {
            echo "<script>alert('Certificate uploaded successfully!\\nCertificate ID: $certID\\n\\nNote this ID — it should be printed on the physical certificate.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
        } else {
            echo "<script>alert('Database error: " . mysqli_error($mysqli) . "');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
        }

    } else {
        echo "<script>alert('There is no Registrar associated with the University chosen');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
    }
}

// ════════════════════════════════════════════
// DELETE — Remove certificate
// ════════════════════════════════════════════
if ($method === 'Delete') {
    $eid       = $_POST["id"];
    $file_name = $_POST["file_name"];

    if (unlink("C:/xampp/htdocs/CertsVerify/uploads/" . $file_name . ".pdf")) {
        mysqli_query($mysqli, "DELETE FROM certificate WHERE id='$eid'");
        echo "<script>alert('Certificate deleted successfully');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
    } else {
        echo "<script>alert('Could not delete the file. Please try again.');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
    }
}

// ════════════════════════════════════════════
// UPDATE — Edit certificate details
// ════════════════════════════════════════════
if ($method === 'Update') {
    $eid              = $_POST["id"];
    $student_names    = $_POST["student_names"];
    $admission_number = $_POST["admission_number"];
    $university_name  = $_POST["university_name"];
    $course_name      = $_POST["course_name"];
    $grade             = $_POST["grade"] ?? '';
    $start_date       = $_POST["start_date"];
    $end_date         = $_POST["end_date"];
    $file_name        = $_POST["file_name"];
    $registrar_id     = $_SESSION['user_id'];
    $grade_esc        = mysqli_real_escape_string($mysqli, $grade);

    // Check registrar exists for university
    $registrar_query = "SELECT * FROM registrar WHERE universityName='$university_name' LIMIT 1";
    $result          = mysqli_query($mysqli, $registrar_query);
    $registrar       = mysqli_fetch_assoc($result);

    if ($registrar) {
        // ── Duplicate admission number check on Update ────────────────────────
        // Allow the current record to keep its own admission number,
        // but block if another DIFFERENT record already has this number.
        $admEsc  = mysqli_real_escape_string($mysqli, $admission_number);
        $dupUpd  = mysqli_query($mysqli, "SELECT certID FROM certificate WHERE admissionNumber='$admEsc' AND id != '$eid' LIMIT 1");
        if (mysqli_num_rows($dupUpd) > 0) {
            $existing = mysqli_fetch_assoc($dupUpd)['certID'];
            echo "<script>alert('Duplicate detected!\\n\\nAdmission number \\\"$admission_number\\\" is already used by another certificate (Cert ID: $existing).\\n\\nPlease check the admission number and try again.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
            exit;
        }

        // If a new file was uploaded — replace it
        if (!empty($_FILES["file_upload"]["name"])) {
            $finfo          = new finfo(FILEINFO_MIME_TYPE);
            $extension_type = $finfo->file($_FILES["file_upload"]["tmp_name"]);

            if ($extension_type === "application/pdf") {
                $fileName    = $student_names . "_" . $admission_number . "_" . $university_name . "_" . $course_name;
                $fileName    = preg_replace("/[^\w-]/", "_", $fileName);
                $pathinfo    = pathinfo($_FILES["file_upload"]["name"]);
                $filename    = $fileName . "." . $pathinfo["extension"];
                $destination = realpath("../") . "/uploads/" . $filename;

                $i = 1;
                while (file_exists($destination)) {
                    $filename    = $fileName . "($i)." . $pathinfo["extension"];
                    $destination = realpath("../") . "/uploads/" . $filename;
                    $i++;
                }

                move_uploaded_file($_FILES["file_upload"]["tmp_name"], $destination);

                $query = mysqli_query($mysqli,
                    "UPDATE certificate SET
                        studentNames='$student_names',
                        admissionNumber='$admission_number',
                        universityName='$university_name',
                        courseName='$course_name',
                        grade='$grade_esc',
                        startDate='$start_date',
                        endDate='$end_date',
                        fileName='$fileName',
                        registrar_id='$registrar_id'
                     WHERE id='$eid'"
                );
            }
        } else {
            // No new file — just update the details
            $query = mysqli_query($mysqli,
                "UPDATE certificate SET
                    studentNames='$student_names',
                    admissionNumber='$admission_number',
                    universityName='$university_name',
                    courseName='$course_name',
                    grade='$grade_esc',
                    startDate='$start_date',
                    endDate='$end_date',
                    registrar_id='$registrar_id'
                 WHERE id='$eid'"
            );
        }

        echo "<script>alert('Certificate updated successfully');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
    } else {
        echo "<script>alert('There is no Registrar associated with the University chosen');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/registrar/dashboard.php'; </script>";
    }
}
?>
