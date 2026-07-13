<?php
$mysqli = require __DIR__ . "/db.php";
session_start();

$method = $_POST["method"]; 
$role = $_POST["role"];

// Add System Admin Details Process
if($method === 'Create'){
    //get form field values
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email = $_POST["email"];
    $phone_number = $_POST["phone_number"];

    //check if passwords match
    if ($password_1 != $password_2) {
        echo '<script>alert("The two passwords do not match")</script>';
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
    } else {
        //query sysdamin table using email
        $user_check_query = "SELECT * FROM sysadmin WHERE email='$email' LIMIT 1";
        $result = mysqli_query($mysqli, $user_check_query);
        $user = mysqli_fetch_assoc($result);

        // if user exists
        if ($user) {
            //alert if email already exists
            if ($user['email'] === $email) {
                echo '<script>alert("Email Address already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
            }

            //alert if phone number already exists
            if ($user['phoneNumber'] === $phone_number){
                echo '<script>alert("Phone Number already exists!")</script>';
                echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php; </script>";
            }
        }

        //proceed to add new record
        else {
            $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

            //insert values into sysdamin table
            $sql = "INSERT INTO sysadmin (firstname,lastname, email, role, phoneNumber, password) 
            VALUES (?,?,?,?,?,?)";
                    
            $stmt = $mysqli->stmt_init();

            if ( ! $stmt->prepare($sql)) {
                die("SQL error: " . $mysqli->error);
            }

            //bind form values to table
            $stmt->bind_param(
                "ssssss", 
                $_POST["first_name"], 
                $_POST["last_name"], 
                $_POST["email"], 
                $_POST["role"],
                $_POST["phone_number"],
                $password_hash
            );

            $stmt->execute();
            echo '<script>alert("New System Admin Created Successfully!")</script>';
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
        }
    }
}

// Update System Admin Details Process
if($method === 'Update'){
    $eid=$_POST['id'];
    //get form values
    $first_name=$_POST['first_name'];
    $last_name=$_POST['last_name'];
    $email=$_POST['email'];
    $role=$_POST['role'];
    $phone_number=$_POST['phone_number'];
    
    //query bt to update sysadmin data
    $query=mysqli_query($conn, "update sysadmin set firstname='$first_name',lastname='$last_name', email='$email', role='$role', phoneNumber='$phone_number' where ID='$eid'");
    
    //check if query was successful
    if ($query) {
        echo "<script>alert('You have successfully update the System Admin data');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
    }
}

// Delete System Admin Details Process
if($method === 'Delete'){
    $eid=$_POST['id'];
    
    //query db to delete sysadmin data based on id
    $query=mysqli_query($conn, "DELETE FROM sysadmin WHERE id='$eid'");
    
    //check if operation was successful
    if ($query) {
        //check if deleted data belongs to logged in user id
        if ($eid === $_SESSION['user_id']){
            //delete session data
            unset(
                $_SESSION['user_id'],
                $_SESSION['email'],
                $_SESSION['first_name'],
                $_SESSION['last_name'],
                $_SESSION['role'],
                $_SESSION['phone_number']
            );
            session_destroy();
            //log out the user
            echo "<script>alert('You have successfully deleted your own account. Create a new account to access dashboard.');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/register.php'; </script>";
        }else {
            echo "<script>alert('You have successfully deleted the System Admin data');</script>";
            echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
        }
    }
    else{
        echo "<script>alert('Something Went Wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/dashboard-admins.php'; </script>";
    }
}

?>