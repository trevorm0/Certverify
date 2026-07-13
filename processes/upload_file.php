<?php
$mysqli = require __DIR__ . "/db.php";

$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$course_name = $_POST['course_name'];
$email = $_POST['email'];
$phone_number = $_POST['phone_number'];
$admission_number = $_POST['admission_number'];
$filename = $_FILES['pdf_file']['name'];

// destination of the file on the server
$destination = 'c/';

// get the file extension
$extension = pathinfo($filename, PATHINFO_EXTENSION);

// the physical file on a temporary uploads directory on the server
$file = $_FILES['pdf_file']['tmp_name'];
$size = $_FILES['pdf_file']['size'];

if (!in_array($extension, ['pdf'])) {
    echo "You file extension must be .pdf";
} elseif ($_FILES['pdf_file']['size'] > 1000000) { // file shouldn't be larger than 1Megabyte
    echo "File too large!";
} else {
    if (is_uploaded_file($_FILES['pdf_file']['tmp_name'])){
        move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destination.$filename);
        echo '<script>alert("File uploaded")</script>';
    }  
    // move the uploaded (temporary) file to the specified destination
    // if (move_uploaded_file($file, $destination)) {
    //     $insertquery = "INSERT INTO certificates (first_name,last_name,course_name,email,phone_number,admission_number,pdf_file) VALUES($first_name,$last_name,$course_name,$email,$phone_number,$admission_number,$filename)";
    //     if (mysqli_query($mysqli, $insertquery)) {
    //         echo '<script>alert("File uploaded")</script>';
    //     }
    // } else {
    //     echo '<script>alert("Upload failed")</script>';
    // }
}
?>