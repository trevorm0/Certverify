<?php
session_start();
$mysqli = require 'processes/db.php';

// Show what's in the certificate table
echo "<h3>Certificates in database:</h3>";
$result = mysqli_query($mysqli, "SELECT studentNames, admissionNumber, universityName, courseName FROM certificate");
while($row = mysqli_fetch_assoc($result)){
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}
?>