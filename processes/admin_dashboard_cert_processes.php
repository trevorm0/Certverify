<?php
$mysqli = require __DIR__ . "/db.php";
session_start();

$method = $_POST["method"];

// Add Certificate File Process
if($method === 'Create'){
    //get form values
    $student_names = $_POST["student_names"];
    $admission_number = $_POST["admission_number"];
    $university_name = $_POST["university_name"];
    $course_name = $_POST["course_name"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];

    //query registrar table by university name
    $registrar_query = "SELECT * FROM registrar WHERE universityName='$university_name' LIMIT 1";
    $result = mysqli_query($mysqli, $registrar_query);
    $registrar = mysqli_fetch_assoc($result);

    //if registrar exists
    if($registrar){
        //alert if POST method was not used in form
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo "<script>alert('Post request method required! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            exit;
        }
        
        //alert if file field is empty
        if (empty($_FILES)) {
            echo "<script>alert('File input is empty! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            exit;
        }
        
        //alert if error occurs during upload process
        if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
        
            switch ($_FILES["file"]["error"]) {
                case UPLOAD_ERR_PARTIAL:
                    echo "<script>alert('File only partially uploaded! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo "<script>alert('No file was uploaded! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo "<script>alert('File upload was stopped by a PHP extension! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    echo "<script>alert('File exceeds MAX_FILE_SIZE in php.ini! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    echo "<script>alert('File exceeds upload_max_filesize in php.ini! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo "<script>alert('Temporary file not found! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo "<script>alert('Failed to write file! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
                default:
                    echo "<script>alert('Unknown upload error! Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                    exit;
                    break;
            }
        }
        
        // Reject uploaded file larger than 1MB
        if ($_FILES["file"]["size"] > 1048576) {
            echo "<script>alert('Duplicate File Found! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            exit;
        }
        
        // Use fileinfo to get the file extension type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $extension_type = $finfo->file($_FILES["file"]["tmp_name"]);
        
        $allowed_extension_types = ["application/pdf"];
        
        //check if file has the right extension
        if ( !in_array($_FILES["file"]["type"], $allowed_extension_types)) {
            echo "<script>alert('Invalid file type! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            exit;
        }
        
        //Format file name to include graduate names, admission number & course
        $pathinfo = pathinfo($_FILES["file"]["name"]);
        $fileName = $student_names . "_" . $admission_number . "_" . $university_name . "_" . $course_name;
        $fileName = preg_replace("/[^\w-]/", "_", $fileName);

        //check if file with formatted name exists in table
        $cert_check_query = "SELECT * FROM certificate WHERE fileName='$fileName' LIMIT 1";
        $result = mysqli_query($mysqli, $cert_check_query);
        $cert = mysqli_fetch_assoc($result);

        //if file exists
        if ($cert){
            echo "<script>alert('Duplicate File Found! Please try again');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            exit;
        } else{            
            // Generate a secure random filename (hash-based, not guessable)
            $secureFilename = bin2hex(random_bytes(16)) . '.pdf';
            $destination    = realpath("../") . "/uploads/" . $secureFilename;

            //alert if error occurs when moving uploaded file
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], $destination)) {
                echo "<script>alert('Can\\'t move uploaded file! Please try again');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                exit;
            }

            //query table to insert certificate data (store both readable fileName and secure_filename)
            $sql = "INSERT INTO certificate (studentNames, admissionNumber, universityName, courseName, startDate, endDate, fileName, secure_filename, registrar_id) VALUES ('$student_names', '$admission_number', '$university_name', '$course_name', '$start_date', '$end_date', '$fileName', '$secureFilename', '{$registrar['id']}')";
            $data = mysqli_query($mysqli, $sql);
            if ($data){
                echo "<script>alert('File Successfully uploaded');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            }
            else{
                echo "<script>alert('Error! File was not uploaded: " . mysqli_error($mysqli) . "');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            }
        }
    } else{
        echo "<script>alert('There is no Registrar associated with the University chosen');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
    }
}

