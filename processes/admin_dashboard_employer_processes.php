<?php
$mysqli = require __DIR__ . "/db.php";

$method = $_POST["method"];
$role = $_POST["role"];

// Add Employer Data Process
if($method === 'Create'){
    //get form values
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email = $_POST["email"];
    $phone_number = $_POST["phone_number"];

    //check if password march
    if ($password_1 != $password_2) {
        echo '<script>alert("The two passwords do not match")</script>';
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
    } else {
        //query employer table with email
        $user_check_query = "SELECT * FROM employer WHERE email='$email' LIMIT 1";
        $result = mysqli_query($mysqli, $user_check_query);
        $user = mysqli_fetch_assoc($result);

        // if user exists
        if ($user) { 
            //alert if email already exists
            if ($user['email'] === $email) {
                echo '<script>alert("Email Address already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
            }

            //alert if phone number already exists
            if ($user['phoneNumber'] === $phone_number){
                echo '<script>alert("Phone Number already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php; </script>";
            }
        }

        //proceedto save new data
        else {
            $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

            //query db to insert data
            $sql = "INSERT INTO employer (firstname,lastname, email, businessName, role, phoneNumber, password) 
            VALUES (?,?,?,?,?,?,?)";
                    
            $stmt = $mysqli->stmt_init();

            if ( ! $stmt->prepare($sql)) {
                die("SQL error: " . $mysqli->error);
            }

            //bind form values to table
            $stmt->bind_param(
                "sssssss", 
                $_POST["first_name"], 
                $_POST["last_name"], 
                $_POST["email"], 
                $_POST["business_name"], 
                $_POST["role"],
                $_POST["phone_number"],
                $password_hash
            );

            $stmt->execute();
            echo '<script>alert("New Employer Created Successfully!")</script>';
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
        }
    }
}

// Edit Employer Data Process
if($method === 'Update'){
    $eid=$_POST['id'];
    //get form values
    $first_name=$_POST['first_name'];
    $last_name=$_POST['last_name'];
    $email=$_POST['email'];
    $business_name=$_POST['business_name'];
    $role=$_POST['role'];
    $phone_number=$_POST['phone_number'];
    
    //query db to update employer data
    $query=mysqli_query($conn, "update employer set firstname='$first_name',lastname='$last_name', email='$email', businessName='$business_name', role='$role', phoneNumber='$phone_number' where ID='$eid'");
    
    //check if operation is successful
    if ($query) {
        echo "<script>alert('You have successfully update the Employer data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
    }
}

// Delete Employer Data Process
if($method === 'Delete'){
    $eid=$_POST['id'];
    
    //query to delete employer data based on id
    $query=mysqli_query($conn, "DELETE FROM employer WHERE id='$eid'");
    
    //check if query is successful
    if ($query) {
        echo "<script>alert('You have successfully deleted the Employer data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-employers.php'; </script>";
    }
}

?>