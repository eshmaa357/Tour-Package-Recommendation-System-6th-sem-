<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$error='';
$msg='';

// Fetch user details
$sql_user = "SELECT FullName, EmailId FROM tblusers WHERE id=:uid";
$query_user = $dbh->prepare($sql_user);
$query_user->bindParam(':uid', $uid, PDO::PARAM_INT);
$query_user->execute();
$user = $query_user->fetch(PDO::FETCH_OBJ);

if (!$user) {
    $error = "User not found.";
}
?>

<!DOCTYPE HTML>
<html>
<head>
<title>TTA | Admin User Bookings</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="css/bootstrap.min.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link rel="stylesheet" href="css/morris.css" type="text/css"/>
<link href="css/font-awesome.css" rel="stylesheet"> 
<script src="js/jquery-2.1.4.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/table-style.css" />
<link rel="stylesheet" type="text/css" href="css/basictable.css" />
<script type="text/javascript" src="js/jquery.basictable.min.js"></script>
<script type="text/javascript">
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
<link rel="stylesheet" href="css/icon-font.min.css" type='text/css' />
<style>
.errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
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
    <li class="breadcrumb-item"><a href="dashboard.php">Home</a><i class="fa fa-angle-right"></i><a href="manage-users.php">Manage Users</a><i class="fa fa-angle-right"></i>User Bookings</li>
</ol>
<div class="agile-grids">	
    <?php if ($error) { ?>
        <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
    <?php } else if ($msg) { ?>
        <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
    <?php } ?>
    <?php if ($user) { ?>
        <h2>Bookings for <?php echo htmlentities($user->FullName); ?> (<?php echo htmlentities($user->EmailId); ?>)</h2>
        <div class="agile-tables">
            <div class="w3l-table-info">
                <table id="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Package</th>
                            <th>From / To</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Booking Date</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$sql = "SELECT tblbooking.BookingId as bookid, tbltourpackages.PackageName as pckname, tblbooking.PackageId as pid, tblbooking.FromDate as fdate, tblbooking.ToDate as tdate, tblbooking.Comment as comment, tblbooking.status as status, tblbooking.PaymentStatus as paymentstatus, tblbooking.RegDate as regdate, tblbooking.CancelledBy as cancelby, tblbooking.UpdationDate as upddate 
        FROM tblbooking 
        JOIN tbltourpackages ON tbltourpackages.PackageId = tblbooking.PackageId 
        WHERE tblbooking.UserEmail=:email";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $user->EmailId, PDO::PARAM_STR);
try {
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    $cnt = 1;
    if ($query->rowCount() > 0) {
        foreach ($results as $result) {
            ?>
            <tr>
                <td>#BK-<?php echo htmlentities($result->bookid);?></td>
                <td><a href="update-package.php?pid=<?php echo htmlentities($result->pid);?>"><?php echo htmlentities($result->pckname);?></a></td>
                <td><?php echo htmlentities($result->fdate);?> To <?php echo htmlentities($result->tdate);?></td>
                <td><?php echo htmlentities($result->comment);?></td>
                <td>
                    <?php 
                    if ($result->status == 0) {
                        echo "Pending";
                    } elseif ($result->status == 1) {
                        echo "Confirmed";
                    } elseif ($result->status == 2 && $result->cancelby == 'a') {
                        echo "Canceled by admin at " . $result->upddate;
                    } elseif ($result->status == 2 && $result->cancelby == 'u') {
                        echo "Canceled by User at " . $result->upddate;
                    }
                    ?>
                </td>
                <td><?php echo htmlentities($result->paymentstatus ?? ''); ?></td>
                <td><?php echo htmlentities($result->regdate);?></td>
            </tr>
            <?php 
            $cnt++;
        }
    } else {
        echo "<tr><td colspan='7'>No bookings found for this user.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='7'>Error fetching bookings: " . $e->getMessage() . "</td></tr>";
}
?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>
    <div class="inner-block"></div>
<?php include('includes/footer.php');?>
</div>
</div>
<?php include('includes/sidebarmenu.php');?>
<div class="clearfix"></div>		
</div>
<script>
var toggle = true;
$(".sidebar-icon").click(function() {                
    if (toggle) {
        $(".page-container").addClass("sidebar-collapsed").removeClass("sidebar-collapsed-back");
        $("#menu span").css({"position":"absolute"});
    } else {
        $(".page-container").removeClass("sidebar-collapsed").addClass("sidebar-collapsed-back");
        setTimeout(function() {
            $("#menu span").css({"position":"relative"});
        }, 400);
    }
    toggle = !toggle;
});
</script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/scripts.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php ?>