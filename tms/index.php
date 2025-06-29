<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once('includes/config.php');

// Log session data
error_log("index.php: Session login=" . (isset($_SESSION['login']) ? $_SESSION['login'] : 'none'));
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Tour and Travels</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="/tms/tms/css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="/tms/tms/css/style.css" rel='stylesheet' type='text/css' />
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>
<link href="/tms/tms/css/font-awesome.css" rel="stylesheet">
<script src="/tms/tms/js/jquery-1.12.0.min.js"></script>
<script src="/tms/tms/js/bootstrap.min.js"></script>
<link href="/tms/tms/css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="/tms/tms/js/wow.min.js"></script>
<script>
    new WOW().init();
</script>
<style>
.errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
</style>
</head>
<?php
if (isset($_SESSION['login']) && isset($_GET['redirect'])) {
    ?>
    <script>
        window.location.href = '<?php echo urldecode($_GET['redirect']); ?>';
    </script>
    <?php
}
?>
<body>
<?php include('includes/header.php');?>
<div class="banner">
    <div class="container">
        <h1 class="wow zoomIn animated animated" data-wow-delay=".3s" style="visibility: visible; animation-delay: 0.3s; animation-name: zoomIn;"> Tour and Travels </h1>
    </div>
</div>
<div class="container">
    <div class="holiday">
        <?php
        if (isset($_GET['error'])) {
            if ($_GET['error'] == 'package_not_found') {
                echo '<div class="errorWrap"><strong>ERROR</strong>: Package not found.</div>';
            } elseif ($_GET['error'] == 'user_banned') {
                echo '<div class="errorWrap"><strong>ERROR</strong>: Your account has been banned. Please contact support.</div>';
            }
        }
        if (isset($_GET['login_error']) && $_GET['login_error'] == 'true') {
            echo '<div class="errorWrap"><strong>ERROR</strong>: Invalid email or password.</div>';
        }
        ?>
        <h3>Package List</h3>
        <?php
        // Recommendations for logged-in users
        if (isset($_SESSION['login'])) {
            $sql = "SELECT PackageId FROM tblbooking 
                    WHERE UserEmail = :userEmail AND status IN (0, 1) 
                    ORDER BY RegDate DESC LIMIT 1";
            try {
                $sth = $dbh->prepare($sql);
                $sth->bindParam(':userEmail', $_SESSION['login'], PDO::PARAM_STR);
                $sth->execute();
                $latestBooked = $sth->fetch(PDO::FETCH_OBJ);
                error_log("index.php: Booking query result=" . ($latestBooked ? "PackageId={$latestBooked->PackageId}" : 'none'));
                if ($latestBooked) {
                    echo '<h4>Recommendations Based on Your Recent Booking</h4>';
                    $pkgid = $latestBooked->PackageId;
                    error_log("index.php: Setting pkgid=$pkgid for recommendations");
                    try {
                        include('recommend.php');
                    } catch (Exception $e) {
                        error_log("index.php: Error including recommend.php: " . $e->getMessage());
                        echo '<p>Unable to generate recommendations at this time.</p>';
                    }
                } else {
                    echo '<p>No confirmed or pending bookings found. Explore packages below!</p>';
                    $sql = "SELECT * FROM tbltourpackages ORDER BY RAND() LIMIT 4";
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    if ($query->rowCount() > 0) {
                        foreach ($results as $result) { ?>
                            <div class="rom-btm">
                                <div class="col-md-3 room-left wow fadeInLeft animated" data-wow-delay=".3s">
                                    <img src="/tms/tms/admin/pacakgeimages/<?php echo htmlentities($result->PackageImage);?>" class="img-responsive" alt="">
                                </div>
                                <div class="col-md-6 room-midle wow fadeInUp animated" data-wow-delay=".3s">
                                    <h4>Package Name: <?php echo htmlentities($result->PackageName);?></h4>
                                    <h6>Package Type: <?php echo htmlentities($result->PackageType);?></h6>
                                    <p><b>Package Location:</b> <?php echo htmlentities($result->PackageLocation);?></p>
                                    <p><b>Features:</b> <?php echo htmlentities($result->PackageFetures);?></p>
                                </div>
                                <div class="col-md-3 room-right wow fadeInRight animated" data-wow-delay=".3s">
                                    <h5>RS <?php echo htmlentities($result->PackagePrice);?></h5>
                                    <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($result->PackageId);?>&price=<?php echo urlencode($result->PackagePrice);?>&destination=<?php echo urlencode($result->PackageLocation);?>" class="view">Details</a>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        <?php }
                    } else {
                        echo '<p>No packages available at this time.</p>';
                    }
                }
            } catch (PDOException $e) {
                error_log("index.php: SQL error: " . $e->getMessage());
                echo '<p>Error fetching bookings. Please try again later.</p>';
            }
            echo '<a href="/tms/tms/package-list.php?page=1" class="view">View All Packages</a>';
        } else {
            $sql = "SELECT * FROM tbltourpackages ORDER BY RAND() LIMIT 4";
            try {
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                if ($query->rowCount() > 0) {
                    foreach ($results as $result) { ?>
                        <div class="rom-btm">
                            <div class="col-md-3 room-left wow fadeInLeft animated" data-wow-delay=".3s">
                                <img src="/tms/tms/admin/pacakgeimages/<?php echo htmlentities($result->PackageImage);?>" class="img-responsive" alt="">
                            </div>
                            <div class="col-md-6 room-midle wow fadeInUp animated" data-wow-delay=".3s">
                                <h4>Package Name: <?php echo htmlentities($result->PackageName);?></h4>
                                <h6>Package Type: <?php echo htmlentities($result->PackageType);?></h6>
                                <p><b>Package Location:</b> <?php echo htmlentities($result->PackageLocation);?></p>
                                <p><b>Features:</b> <?php echo htmlentities($result->PackageFetures);?></p>
                            </div>
                            <div class="col-md-3 room-right wow fadeInRight animated" data-wow-delay=".3s">
                                <h5>RS <?php echo htmlentities($result->PackagePrice);?></h5>
                                <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($result->PackageId);?>&price=<?php echo urlencode($result->PackagePrice);?>&destination=<?php echo urlencode($result->PackageLocation);?>" class="view">Details</a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    <?php }
                } else {
                    echo '<p>No packages available at this time.</p>';
                }
            } catch (PDOException $e) {
                error_log("index.php: SQL error for packages: " . $e->getMessage());
                echo '<p>Error fetching packages. Please try again later.</p>';
            }
            echo '<div><a href="/tms/tms/package-list.php?page=1" class="view">View More Packages</a></div>';
        }
        ?>
    </div>
    <div class="clearfix"></div>
</div>
<div class="routes">
    <div class="container">
        <div class="col-md-4 routes-left wow fadeInRight animated" data-wow-delay=".3s">
            <div class="rou-left">
                <a href="#"><i class="glyphicon glyphicon-list-alt"></i></a>
            </div>
            <div class="rou-rgt wow fadeInDown animated" data-wow-delay=".3s">
                <h3>8000</h3>
                <p>Enquiries</p>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="col-md-4 routes-left">
            <div class="rou-left">
                <a href="#"><i class="fa fa-user"></i></a>
            </div>
            <div class="rou-rgt">
                <h3>1500</h3>
                <p>Registered users</p>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="col-md-4 routes-left wow fadeInRight animated" data-wow-delay=".3s">
            <div class="rou-left">
                <a href="#"><i class="fa fa-ticket"></i></a>
            </div>
            <div class="rou-rgt">
                <h3>7,00,000+</h3>
                <p>Booking</p>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<?php include('includes/footer.php');?>
<?php include('includes/signup.php');?>
<?php include('includes/signin.php');?>
<?php include('includes/write-us.php');?>
<?php if (isset($_GET['login_error']) && $_GET['login_error'] == 'true' || isset($_GET['show_login']) && $_GET['show_login'] == 'true') { ?>
<script>
    $(document).ready(function() {
        $('#myModal4').modal('show');
    });
</script>
<?php } ?>
<script>
$(document).ready(function() {
    $('.view').on('click', function(e) {
        console.log('Details link clicked: ' + $(this).attr('href'));
    });
});
</script>
</body>
</html>