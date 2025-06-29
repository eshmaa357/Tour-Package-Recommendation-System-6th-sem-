<?php
session_start();
include('includes/config.php');

if (isset($_POST['signin'])) {
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    // Check user with Status = 1 (Active)
    $sql = "SELECT EmailId, Password, Status FROM tblusers WHERE EmailId=:email AND Password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);

    if ($user) {
        if ($user->Status == 1) {
            $_SESSION['login'] = $email;
            echo "<script type='text/javascript'> document.location = 'package-list.php'; </script>";
        } else {
            echo "<script>alert('Your account has been banned. Please contact support.');</script>";
        }
    } else {
        echo "<script>alert('Invalid email or password');</script>";
    }
}
?>
