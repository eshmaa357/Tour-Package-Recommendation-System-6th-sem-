<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Debug session
error_log("manage-users.php: Session alogin = " . (isset($_SESSION['alogin']) ? $_SESSION['alogin'] : 'not set'));

// Check if admin is logged in
if (empty($_SESSION['alogin'])) {
    error_log("manage-users.php: No admin logged in, redirecting to admin login");
    header('Location: /tms/tms/admin/index.php');
    exit;
}

// Generate or verify CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$msg = '';
$error = '';

// Check if tblreviews exists
$reviewTableExists = $dbh->query("SHOW TABLES LIKE 'tblreviews'")->fetch();

// Code for banning a user
if (isset($_REQUEST['banid']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $uid = intval($_GET['banid']);
    $status = 0;
    
    // Get user email for session invalidation
    $sql = "SELECT EmailId FROM tblusers WHERE id = :uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':uid', $uid, PDO::PARAM_INT);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);
    
    try {
        // Update user status
        $sql = "UPDATE tblusers SET Status=:status, UpdationDate=CURRENT_TIMESTAMP WHERE id=:uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $status, PDO::PARAM_INT);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();
        
        // Log ban action
        error_log("manage-users.php: User banned, uid=$uid, email=" . ($user->EmailId ?? 'unknown'));
        $msg = "User banned successfully";
    } catch (PDOException $e) {
        $error = "Error banning user: " . $e->getMessage();
        error_log("manage-users.php: Ban error for uid=$uid: " . $e->getMessage());
    }
}

// Code for unbanning a user
if (isset($_REQUEST['unbanid']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $uid = intval($_GET['unbanid']);
    $status = 1;
    $sql = "UPDATE tblusers SET Status=:status, UpdationDate=CURRENT_TIMESTAMP WHERE id=:uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':status', $status, PDO::PARAM_INT);
    $query->bindParam(':uid', $uid, PDO::PARAM_INT);
    try {
        $query->execute();
        if ($query->rowCount() > 0) {
            $msg = "User unbanned successfully";
        } else {
            $error = "User not found or already unbanned";
        }
    } catch (PDOException $e) {
        $error = "Error unbanning user: " . $e->getMessage();
        error_log("manage-users.php: Unban error for uid=$uid: " . $e->getMessage());
    }
}

// Code for deleting a user
if (isset($_REQUEST['deleteid']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $uid = intval($_GET['deleteid']);
    try {
        // Get user email for logging
        $sql = "SELECT EmailId FROM tblusers WHERE id = :uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_OBJ);

        // Delete user's bookings
        $sql = "DELETE FROM tblbooking WHERE UserEmail=(SELECT EmailId FROM tblusers WHERE id=:uid)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();

        // Delete user's reviews if tblreviews exists
        if ($reviewTableExists !== false) {
            $sql = "DELETE FROM tblreviews WHERE UserEmail=(SELECT EmailId FROM tblusers WHERE id=:uid)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':uid', $uid, PDO::PARAM_INT);
            $query->execute();
        }

        // Delete user's enquiries
        $sql = "DELETE FROM tblenquiry WHERE EmailId=(SELECT EmailId FROM tblusers WHERE id=:uid)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();

        // Delete user's issues
        $sql = "DELETE FROM tblissues WHERE UserEmail=(SELECT EmailId FROM tblusers WHERE id=:uid)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();

        // Delete the user
        $sql = "DELETE FROM tblusers WHERE id=:uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();

        error_log("manage-users.php: User deleted, uid=$uid, email=" . ($user->EmailId ?? 'unknown'));
        $msg = "User deleted successfully";
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
        error_log("manage-users.php: Delete error for uid=$uid: " . $e->getMessage());
    }
}
?>

<!DOCTYPE HTML>
<html>
<head>
<title>TTA | Admin Manage Users</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="/tms/tms/admin/css/bootstrap.min.css" rel='stylesheet' type='text/css' />
<link href="/tms/tms/admin/css/style.css" rel='stylesheet' type='text/css' />
<link rel="stylesheet" href="/tms/tms/admin/css/morris.css" type='text/css'/>
<link href="/tms/tms/admin/css/font-awesome.css" rel="stylesheet"> 
<script src="/tms/tms/admin/js/jquery-2.1.4.min.js"></script>
<link rel="stylesheet" type="text/css" href="/tms/tms/admin/css/table-style.css" />
<link rel="stylesheet" type="text/css" href="/tms/tms/admin/css/basictable.css" />
<script type="text/javascript" src="/tms/tms/admin/js/jquery.basictable.min.js"></script>
<script>
$(document).ready(function() {
    $('#table').basictable();
    $('#table-breakpoint').basictable({ breakpoint: 768 });
    $('#table-swap-axis').basictable({ swapAxis: true });
    $('#table-force-off').basictable({ forceResponsive: false });
    $('#table-no-resize').basictable({ noResize: true });
    $('#table-two-axis').basictable();
    $('#table-max-height').basictable({ tableWrapper: true });
});
</script>
<link href='//fonts.googleapis.com/css?family=Roboto:700,500,300,100italic,100,400' rel='stylesheet' type='text/css'/>
<link href='//fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="/tms/tms/admin/css/icon-font.min.css" type='text/css' />
<style>
.errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
</style>
</head> 
<body>
<div class="page-container">
<div class="left-content">
<div class="mother-grid-inner">
<?php include('includes/header.php');?>
<div class="clearfix"></div>	
</div>
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/tms/tms/admin/dashboard.php">Home</a><i class="fa fa-angle-right"></i>Manage Users</li>
</ol>
<div class="agile-grids">	
<?php if ($error) { ?>
    <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
<?php } else if ($msg) { ?>
    <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
<?php } ?>
<div class="agile-tables">
<div class="w3l-table-info">
<h2>Manage Users</h2>
<table id="table">
<thead>
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Mobile No.</th>
    <th>Email Id</th>
    <th>Reg Date</th>
    <th>Updation Date</th>
    <th>Status</th>
    <th>Action</th>
    <th>Bookings</th>
</tr>
</thead>
<tbody>
<?php
$sql = "SELECT id, FullName, MobileNumber, EmailId, RegDate, UpdationDate, Status FROM tblusers";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
if ($query->rowCount() > 0) {
    foreach ($results as $result) {
        $status = $result->Status ?? 0;
?>
<tr>
    <td><?php echo htmlentities($cnt);?></td>
    <td><?php echo htmlentities($result->FullName);?></td>
    <td><?php echo htmlentities($result->MobileNumber);?></td>
    <td><?php echo htmlentities($result->EmailId);?></td>
    <td><?php echo htmlentities($result->RegDate);?></td>
    <td><?php echo htmlentities($result->UpdationDate ?? '');?></td>
    <td><?php echo $status == 1 ? 'Active' : 'Banned'; ?></td>
    <td>
        <?php if ($status == 1) { ?>
            <form action="/tms/tms/admin/manage-users.php?banid=<?php echo htmlentities($result->id);?>" method="post" onsubmit="return confirm('Do you really want to ban this user?')">
                <input type="hidden" name="csrf_token" value="<?php echo htmlentities($csrf_token); ?>">
                <button type="submit" class="btn btn-danger btn-sm">Ban</button>
            </form>
        <?php } else { ?>
            <form action="/tms/tms/admin/manage-users.php?unbanid=<?php echo htmlentities($result->id);?>" method="post" onsubmit="return confirm('Do you want to unban this user?')">
                <input type="hidden" name="csrf_token" value="<?php echo htmlentities($csrf_token); ?>">
                <button type="submit" class="btn btn-success btn-sm">Unban</button>
            </form>
        <?php } ?>
        <form action="/tms/tms/admin/manage-users.php?deleteid=<?php echo htmlentities($result->id);?>" method="post" onsubmit="return confirm('Do you really want to delete this user and all their associated data?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlentities($csrf_token); ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
    </td>
    <td><a href="/tms/tms/admin/user-bookings.php?uid=<?php echo htmlentities($result->id);?>" class="btn btn-info btn-sm">View Bookings</a></td>
</tr>
<?php 
        $cnt++;
    }
}
?>
</tbody>
</table>
</div>
</div>
<script>
$(document).ready(function() {
    var navoffeset = $(".header-main").offset().top;
    $(window).scroll(function() {
        var scrollpos = $(window).scrollTop(); 
        if (scrollpos >= navoffeset) {
            $(".header-main").addClass("fixed");
        } else {
            $(".header-main").removeClass("fixed");
        }
    });
});
</script>
<div class="inner-block"></div>
<?php include('includes/footer.php'); ?>
</div>
</div>
<?php include('includes/sidebarmenu.php'); ?>
<div class="clearfix"></div>		
</div>
<script src="/tms/tms/admin/js/jquery.nicescroll.js"></script>
<script src="/tms/tms/admin/js/scripts.js"></script>
<script src="/tms/tms/admin/js/bootstrap.min.js"></script>	   
</body>
</html>