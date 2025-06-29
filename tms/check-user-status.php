<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('includes/config.php');

function checkUserStatus($dbh, $email) {
    if (!$email) {
        return false;
    }
    $sql = "SELECT Status FROM tblusers WHERE EmailId = :email LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);
    if (!$user || $user->Status != 1) {
        error_log("check-user-status.php: User banned or not found for email: $email");
        session_destroy();
        header("Location: /tms/tms/index.php?error=user_banned");
        exit();
    }
    return true;
}
?>