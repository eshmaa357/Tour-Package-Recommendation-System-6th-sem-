<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('includes/config.php');

if (!isset($_SESSION['login'])) {
    error_log("check-login.php: No user logged in, redirecting to index.php");
    header("Location: /tms/tms/index.php?show_login=true");
    exit();
}

$email = $_SESSION['login'];

// Check user status
$sql = "SELECT Status FROM tblusers WHERE EmailId = :email LIMIT 1";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $email, PDO::PARAM_STR);
$query->execute();
$user = $query->fetch(PDO::FETCH_OBJ);

if (!$user || $user->Status != 1) {
    error_log("check-login.php: User banned or not found for email: $email");
    session_destroy();
    header("Location: /tms/tms/index.php?error=user_banned");
    exit();
}
?>