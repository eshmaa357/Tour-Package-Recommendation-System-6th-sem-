<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {	
    header('location:index.php');
} else {
    // Code for approving review
    if (isset($_REQUEST['approveid'])) {
        $rid = intval($_GET['approveid']);
        $status = 1;
        $sql = "UPDATE tblreviews SET Status=:status WHERE ReviewId=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        $msg = "Review approved successfully";
    }

    // Code for deleting review
    if (isset($_REQUEST['deleteid'])) {
        $rid = intval($_GET['deleteid']);
        $sql = "DELETE FROM tblreviews WHERE ReviewId=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        $msg = "Review deleted successfully";
    }
?>
<!DOCTYPE HTML>
<html>
<head>
<title>TTA | Admin Manage Reviews</title>
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
    <li class="breadcrumb-item"><a href="dashboard.php">Home</a><i class="fa fa-angle-right"></i>Manage Reviews</li>
</ol>
<div class="agile-grids">	
    <?php if ($error) { ?>
        <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
    <?php } else if ($msg) { ?>
        <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
    <?php } ?>
    <div class="agile-tables">
        <div class="w3l-table-info">
            <h2>Manage Reviews</h2>
            <table id="table">
                <thead>
                    <tr>
                        <th>Review ID</th>
                        <th>User</th>
                        <th>Package</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Review Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
$sql = "SELECT tblreviews.ReviewId as rid, tblreviews.Rating as rating, tblreviews.Comment as comment, tblreviews.ReviewDate as rdate, tblreviews.Status as status, tblusers.FullName as fname, tbltourpackages.PackageName as pckname 
        FROM tblreviews 
        JOIN tblusers ON tblreviews.UserEmail = tblusers.EmailId 
        JOIN tbltourpackages ON tbltourpackages.PackageId = tblreviews.PackageId";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
if ($query->rowCount() > 0) {
    foreach ($results as $result) {
        ?>
        <tr>
            <td>#REV-<?php echo htmlentities($result->rid);?></td>
            <td><?php echo htmlentities($result->fname);?></td>
            <td><?php echo htmlentities($result->pckname);?></td>
            <td><?php echo htmlentities($result->rating);?>/5</td>
            <td><?php echo htmlentities($result->comment);?></td>
            <td><?php echo htmlentities($result->rdate);?></td>
            <td>
                <?php 
                if ($result->status == 0) {
                    echo "Pending";
                } elseif ($result->status == 1) {
                    echo "Approved";
                }
                ?>
            </td>
            <td>
                <?php if ($result->status == 0) { ?>
                    <a href="manage-reviews.php?approveid=<?php echo htmlentities($result->rid);?>" onclick="return confirm('Do you want to approve this review?')" class="btn btn-success btn-sm">Approve</a>
                <?php } ?>
                <a href="manage-reviews.php?deleteid=<?php echo htmlentities($result->rid);?>" onclick="return confirm('Do you want to delete this review?')" class="btn btn-danger btn-sm">Delete</a>
            </td>
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
<?php } ?>