<?php
// No session_start() here; it’s in parent files
if (isset($_SESSION['login']) && $_SESSION['login']) {
?>
<div class="top-header">
    <div class="container">
        <ul class="tp-hd-lft wow fadeInLeft animated" data-wow-delay=".3s">
            <li class="hm"><a href="index.php"><i class="fa fa-home"></i></a></li>
            <li class="prnt"><a href="profile.php">My Profile</a></li>
            <li class="prnt"><a href="change-password.php">Change Password</a></li>
            <li class="prnt"><a href="tour-history.php">My Tour History</a></li>
            <li class="prnt"><a href="issuetickets.php">Issue Tickets</a></li>
        </ul>
        <ul class="tp-hd-rgt wow fadeInRight animated" data-wow-delay=".3s"> 
            <li class="tol">Welcome :</li>                
            <li class="sig"><?php echo htmlentities($_SESSION['login']);?></li> 
            <li class="sigi"><a href="logout.php">/ Logout</a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<?php } else { ?>
<div class="top-header">
    <div class="container">
        <ul class="tp-hd-lft wow fadeInLeft animated" data-wow-delay=".3s">
            <li class="hm"><a href="index.php"><i class="fa fa-home"></i></a></li>
            <li class="hm"><a href="admin/index.php">Admin Login</a></li>
        </ul>
        <ul class="tp-hd-rgt wow fadeInRight animated" data-wow-delay=".3s"> 
            <li class="sig"><a href="#" data-toggle="modal" data-target="#myModal">Sign Up</a></li> 
            <li class="sigi"><a href="#" data-toggle="modal" data-target="#myModal4">/ Sign In</a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<?php } ?>

<!--- /top-header ---->
<!--- header ---->
<div class="header">
    <div class="container">
        <div class="logo wow fadeInDown animated" data-wow-delay=".3s">
            <a href="index.php">Tour<span> and Travels </span></a>    
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<!--- /header ---->

<!--- footer-btm ---->
<div class="footer-btm wow fadeInLeft animated" data-wow-delay=".3s">
    <div class="container">
        <div class="navigation">
            <nav class="navbar navbar-default">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse nav-wil" id="bs-example-navbar-collapse-1">
                    <nav class="cl-effect-1">
                        <ul class="nav navbar-nav">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="page.php?type=aboutus">About</a></li>
                            <li><a href="package-list.php">Tour Packages</a></li>
                            <li><a href="page.php?type=contact">Contact Us</a></li>
                            <?php if (isset($_SESSION['login']) && $_SESSION['login']) { ?>
                                <li>Need Help?<a href="#" data-toggle="modal" data-target="#myModal3"> / Write Us </a></li>
                            <?php } else { ?>
                                <li><a href="enquiry.php"> Enquiry </a></li>
                            <?php } ?>
                            <!-- search form with single field for package details and price filter -->
                            <form action="search-results.php" method="get" class="search-form" style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; position: relative;">
                                    <input type="text" name="destination" placeholder="Enter package name, type, location, or location type..." style="width: 213px; padding: 6px; margin-left: 210px;">
                                    <button type="submit" style="position: absolute; right: 0; top: 30%; transform: translateY(-30%); border: none; background: none; margin-right: 6px;">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <input type="number" name="price" placeholder="Budget (e.g., 10000)" min="0" step="1" value="<?php echo (basename($_SERVER['PHP_SELF']) === 'search-results.php' && isset($_GET['price'])) ? htmlentities($_GET['price']) : ''; ?>" style="width: 160px; padding: 6px; font-size: 14px;">
                            </form>
                            <div class="clearfix"></div>
                        </ul>
                    </nav>
                </div>
            </nav>
        </div>
        <div class="clearfix"></div>
    </div>
</div>