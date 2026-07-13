<?PHP
session_start();
// Check if user is already logged in or not
if (!(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '')) {
    echo '<script>alert("Access Denied! You must log in first.")</script>';
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
        <title>Certificate Details - Certs Verify</title>
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
                    <h1>Certificates</h1>
                    <button type="button" class="btn btn-primary" style="float: right;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <i class="fa fa-plus"></i> Add A New Certificate
                    </button>
                </div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Names</th>
                            <th>Admission Number</th>
                            <th>Course Name</th>
                            <th>University Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            //connection to DB
                            require_once "../processes/db.php";

                            //query certificate table data
                            $sql = 'SELECT * FROM certificate';
                            $result = mysqli_query($conn,$sql);
                            $i = 1;                            
                            while($row = mysqli_fetch_array($result))
                            {
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $row['studentNames']; ?></td>
                            <td><?php echo $row['admissionNumber']; ?></td>
                            <td><?php echo $row['courseName']; ?></td>
                            <td><?php echo $row['universityName']; ?></td>
                            <td><?php echo strftime('%m-%d-%Y', strtotime($row['startDate'])); ?></td>
                            <td><?php echo strftime('%m-%d-%Y', strtotime($row['endDate'])); ?></td>
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
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">View Certificate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form >
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="student_names" class="form-control" id="student_names" aria-describedby="emailHelp" placeholder="Student Names" value="<?php echo $row['studentNames']; ?>" readonly>
                                                        <label for="student_names">Student Names</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Admission Number" value="<?php echo $row['admissionNumber']; ?>" readonly>
                                                        <label for="admission_number">Admission Number</label>
                                                    </div>
                                                </div>
                                            </div>    
                                            <div class="form-floating mb-3">
                                                <input type="text" name="course" class="form-control" id="course" aria-describedby="emailHelp" placeholder="Course" value="<?php echo $row['courseName']; ?>" readonly>
                                                <label for="email">Course</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input type="text" name="university_name" class="form-control" id="university_name" aria-describedby="emailHelp" placeholder="University" value="<?php echo $row['universityName']; ?>" readonly>
                                                <label for="university_name">University Name</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="start_date" class="form-control" id="start_date" aria-describedby="emailHelp" placeholder="Start Date" value="<?php echo strftime('%m-%d-%Y', strtotime($row['startDate'])); ?>" readonly>
                                                        <label for="start_date">Start Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="end_date" class="form-control" id="end_date" aria-describedby="emailHelp" placeholder="End Date" value="<?php echo strftime('%m-%d-%Y', strtotime($row['endDate'])); ?>" readonly>
                                                        <label for="end_date">End Date</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating mb-3 text-center py-2">
                                                <i class="bi bi-file-earmark-pdf mb-1" style="font-size: 100px; color: #00d563;"></i>
                                                <br>
                                                <?php if (!empty($row['certID'])): ?>
                                                    <a href="../serve_cert.php?id=<?php echo urlencode($row['certID']); ?>" target="_blank">
                                                        View Certificate
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No certificate file linked</span>
                                                <?php endif; ?>
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
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Certificate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form  method="POST" enctype="multipart/form-data" action="../processes/admin_dashboard_cert_processes.php">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="number" name="id" class="form-control" id="id" aria-describedby="emailHelp" placeholder="ID Number" value="<?php echo $row['id']; ?>" style="visibility: hidden;">
                                                </div>
                                                <div class="col-sm-6">
                                                    <input type="text" name="file_name" class="form-control" id="file_name" aria-describedby="emailHelp" placeholder="File Name" value="<?php echo $row['fileName']; ?>" style="visibility: hidden;">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="student_names" class="form-control" id="student_names" aria-describedby="emailHelp" placeholder="Student Names" value="<?php echo $row['studentNames']; ?>" required>
                                                        <label for="first_name">Student Names</label>
                                                        <small style="font-size: 10px;" class="text-danger">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Admission Number" value="<?php echo $row['admissionNumber']; ?>" required>
                                                        <label for="last_name">Admission Number</label>
                                                        <small style="font-size: 10px;" class="text-danger">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
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
                                                        <small style="font-size: 10px;" class="text-danger">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <select class="form-select" name="course_name" class="form-control" id="course_name" aria-label="Default select example" required>
                                                            <option selected disabled>Choose Course</option>

                                                            <option value="Bachelor of Commerce" <?php if($row['courseName'] == 'Bachelor of Commerce') echo 'selected'; ?>>Bachelor of Commerce</option>
                                                            <option value="Bachelor of Tourism Management" <?php if($row['courseName'] == 'Bachelor of Tourism Management') echo 'selected'; ?>>Bachelor of Tourism Management</option>
                                                            <option value="Bachelor of Science in Hospitality and Hotel Management" <?php if($row['courseName'] == 'Bachelor of Science in Hospitality and Hotel Management') echo 'selected'; ?>>Bachelor of Science in Hospitality and Hotel Management</option>
                                                            <option value="Bachelor of Business Science: Financial Engineering" <?php if($row['courseName'] == 'Bachelor of Business Science: Financial Engineering') echo 'selected'; ?>>Bachelor of Business Science: Financial Engineering</option>
                                                            <option value="Kenyatta University" <?php if($row['courseName'] == 'Bachelor of Business Science: Financial Economics') echo 'selected'; ?>>Bachelor of Business Science: Financial Economics</option>
                                                            <option value="Bachelor of Business Science: Actuarial Science" <?php if($row['courseName'] == 'Bachelor of Business Science: Actuarial Science') echo 'selected'; ?>>Bachelor of Business Science: Actuarial Science</option>
                                                            <option value="Bachelor Of Science In Informatics And Computer Science" <?php if($row['courseName'] == 'Bachelor Of Science In Informatics And Computer Science') echo 'selected'; ?>>Bachelor Of Science In Informatics And Computer Science</option>
                                                            <option value="Bachelor Of Business Information Technology" <?php if($row['courseName'] == 'Bachelor Of Business Information Technology') echo 'selected'; ?>>Bachelor Of Business Information Technology</option>
                                                            <option value="Bachelor of Science in Computer Networks and Cyber Security (BSc. CNS)" <?php if($row['courseName'] == 'Bachelor of Science in Computer Networks and Cyber Security (BSc. CNS)') echo 'selected'; ?>>Bachelor of Science in Computer Networks and Cyber Security (BSc. CNS)</option>
                                                        </select>
                                                        <label for="course_name">Course Name</label>
                                                        <small style="font-size: 10px;" class="text-danger">Updating this field will require re-uploading the certificate</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="date" class="form-control" value="<?php echo strftime('%Y-%m-%d', strtotime($row['startDate'])); ?>" name="start_date" id="start_date" placeholder="Start Date" required/>
                                                        <label for="start_date">Start Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="date" class="form-control" value="<?php echo strftime('%Y-%m-%d', strtotime($row['endDate'])); ?>" name="end_date" id="end_date" placeholder="End Date" required/>
                                                        <label for="end_date">End Date</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="formFile" class="form-label">Choose Certificate (Optional)</label>
                                                <input class="form-control" type="file" name="file_upload" id="file_upload" require>
                                                <small style="font-size: 10px;" class="text-danger">Uploading a new file will replace the existing file</small>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <input type="text" class="form-control" id="method" name="method" value="Update" readonly required style="visibility: hidden;">
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
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Delete Certificate Info</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form  method="POST" action="../processes/admin_dashboard_cert_processes.php">
                                            <p>Are you <b>sure</b> want to delete the following Certificate data?</p>
                                            <input type="number" name="id" class="form-control" id="id" aria-describedby="emailHelp" placeholder="ID Number" value="<?php echo $row['id']; ?>" style="visibility: hidden;">
                                            <ul>
                                                <li> <p><b>Student Names: </b><?php echo $row['studentNames']; ?> </li>
                                                <li> <p><b>Admission Number: </b><?php echo $row['admissionNumber']; ?></p> </li>
                                                <li> <p><b>Course Name: </b><?php echo $row['courseName']; ?></p> </li>
                                                <li> <p><b>University Name: </b><?php echo $row['universityName']; ?></p> </li>
                                                <li> <p><b>Start Date: </b><?php echo $row['startDate']; ?></p> </li>
                                                <li> <p><b>End Date: </b><?php echo $row['endDate']; ?></p> </li>
                                                <li> <p><b>Certificate: </b><?php echo $row['fileName']; ?>.pdf</p> </li>
                                            </ul>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="method" name="method" value="Delete" readonly required style="visibility: hidden;">
                                                </div>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" id="file_name" name="file_name" value="<?php echo $row['fileName']; ?>" readonly required style="visibility: hidden;">
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
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ADD POP UP FORM -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add Certificate Info</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data" action="../processes/admin_dashboard_cert_processes.php">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="student_names" class="form-control" id="student_names" aria-describedby="emailHelp" placeholder="Student Names" required>
                                        <label for="first_name">Student Names</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="admission_number" class="form-control" id="admission_number" aria-describedby="emailHelp" placeholder="Admission Number" required>
                                        <label for="last_name">Admission Number</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
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
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" name="course_name" class="form-control" id="course_name" aria-label="Default select example" required>
                                            <option selected disabled>Choose Course</option>
                                            <option value="Bachelor of Commerce">Bachelor of Commerce</option>
                                            <option value="Bachelor of Tourism Management">Bachelor of Tourism Management</option>
                                            <option value="Bachelor of Science in Hospitality and Hotel Management">Bachelor of Science in Hospitality and Hotel Management</option>
                                            <option value="Bachelor of Business Science: Financial Engineering">Bachelor of Business Science: Financial Engineering</option>
                                            <option value="Bachelor of Business Science: Financial Economics">Bachelor of Business Science: Financial Economics</option>
                                            <option value="Bachelor of Business Science: Actuarial Science">Bachelor of Business Science: Actuarial Science</option>
                                            <option value="Bachelor Of Science In Informatics And Computer Science">Bachelor Of Science In Informatics And Computer Science</option>
                                            <option value="Bachelor Of Business Information Technology">Bachelor Of Business Information Technology</option>
                                            <option value="Bachelor of Science in Computer Networks and Cyber Security (BSc. CNS)">Bachelor of Science in Computer Networks and Cyber Security (BSc. CNS)</option>
                                        </select>
                                        <label for="course_name">Course Name</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" name="start_date" class="form-control" id="start_date" aria-describedby="emailHelp" placeholder="Start Date" required>
                                        <label for="start_date">Start Date</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" name="end_date" class="form-control" id="end_date" aria-describedby="emailHelp" placeholder="End Date" required>
                                        <label for="end_date">End Date</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="formFile" class="form-label">Choose Certificate</label>
                                <input class="form-control" type="file" name="file" id="file" require>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <input type="text" class="form-control" id="method" name="method" value="Create" readonly required style="visibility: hidden;">
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg" type="submit" name="submit">Add</button>
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