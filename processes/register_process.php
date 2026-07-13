<?php
$mysqli = require __DIR__ . "/db.php";

$role = $_POST["role"];

function validatePassword($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long.";
    if (!preg_match('/[A-Z]/', $password)) return "Password must contain at least one uppercase letter.";
    if (!preg_match('/[0-9]/', $password)) return "Password must contain at least one number.";
    return null;
}

function redirectBack($role, $message) {
    $pages = [
        'Registrar' => 'registrar/register.php',
        'SysAdmin'  => 'admin/register.php',
        'Graduate'  => 'graduate/register.php',
        'Employer'  => 'employer/register.php',
    ];
    $page = $pages[$role] ?? 'index.php';
    echo "<script>alert('$message')</script>";
    echo "<script>document.location='http://localhost/CertsVerify/$page';</script>";
    exit;
}

// ── Graduate ──
if ($role === 'Graduate') {
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email      = $_POST["email"];
    $phone      = $_POST["phone_number"];

    if ($password_1 != $password_2) redirectBack($role, "The two passwords do not match.");
    $pwError = validatePassword($password_1);
    if ($pwError) redirectBack($role, $pwError);

    $safe   = mysqli_real_escape_string($mysqli, $email);
    $result = mysqli_query($mysqli, "SELECT * FROM graduate WHERE email='$safe' LIMIT 1");
    $user   = mysqli_fetch_assoc($result);

    if ($user) {
        redirectBack($role, "Email Address already exists!");
    } else {
        $hash     = password_hash($password_1, PASSWORD_DEFAULT);
        $sec_q    = $_POST["security_question"] ?? "What is your favourite colour?";
        $sec_a    = strtolower(trim($_POST["security_answer"] ?? "blue"));
        $sql      = "INSERT INTO graduate (firstname,lastname,email,universityName,admissionNumber,role,phoneNumber,password,security_question,security_answer)
                     VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssssss",
            $_POST["first_name"], $_POST["last_name"], $_POST["email"],
            $_POST["university_name"], $_POST["admission_number"],
            $_POST["role"], $_POST["phone_number"], $hash, $sec_q, $sec_a
        );
        $stmt->execute();
        echo '<script>alert("Sign Up Successful!")</script>';
        echo "<script>document.location='http://localhost/CertsVerify/graduate/login.php';</script>";
    }
}

// ── Employer ──
if ($role === 'Employer') {
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email      = $_POST["email"];

    if ($password_1 != $password_2) redirectBack($role, "The two passwords do not match.");
    $pwError = validatePassword($password_1);
    if ($pwError) redirectBack($role, $pwError);

    $safe   = mysqli_real_escape_string($mysqli, $email);
    $result = mysqli_query($mysqli, "SELECT * FROM employer WHERE email='$safe' LIMIT 1");
    $user   = mysqli_fetch_assoc($result);

    if ($user) {
        redirectBack($role, "Email Address already exists!");
    } else {
        $hash  = password_hash($password_1, PASSWORD_DEFAULT);
        $sec_q = $_POST["security_question"] ?? "What is your favourite colour?";
        $sec_a = strtolower(trim($_POST["security_answer"] ?? "blue"));
        $sql   = "INSERT INTO employer (firstname,lastname,email,businessName,role,phoneNumber,password,security_question,security_answer,status)
                  VALUES (?,?,?,?,?,?,?,?,?,'pending')";
        $stmt  = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssss",
            $_POST["first_name"], $_POST["last_name"], $_POST["email"],
            $_POST["business_name"], $_POST["role"], $_POST["phone_number"],
            $hash, $sec_q, $sec_a
        );
        $stmt->execute();
        echo "<script>alert('Registration successful!\\n\\nYour account is pending admin approval.\\nYou will be able to log in once approved.')</script>";
        echo "<script>document.location='http://localhost/CertsVerify/employer/login.php';</script>";
    }
}

// ── Registrar ──
if ($role === 'Registrar') {
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email      = $_POST["email"];

    if ($password_1 != $password_2) redirectBack($role, "The two passwords do not match.");
    $pwError = validatePassword($password_1);
    if ($pwError) redirectBack($role, $pwError);

    $safe   = mysqli_real_escape_string($mysqli, $email);
    $result = mysqli_query($mysqli, "SELECT * FROM registrar WHERE email='$safe' LIMIT 1");
    $user   = mysqli_fetch_assoc($result);

    if ($user) {
        redirectBack($role, "Email Address already exists!");
    } else {
        $hash  = password_hash($password_1, PASSWORD_DEFAULT);
        $sec_q = $_POST["security_question"] ?? "What is your favourite colour?";
        $sec_a = strtolower(trim($_POST["security_answer"] ?? "blue"));
        $sql   = "INSERT INTO registrar (firstname,lastname,email,universityName,role,phoneNumber,password,status,security_question,security_answer)
                  VALUES (?,?,?,?,?,?,?,'pending',?,?)";
        $stmt  = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssss",
            $_POST["first_name"], $_POST["last_name"], $_POST["email"],
            $_POST["university_name"], $_POST["role"], $_POST["phone_number"],
            $hash, $sec_q, $sec_a
        );
        $stmt->execute();
        echo "<script>alert('Registration successful!\\n\\nYour account is pending admin approval.\\nYou will be able to log in once approved.')</script>";
        echo "<script>document.location='http://localhost/CertsVerify/registrar/login.php';</script>";
    }
}

// ── SysAdmin ──
if ($role === 'SysAdmin') {
    $password_1 = $_POST["password"];
    $password_2 = $_POST["confirm_password"];
    $email      = $_POST["email"];

    if ($password_1 != $password_2) redirectBack($role, "The two passwords do not match.");
    $pwError = validatePassword($password_1);
    if ($pwError) redirectBack($role, $pwError);

    $safe   = mysqli_real_escape_string($mysqli, $email);
    $result = mysqli_query($mysqli, "SELECT * FROM sysadmin WHERE email='$safe' LIMIT 1");
    $user   = mysqli_fetch_assoc($result);

    if ($user) {
        redirectBack($role, "Email Address already exists!");
    } else {
        $hash  = password_hash($password_1, PASSWORD_DEFAULT);
        $sec_q = $_POST["security_question"] ?? "What is your favourite colour?";
        $sec_a = strtolower(trim($_POST["security_answer"] ?? "blue"));
        $sql   = "INSERT INTO sysadmin (firstname,lastname,email,role,phoneNumber,password,security_question,security_answer)
                  VALUES (?,?,?,?,?,?,?,?)";
        $stmt  = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssss",
            $_POST["first_name"], $_POST["last_name"], $_POST["email"],
            $_POST["role"], $_POST["phone_number"], $hash, $sec_q, $sec_a
        );
        $stmt->execute();
        echo '<script>alert("Sign Up Successful!")</script>';
        echo "<script>document.location='http://localhost/CertsVerify/admin/login.php';</script>";
    }
}
?>
