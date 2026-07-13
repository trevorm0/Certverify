<?php
$mysqli = require __DIR__ . "/db.php";
session_start();

$search = $_POST["search"];

$graduate_check_query = "SELECT * FROM graduate WHERE admissionNumber='$search' LIMIT 1";
$result = mysqli_query($mysqli, $graduate_check_query);
$graduate_results = mysqli_fetch_assoc($result);

if ($graduate_results['admissionNumber']){
    $cert_check_query = 'SELECT * FROM certificate';
    $cert_result = mysqli_query($conn,$cert_check_query);
    $i = 1;                            
    while($row = mysqli_fetch_array($cert_result)){
        if (str_contains($row['fileName'], $graduate_results['admissionNumber'])) { 
            echo '<script>alert("Certificate record exists!")</script>';
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/graduate/dashboard.php'; </script>";
        } else{
            echo '<script>alert("Certificate record does not exist!")</script>';
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/graduate/dashboard.php'; </script>";
        }
    }
}
else{
    echo '<script>alert("Graduate record does not exist!")</script>';
    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/graduate/dashboard.php'; </script>";
}
?>