// Edit Certificate File Process
if($method === 'Update'){
    $eid=$_POST['id'];
    //get form values
    $student_names = $_POST["student_names"];
    $admission_number = $_POST["admission_number"];
    $university_name = $_POST["university_name"];
    $course_name = $_POST["course_name"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $file_name = $_POST["file_name"];

    //query registrar table with university name
    $registrar_query = "SELECT * FROM registrar WHERE universityName='$university_name' LIMIT 1";
    $result = mysqli_query($mysqli, $registrar_query);
    $registrar = mysqli_fetch_assoc($result);

    //if registrar exists
    if($registrar){
        //if file input is empty
        if($_FILES["file_upload"]['name'] == "") {
            //query table to update data — no new file, secure_filename stays unchanged
            $registrar_id = $registrar['id'];
            $query = mysqli_query($mysqli,
                "UPDATE certificate SET
                    studentNames='$student_names',
                    admissionNumber='$admission_number',
                    universityName='$university_name',
                    courseName='$course_name',
                    startDate='$start_date',
                    endDate='$end_date',
                    registrar_id='$registrar_id'
                 WHERE id='$eid'"
            );
            
            if ($query) {
                echo "<script>alert('You have successfully updated the Certificate information');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            } else {
                echo "<script>alert('Something Went Wrong. Please try again');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
            }

        //if file inout is not empty
        } else{
            //check if file upload has errors
            if ($_FILES["file_upload"]["error"] !== UPLOAD_ERR_OK) {
                switch ($_FILES["file"]["error"]) {
                    case UPLOAD_ERR_PARTIAL:
                        echo "<script>alert('File only partially uploaded! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        echo "<script>alert('No file was uploaded! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        echo "<script>alert('File upload was stopped by a PHP extension! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        echo "<script>alert('File exceeds MAX_FILE_SIZE in php.ini! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                        echo "<script>alert('File exceeds upload_max_filesize in php.ini! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        echo "<script>alert('Temporary file not found! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        echo "<script>alert('Failed to write file! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                    default:
                        echo "<script>alert('Unknown upload error! Please try again');</script>";
                        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                        exit;
                        break;
                }
            }
                
            // Reject uploaded file larger than 1MB
            if ($_FILES["file_upload"]["size"] > 1048576) {
                echo "<script>alert('File is bigger than 1MB! Please try again');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                exit;
            }

            // Delete old file from uploads using secure_filename if available
            $safe_eid    = mysqli_real_escape_string($mysqli, $eid);
            $oldRecord   = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT secure_filename, fileName FROM certificate WHERE id='$safe_eid' LIMIT 1"));
            $oldFile     = !empty($oldRecord['secure_filename'])
                ? $oldRecord['secure_filename']
                : $oldRecord['fileName'] . '.pdf';
            $oldPath     = realpath("../") . "/uploads/" . $oldFile;
            if (file_exists($oldPath)) unlink($oldPath);
            
            // Use fileinfo to get the extension type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $extension_type = $finfo->file($_FILES["file_upload"]["tmp_name"]);
            
            $allowed_extension_types = ["application/pdf"];
                    
            // Check if file has the right extension
            if ( !in_array($_FILES["file_upload"]["type"], $allowed_extension_types)) {
                echo "<script>alert('Invalid file type! Please try again');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                exit;
            }
            
            // Format file name to include graduates names, admission number & course name
            $pathinfo = pathinfo($_FILES["file_upload"]["name"]);
            $fileName = $student_names . "_" . $admission_number . "_" . $university_name . "_" . $course_name;
            $fileName = preg_replace("/[^\w-]/", "_", $fileName);
            // Generate new secure filename for the replacement file
            $newSecureFilename = bin2hex(random_bytes(16)) . '.pdf';
            $destination       = realpath("../") . "/uploads/" . $newSecureFilename;

            // Add a numeric suffix if somehow that hash already exists (extremely unlikely)
            $i = 1;
            while (file_exists($destination)) {
                $newSecureFilename = bin2hex(random_bytes(16)) . '.pdf';
                $destination       = realpath("../") . "/uploads/" . $newSecureFilename;
                $i++;
            }

            //alert if upload file can't be moved
            if (!move_uploaded_file($_FILES["file_upload"]["tmp_name"], $destination)) {
                echo "<script>alert('Can\\'t move uploaded file! Please try again');</script>";
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                exit;
            }

            //query registrar table with University name
            $registrar_query = "SELECT * FROM registrar WHERE universityName='$university_name' LIMIT 1";
            $result          = mysqli_query($mysqli, $registrar_query);
            $registrar       = mysqli_fetch_assoc($result);

            //if registrar exists
            if($registrar){
                //query table to update file record including new secure_filename
                $registrar_id = $registrar['id'];
                $query = mysqli_query($mysqli,
                    "UPDATE certificate SET
                        studentNames='$student_names',
                        admissionNumber='$admission_number',
                        universityName='$university_name',
                        courseName='$course_name',
                        startDate='$start_date',
                        endDate='$end_date',
                        fileName='$fileName',
                        secure_filename='$newSecureFilename',
                        registrar_id='$registrar_id'
                     WHERE id='$eid'"
                );

                if ($query) {
                    echo "<script>alert('You have successfully updated the Certificate file & data');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                } else {
                    echo "<script>alert('Something Went Wrong. Please try again');</script>";
                    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
                }
            }
        }
    } else{
        echo "<script>alert('There is no Registrar associated with the University chosen');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
    }  
}

// Delete Certificate File Process
if($method === 'Delete'){
    $eid       = $_POST['id'];
    $file_name = $_POST['file_name'];

    // Fetch the secure filename from DB so we delete the right file
    $safe_eid   = mysqli_real_escape_string($mysqli, $eid);
    $certRecord = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT secure_filename, fileName FROM certificate WHERE id='$safe_eid' LIMIT 1"));

    $fileToDelete = !empty($certRecord['secure_filename'])
        ? $certRecord['secure_filename']
        : $certRecord['fileName'] . '.pdf';

    $filePath = realpath(__DIR__ . "/../") . "/uploads/" . $fileToDelete;

    if (file_exists($filePath) && unlink($filePath)) {
        mysqli_query($mysqli, "DELETE FROM certificate WHERE id='$safe_eid'");
        echo "<script>alert('You have successfully deleted the Certificate data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
    } else {
        // Delete DB record even if file is missing so the UI stays clean
        mysqli_query($mysqli, "DELETE FROM certificate WHERE id='$safe_eid'");
        echo "<script>alert('Certificate record deleted (file was already missing from disk).');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-certificates.php'; </script>";
    }
}
?>