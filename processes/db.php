<?php
    $servername = "localhost";
    $username = "verifyAdmin";
    $password = "12345";
    $db = "verifyDB";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $db);

    // //Check connection
    // if ($conn->connect_error) {
    //   die("Connection failed: " . $conn->connect_error);
    // }
    // echo "Connected successfully";

    // $sql= "CREATE TABLE registrar (
    //     id INT(6) NOT NULL AUTO_INCREMENT,
    //     firstname VARCHAR(255) NOT NULL,
    //     lastname VARCHAR(255) NOT NULL,
    //     email VARCHAR(255),
    //     universityName VARCHAR(255),
    //     role VARCHAR(255),
    //     phoneNumber VARCHAR(255),
    //     password VARCHAR(255),
    //     PRIMARY KEY (id)
    // ) ENGINE=INNODB";

    // if ($conn->query($sql) === TRUE) {
    //     echo "Table registrar created successfully";
    // } else {
    //     echo "Error creating table: " . $conn->error;
    // }

    // $sql= "CREATE TABLE sysadmin (
    //     id INT(6) NOT NULL AUTO_INCREMENT,
    //     firstname VARCHAR(255) NOT NULL,
    //     lastname VARCHAR(255) NOT NULL,
    //     email VARCHAR(255),
    //     role VARCHAR(255),
    //     phoneNumber VARCHAR(255),
    //     password VARCHAR(255),
    //     PRIMARY KEY (id)
    // ) ENGINE=INNODB";

    // if ($conn->query($sql) === TRUE) {
    //     echo "Table sysadmin created successfully";
    // } else {
    //     echo "Error creating table: " . $conn->error;
    // }

    // $sql= "CREATE TABLE graduate (
    //     id INT(6) NOT NULL AUTO_INCREMENT,
    //     firstname VARCHAR(255) NOT NULL,
    //     lastname VARCHAR(255) NOT NULL,
    //     universityName VARCHAR(255),
    //     admissionNumber VARCHAR(255),
    //     email VARCHAR(255),
    //     role VARCHAR(255),
    //     phoneNumber VARCHAR(255),
    //     password VARCHAR(255),
    //     PRIMARY KEY (id)
    // ) ENGINE=INNODB";

    // if ($conn->query($sql) === TRUE) {
    //     echo "Table graduate created successfully";
    // } else {
    //     echo "Error creating table: " . $conn->error;
    // }

    // $sql= "CREATE TABLE employer (
    //     id INT(6) NOT NULL AUTO_INCREMENT,
    //     firstname VARCHAR(255) NOT NULL,
    //     lastname VARCHAR(255) NOT NULL,
    //     businessName VARCHAR(255),
    //     email VARCHAR(255),
    //     role VARCHAR(255),
    //     phoneNumber VARCHAR(255),
    //     password VARCHAR(255),
    //     PRIMARY KEY (id)
    // ) ENGINE=INNODB";
    
    // if ($conn->query($sql) === TRUE) {
    //   echo "Table employer created successfully";
    // } else {
    //   echo "Error creating table: " . $conn->error;
    // }

    // $sql= "CREATE TABLE certificate (
    //     id INT(6) NOT NULL AUTO_INCREMENT,
    //     studentNames VARCHAR(255) NOT NULL,
    //     admissionNumber VARCHAR(255) NOT NULL,
    //     universityName VARCHAR(255) NOT NULL,
    //     courseName VARCHAR(255) NOT NULL,
    //     startDate datetime NOT NULL,
    //     endDate datetime NOT NULL,
    //     fileName varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    //     PRIMARY KEY (id),
    //     registrar_id INT,
    //     INDEX reg_ind (registrar_id),
    //     FOREIGN KEY (registrar_id)
    //         REFERENCES registrar(id)
    //         ON DELETE CASCADE
    // ) ENGINE=INNODB";
    
    // if ($conn->query($sql) === TRUE) {
    //   echo "Table certificate created successfully";
    // } else {
    //   echo "Error creating table: " . $conn->error;
    // }
    
    return $conn;
    ?>
?>