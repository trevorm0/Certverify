<?PHP
session_start();
// Check if user is already logged in or not
if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '')) {
    echo '<script>alert("Access Desnied! You must log in first.")</script>';
    echo "<script type='text/javascript'> document.location ='http://localhost/CertsVerify/admin/login.php'; </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="Description" content="Enter your description here"/>
        <link rel="icon" type="image/x-icon" href="../assets/img/favicon.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="../assets/css/style.css">
        <title>Graduate Details - Certs Verify</title>
    </head>
    <body>
        <!--Hero Section-->
        <div class="jumbotron jumbotron-fluid">
            <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #ffffff;">
                <div class="container">
                    <a href="#">
                        <img src="../assets/img/nav-logo.png" height="40">
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav mx-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-graduates.php">Graduates</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-registrars.php">Registrars</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-employers.php">Employers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-admins.php">Admins</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-certificates.php">Certificates</a>
                            </li>
                        </ul>
                        <form class="d-flex" role="search" method="POST" action="../processes/logout_process.php">
                            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                <li class="nav-item dropdown">
                                    <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <div class="d-flex flex-row user-info">
                                            <img class="rounded-circle" src="../assets/img/user-icon.png" width="50" height="50" style="margin-right:10px;">
                                            <div class="d-flex flex-column justify-content-start ml-2">
                                                <span class="d-block font-weight-bold name"><?php echo htmlentities($_SESSION['first_name'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlentities($_SESSION['last_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="date text-black-50"><?php echo htmlentities($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="../processes/logout_process.php">Logout</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </form>
                    </div>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="col-md-12">
                <div class="py-5">
                    <h1>Graduates</h1>
                    <button type="button" class="btn btn-primary" style="float: right;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <i class="fa fa-plus"></i> Add A New Graduate
                    </button>
                </div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>University Name</th>
                            <th>Admission Number</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            //connection to DB
                            require_once "../processes/db.php";

                            //query graduate table data
                            $sql = 'SELECT * FROM graduate';   
                            $result = mysqli_query($conn,$sql);  
                            $i = 1;                            
                            while($row = mysqli_fetch_array($result))
                            {
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $row['firstname']; ?></td>
                            <td><?php echo $row['lastname']; ?></td>
                            <td><?php echo $row['universityName']; ?></td>
                            <td><?php echo $row['admissionNumber']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phoneNumber']; ?></td>
                            <td><?php echo $row['role']; ?></td>
                            <td>
                                <a class="text-success" href="#" class="mr-3" style="margin-right:10px;" title="Update Record" data-toggle="tooltip" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['id'] ?>"><i class="bi bi-eye"></i></a>
                                <a class="text-warning" href="#" class="mr-3" style="margin-right:10px;" title="Update Record" data-toggle="tooltip" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id'] ?>"><i class="bi bi-pencil-square"></i></a>
                                <a class="text-danger" href="#" class="mr-3" style="margin-right:10px;" title="Update Record" data-toggle="tooltip" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id'] ?>"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>

                        <!-- VIEW POP UP FORM -->
                        <div id="viewModal<?php echo $row['id'] ?>" class="modal fade" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">View Graduate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form >
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="first_name" class="form-control" id="first_name" aria-describedby="emailHelp" placeholder="First Name" value="<?php echo $row['firstname']; ?>" readonly>
                                                        <label for="first_name">First Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="last_name" class="form-control" id="last_name" aria-describedby="emailHelp" placeholder="Last Name" value="<?php echo $row['lastname']; ?>" readonly>
                                                        <label for="last_name">Last Name</label>
                                                    </div>
                                                </div>
                                            </div>    
                                            <div class="form-floating mb-3">
                                                <input type="text" name="university_name" class="form-control" id="university_name" aria-describedby="emailHelp" placeholder="University" value="<?php echo $row['universityName']; ?>" readonly>
                                                <label for="email">University Name</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Admission Number" value="<?php echo $row['admissionNumber']; ?>" readonly>
                                                <label for="email">Admission Number</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="email" name="email" class="form-control" id="email" aria-describedby="emailHelp" placeholder="Email address" value="<?php echo $row['email']; ?>" readonly>
                                                        <label for="email">Email Address</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="phone_number" class="form-control" id="phone_number" aria-describedby="emailHelp" placeholder="Phone Number" value="<?php echo $row['phoneNumber']; ?>" readonly>
                                                        <label for="phone_number">Phone Number</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input type="text" name="role" class="form-control" id="role" aria-describedby="emailHelp" placeholder="Role" value="<?php echo $row['role']; ?>" readonly>
                                                <label for="email">Role</label>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EDIT POP UP FORM -->
                        <div id="editModal<?php echo $row['id'] ?>" class="modal fade" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Graduate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form  method="POST" action="../processes/admin_dashboard_graduate_processes.php">
                                            <input type="number" name="id" class="form-control" id="id" aria-describedby="emailHelp" placeholder="ID Number" value="<?php echo $row['id']; ?>" style="visibility: hidden;">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="first_name" class="form-control" id="first_name" aria-describedby="emailHelp" placeholder="First Name" value="<?php echo $row['firstname']; ?>">
                                                        <label for="first_name">First Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="last_name" class="form-control" id="last_name" aria-describedby="emailHelp" placeholder="Last Name" value="<?php echo $row['lastname']; ?>">
                                                        <label for="last_name">Last Name</label>
                                                    </div>
                                                </div>
                                            </div>    
                                            <div class="form-floating mb-3">
                                                <select class="form-select" name="university_name" class="form-control" id="university_name" aria-label="Default select example" required>
                                                    <option disabled>Select University</option>
                                                    <option value="Strathmore University" <?php if($row['universityName'] == 'Strathmore University') echo 'selected'; ?>>Strathmore University</option>
                                                    <option value="University of Nairobi" <?php if($row['universityName'] == 'University of Nairobi') echo 'selected'; ?>>University of Nairobi</option>
                                                    <option value="Riara University" <?php if($row['universityName'] == 'Riara University') echo 'selected'; ?>>Riara University</option>
                                                    <option value="KCA University" <?php if($row['universityName'] == 'KCA University') echo 'selected'; ?>>KCA University</option>
                                                    <option value="Kenyatta University" <?php if($row['universityName'] == 'Kenyatta University') echo 'selected'; ?>>Kenyatta University</option>
                                                    <option value="Jomo Kenyatta University of Agriculture and Technology" <?php if($row['universityName'] == 'Jomo Kenyatta University of Agriculture and Technology') echo 'selected'; ?>>Jomo Kenyatta University of Agriculture and Technology</option>
                                                    <option value="Catholic University of East Africa" <?php if($row['universityName'] == 'Catholic University of East Africa') echo 'selected'; ?>>Catholic University of East Africa</option>
                                                </select>
                                                <label for="university_name">University</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Admission Number" value="<?php echo $row['admissionNumber']; ?>">
                                                <label for="email">Admission Number</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="email" name="email" class="form-control" id="email" aria-describedby="emailHelp" placeholder="Email address" value="<?php echo $row['email']; ?>">
                                                        <label for="email">Email Address</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="phone_number" class="form-control" id="phone_number" aria-describedby="emailHelp" placeholder="Phone Number" value="<?php echo $row['phoneNumber']; ?>">
                                                        <label for="phone_number">Phone Number</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="method" name="method" value="Update" readonly required style="visibility: hidden;">
                                                </div>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="role" name="role" value="Graduate" readonly required style="visibility: hidden;">
                                                </div>
                                            </div> 
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DELETE POP UP FORM -->
                        <div id="deleteModal<?php echo $row['id'] ?>" class="modal fade" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Delete Graduate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form  method="POST" action="../processes/admin_dashboard_graduate_processes.php">
                                            <p>Are you <b>sure</b> want to delete the following Graduate data?</p>
                                            <input type="number" name="id" class="form-control" id="id" aria-describedby="emailHelp" placeholder="ID Number" value="<?php echo $row['id']; ?>" style="visibility: hidden;">
                                            <ul>
                                                <li> <p><b>Full Names: </b><?php echo $row['firstname']; ?> <?php echo $row['lastname']; ?></p> </li>
                                                <li> <p><b>University Name: </b><?php echo $row['universityName']; ?></p> </li>
                                                <li> <p><b>Admission Number: </b><?php echo $row['admissionNumber']; ?></p> </li>
                                                <li> <p><b>Email Address: </b><?php echo $row['email']; ?></p> </li>
                                                <li> <p><b>Phone Number: </b><?php echo $row['phoneNumber']; ?></p> </li>
                                                <li> <p><b>Role: </b><?php echo $row['role']; ?></p> </li>
                                            </ul>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="method" name="method" value="Delete" readonly required style="visibility: hidden;">
                                                </div>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="role" name="role" value="Graduate" readonly required style="visibility: hidden;">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
            
                        <?php 
                            $i++;
                            }
                            mysqli_close($conn);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ADD POP UP FORM -->
        <div class="modal fade" id="exampleModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Graduate Account</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form  method="POST" action="../processes/admin_dashboard_graduate_processes.php">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="first_name" class="form-control" id="first_name" aria-describedby="emailHelp" placeholder="First Name" required>
                                        <label for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="last_name" class="form-control" id="last_name" aria-describedby="emailHelp" placeholder="Last Name" required>
                                        <label for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>    
                            <div class="form-floating mb-3">
                                <select class="form-select" name="university_name" class="form-control" id="university_name" aria-label="Default select example" required>
                                    <option selected disabled>Choose University</option>
                                    <option value="Strathmore University">Strathmore University</option>
                                    <option value="University of Nairobi">University of Nairobi</option>
                                    <option value="Riara University">Riara University</option>
                                    <option value="KCA University">KCA University</option>
                                    <option value="Kenyatta University">Kenyatta University</option>
                                    <option value="Jomo Kenyatta University of Agriculture and Technology">Jomo Kenyatta University of Agriculture and Technology</option>
                                    <option value="Catholic University of East Africa">Catholic University of East Africa</option>
                                </select>
                                <label for="university_name">University</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Email address" required>
                                <label for="email">Admission Number</label>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" name="email" class="form-control" id="email" aria-describedby="emailHelp" placeholder="Email address" required>
                                        <label for="email">Email Address</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="phone_number" class="form-control" id="phone_number" aria-describedby="emailHelp" placeholder="Phone Number" required>
                                        <label for="phone_number">Phone Number</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                        <label for="password">Password</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                        <label for="confirm_password">Confirm Password</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" id="method" name="method" value="Create" readonly required style="visibility: hidden;">
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" id="role" name="role" value="Graduate" readonly required style="visibility: hidden;">
                                </div>
                            </div> 
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg" type="submit">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.0/js/bootstrap.min.js"></script>
    </body>
</html>