<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
} else { 
?>
<!DOCTYPE HTML>
<html>
<head>
<title>TTA | Admin Manage Packages</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="css/bootstrap.min.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link rel="stylesheet" href="css/morris.css" type="text/css"/>
<!-- Graph CSS -->
<link href="css/font-awesome.css" rel="stylesheet"> 
<!-- jQuery -->
<script src="js/jquery-2.1.4.min.js"></script>
<!-- //jQuery -->
<!-- tables -->
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
<!-- //tables -->
<link href='//fonts.googleapis.com/css?family=Roboto:700,500,300,100italic,100,400' rel='stylesheet' type='text/css'/>
<link href='//fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<!-- lined-icons -->
<link rel="stylesheet" href="css/icon-font.min.css" type='text/css' />
</head> 
<body>
   <div class="page-container">
   <div class="left-content">
       <div class="mother-grid-inner">
           <?php include('includes/header.php');?>
           <div class="clearfix"></div>
       </div>
       <ol class="breadcrumb">
           <li class="breadcrumb-item"><a href="dashboard.php">Home</a><i class="fa fa-angle-right"></i>Manage Packages</li>
       </ol>
       <div class="agile-grids">	
           <div class="agile-tables">
               <div class="w3l-table-info">
                   <h2>Manage Packages</h2>
                   <table id="table">
                       <thead>
                           <tr>
                               <th>#</th>
                               <th>Name</th>
                               <th>Type</th>
                               <th>Location</th>
                               <th>Price</th>
                               <th>Duration (Days)</th>
                               <th>Max Slots</th>
                               <th>Location Type</th>
                               <th>Creation Date</th>
                               <th>Action</th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php 
                           $sql = "SELECT * FROM tbltourpackages";
                           $query = $dbh->prepare($sql);
                           $query->execute();
                           $results = $query->fetchAll(PDO::FETCH_OBJ);
                           $cnt = 1;
                           if($query->rowCount() > 0) {
                               foreach($results as $result) { ?>
                                   <tr>
                                       <td><?php echo htmlentities($cnt);?></td>
                                       <td><?php echo htmlentities($result->PackageName);?></td>
                                       <td><?php echo htmlentities($result->PackageType);?></td>
                                       <td><?php echo htmlentities($result->PackageLocation);?></td>
                                       <td>RS.<?php echo htmlentities($result->PackagePrice);?></td>
                                       <td><?php echo htmlentities($result->PackageDuration);?></td>
                                       <td><?php echo htmlentities($result->MaxSlots);?></td>
                                       <td><?php echo htmlentities($result->locationType);?></td>
                                       <td><?php echo htmlentities($result->Creationdate);?></td>
                                       <td><a href="update-package.php?pid=<?php echo htmlentities($result->PackageId);?>"><button type="button" class="btn btn-primary btn-block">View Details</button></a></td>
                                   </tr>
                               <?php $cnt++; } 
                           } ?>
                       </tbody>
                   </table>
               </div>
           </div>
       </div>
       <script>
           $(document).ready(function() {
               var navoffeset = $(".header-main").offset().top;
               $(window).scroll(function(){
                   var scrollpos = $(window).scrollTop(); 
                   if(scrollpos >= navoffeset){
                       $(".header-main").addClass("fixed");
                   } else {
                       $(".header-main").removeClass("fixed");
                   }
               });
           });
       </script>
       <div class="inner-block"></div>
       <?php include('includes/footer.php');?>
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