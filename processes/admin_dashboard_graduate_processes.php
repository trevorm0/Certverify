<?php

$mysqli = require __DIR__ . "/db.php";

$method = $_POST["method"];
$role = $_POST["role"];

// Add Graduate Data Process
if($method === 'Create'){
    //get form values
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email = $_POST["email"];
    $phone_number = $_POST["phone_number"];

    //check if password match
    if ($password_1 != $password_2) {
        echo '<script>alert("The two passwords do not match")</script>';
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
    } else{
        //query graduate table with email
        $user_check_query = "SELECT * FROM graduate WHERE email='$email' LIMIT 1";
        $result = mysqli_query($mysqli, $user_check_query);
        $user = mysqli_fetch_assoc($result);

        // if user exists
        if ($user) {
            //alert if email already exists
            if ($user['email'] === $email) {
                echo '<script>alert("Email Address already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
            }

            //alert if phone number exists
            if ($user['phoneNumber'] === $phone_number){
                echo '<script>alert("Phone Number already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php; </script>";
            }
        }

        //proceed to save new data
        else {
            $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

            //query table to insert data
            $sql = "INSERT INTO graduate (firstname,lastname, email, universityName, admissionNumber, role, phoneNumber, password) 
            VALUES (?,?,?,?,?,?,?,?)";
                    
            $stmt = $mysqli->stmt_init();

            if ( ! $stmt->prepare($sql)) {
                die("SQL error: " . $mysqli->error);
            }

            //bind form values to table
            $stmt->bind_param(
                "ssssssss", 
                $_POST["first_name"], 
                $_POST["last_name"], 
                $_POST["email"], 
                $_POST["university_name"], 
                $_POST["admission_number"], 
                $_POST["role"],
                $_POST["phone_number"],
                $password_hash
            );

            $stmt->execute();
            echo '<script>alert("New Graduate Created Successfully!")</script>';
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
        }
    }
}

// Edit Graduate Data Process
if($method === 'Update'){
    $eid=$_POST['id'];
    //get form values
    $first_name=$_POST['first_name'];
    $last_name=$_POST['last_name'];
    $email=$_POST['email'];
    $university_name=$_POST['university_name'];
    $admission_number=$_POST['admission_number'];
    $role=$_POST['role'];
    $phone_number=$_POST['phone_number'];
    
    //query db to update data
    $query=mysqli_query($conn, "update graduate set firstname='$first_name',lastname='$last_name', email='$email', universityName='$university_name', admissionNumber='$admission_number' , role='$role', phoneNumber='$phone_number' where ID='$eid'");
    
    //if operation was successful
    if ($query) {
        echo "<script>alert('You have successfully update the Graduate data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
    }
}

// Delete Graduate Data Process
if($method === 'Delete'){
    $eid=$_POST['id'];
    
    //query db to delete data
    $query=mysqli_query($conn, "DELETE FROM graduate WHERE id='$eid'");
    
    //if operation was successful
    if ($query) {
        echo "<script>alert('You have successfully deleted the Graduate data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-graduates.php'; </script>";
    }
}

?>