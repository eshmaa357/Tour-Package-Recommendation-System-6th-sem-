<?php
session_start();
include('includes/config.php');
error_reporting(0);

if (isset($_POST['submit'])) {
    $fname = $_POST['fname'];
    $mnumber = $_POST['mobilenumber'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    // Check if email already exists
    $sql = "SELECT EmailId FROM tblusers WHERE EmailId = :email";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();

    if ($query->rowCount() > 0) {
        $_SESSION['msg'] = "This email is already registered.";
        header('location:thankyou.php');
        exit;
    }

    // Insert new user
    $sql = "INSERT INTO tblusers(FullName,MobileNumber,EmailId,Password) VALUES(:fname,:mnumber,:email,:password)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':fname', $fname, PDO::PARAM_STR);
    $query->bindParam(':mnumber', $mnumber, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();

    $lastInsertId = $dbh->lastInsertId();
    if ($lastInsertId) {
        $_SESSION['msg'] = "You are successfully registered. Now you can login.";
        header('location:thankyou.php');
        exit;
    } else {
        $_SESSION['msg'] = "Something went wrong. Please try again.";
        header('location:thankyou.php');
        exit;
    }
}
?>
