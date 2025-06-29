<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
?>

<!DOCTYPE HTML>
<html>
<head>
<title>T&T - Package List</title>
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
    $(document).ready(function() {
        $('.view').on('click', function(e) {
            console.log('Details link clicked: ' + $(this).attr('href'));
        });
    });
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
.rom-btm {
    margin-bottom: 20px;
}
.pagination {
    margin-top: 20px;
}
.pagination .btn {
    margin-right: 5px;
}
.recommendation {
    margin-top: 20px;
    border: 1px solid #ddd;
    padding: 15px;
}
</style>
</head>
<body>
<?php include('includes/header.php');?>
<div class="banner-3">
    <div class="container">
        <h1 class="wow zoomIn animated" data-wow-delay=".3s"> T&T - Package List</h1>
    </div>
</div>
<div class="rooms">
    <div class="container">
        <div class="room-bottom">
            <?php
            if (isset($_GET['error'])) {
                if ($_GET['error'] == 'package_not_found') {
                    echo '<div class="errorWrap"><strong>ERROR</strong>: Package not found.</div>';
                } elseif ($_GET['error'] == 'user_banned') {
                    echo '<div class="errorWrap"><strong>ERROR</strong>: Your account has been banned.</div>';
                } elseif ($_GET['error'] == 'reference_package_not_found') {
                    echo '<div class="errorWrap"><strong>ERROR</strong>: Reference package for recommendations not found.</div>';
                }
            }
            if (isset($_GET['login_error']) && $_GET['login_error'] == 'true') {
                echo '<div class="errorWrap"><strong>ERROR</strong>: Invalid email or password.</div>';
            }
            ?>
            <h3>Package List</h3>
            <?php
            // Recommendation logic for users with bookings
            if (isset($_SESSION['login'])) {
                $sql = "SELECT PackageId FROM tblbooking 
                        WHERE UserEmail = :userEmail AND status = 1 
                        ORDER BY RegDate DESC LIMIT 1";
                $sth = $dbh->prepare($sql);
                $sth->bindParam(':userEmail', $_SESSION['login'], PDO::PARAM_STR);
                $sth->execute();
                $latestBooked = $sth->fetch(PDO::FETCH_OBJ);
                if ($latestBooked) {
                    echo '<div class="recommendation">';
                    echo '<h4>Recommendations Based on Your Last Booking</h4>';
                    $pkgid = $latestBooked->PackageId;
                    error_log("package-list.php: Setting pkgid=$pkgid for recommendations");
                    try {
                        include('recommend.php');
                    } catch (Exception $e) {
                        error_log("package-list.php: Error including recommend.php: " . $e->getMessage());
                        echo '<p>Unable to generate recommendations at this time.</p>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="recommendation"><p>No previous bookings to generate recommendations.</p></div>';
                }
            }

            // Pagination settings
            $packages_per_page = 5;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $packages_per_page;

            // Fetch packages with pagination
            $sql = "SELECT * FROM tbltourpackages LIMIT :limit OFFSET :offset";
            $query = $dbh->prepare($sql);
            $query->bindParam(':limit', $packages_per_page, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);
            // echo "<div class='debugWrap'>Debug: Found " . $query->rowCount() . " packages in tbltourpackages</div>";

            // Count total packages for pagination
            $total_sql = "SELECT COUNT(*) FROM tbltourpackages";
            $total_query = $dbh->prepare($total_sql);
            $total_query->execute();
            $total_packages = $total_query->fetchColumn();
            $total_pages = ceil($total_packages / $packages_per_page);

            if ($query->rowCount() > 0) {
                foreach ($results as $result) {
                    ?>
                    <div class="rom-btm">
                        <div class="col-md-3 room-left wow fadeInLeft animated" data-wow-delay=".3s">
                            <img src="/tms/tms/admin/pacakgeimages/<?php echo htmlentities($result->PackageImage); ?>" class="img-responsive" alt="">
                        </div>
                        <div class="col-md-6 room-midle wow fadeInUp animated" data-wow-delay=".3s">
                            <h4>Package Name: <?php echo htmlentities($result->PackageName); ?></h4>
                            <h6>Package Type: <?php echo htmlentities($result->PackageType); ?></h6>
                            <p><b>Package Location:</b> <?php echo htmlentities($result->PackageLocation); ?></p>
                            <p><b>Features:</b> <?php echo htmlentities($result->PackageFetures); ?></p>
                        </div>
                        <div class="col-md-3 room-right wow fadeInRight animated" data-wow-delay=".3s">
                            <h5>RS <?php echo htmlentities($result->PackagePrice); ?></h5>
                            <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($result->PackageId); ?>&price=<?php echo urlencode($result->PackagePrice); ?>&destination=<?php echo urlencode($result->PackageLocation); ?>" class="view">Details</a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No packages found.</p>";
            }
            ?>
            <?php 
            // Pagination Navigation
            if ($total_pages > 1) { ?>
                <div class="pagination">
                    <?php if ($page > 1) { ?>
                        <a href="/tms/tms/package-list.php?page=<?php echo $page - 1; ?>" class="btn btn-default">Previous</a>
                    <?php } ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                        <a href="/tms/tms/package-list.php?page=<?php echo $i; ?>" class="btn btn-<?php echo $i == $page ? 'primary' : 'default'; ?>"><?php echo $i; ?></a>
                    <?php } ?>
                    <?php if ($page < $total_pages) { ?>
                        <a href="/tms/tms/package-list.php?page=<?php echo $page + 1; ?>" class="btn btn-default">Next</a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php include('includes/footer.php'); ?>
<?php include('includes/signup.php'); ?>
<?php include('includes/signin.php'); ?>
<?php include('includes/write-us.php'); ?>
<?php if (isset($_GET['login_error']) && $_GET['login_error'] == 'true') { ?>
<script>
    $(document).ready(function() {
        $('#myModal4').modal('show');
    });
</script>
<?php } ?>
</body>
</html>