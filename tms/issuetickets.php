<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
 include('check-login.php'); 
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}

// Handle issue submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_issue'])) {
    $issue = $_POST['issue'];
    $description = $_POST['description'];

    $sql = "INSERT INTO tblissues (UserEmail, Issue, Description, PostingDate) VALUES (:email, :issue, :description, NOW())";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $_SESSION['login'], PDO::PARAM_STR);
    $query->bindParam(':issue', $issue, PDO::PARAM_STR);
    $query->bindParam(':description', $description, PDO::PARAM_STR);
    try {
        $query->execute();
        $msg = "Issue submitted successfully!";
    } catch (PDOException $e) {
        $error = "Error submitting issue: " . $e->getMessage();
    }
}

// Debug: Check session
echo "<!-- Session login: " . ($_SESSION['login'] ?? 'Not set') . " -->";
?>

<!DOCTYPE HTML>
<html>
<head>
<title>Tour and Travels</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Tourism Management System In PHP" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>
<link href="css/font-awesome.css" rel="stylesheet">
<!-- Custom Theme files -->
<script src="js/jquery-1.12.0.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!--animate-->
<link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="js/wow.min.js"></script>
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
    .succWrap {
        padding: 10px;
        margin: 0 0 20px 0;
        background: #fff;
        border-left: 4px solid #5cb85c;
        -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
        box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    }
    .debug {
        color: red;
        font-weight: bold;
    }
    .issue-form {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #ccc;
        background: #f9f9f9;
    }
</style>
</head>
<body>
<!-- top-header -->
<div class="top-header">
<?php include('includes/header.php');?>
<div class="banner-1">
    <div class="container">
        <h1 class="wow zoomIn animated animated" data-wow-delay=".5s" style="visibility: visible; animation-delay: 0.5s; animation-name: zoomIn;">Tour and Travels</h1>
    </div>
</div>
<!--- /banner-1 ---->
<!--- privacy ---->
<div class="privacy">
    <div class="container">
        <h3 class="wow fadeInDown animated animated" data-wow-delay=".5s" style="visibility: visible; animation-delay: 0.5s; animation-name: fadeInDown;">Issue Tickets</h3>
        <?php if (isset($error)) { ?><div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div><?php } ?>
        <?php if (isset($msg)) { ?><div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div><?php } ?>

        <!-- Issue Submission Form -->
        <div class="issue-form">
            <h4>Submit a New Issue</h4>
            <form method="post" action="">
                <div>
                    <label for="issue">Issue:</label>
                    <input type="text" name="issue" id="issue" required>
                </div>
                <div>
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <div>
                    <button type="submit" name="submit_issue" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>

        <p>
        <table border="1" width="100%">
            <tr align="center">
                <th>#</th>
                <th>Ticket Id</th>
                <th>Issue</th>	
                <th>Description</th>
                <th>Admin Remark</th>
                <th>Reg Date</th>
                <th>Remark Date</th>
            </tr>
            <?php 
            $uemail = $_SESSION['login'];
            $sql = "SELECT * FROM tblissues WHERE UserEmail = :uemail";
            $query = $dbh->prepare($sql);
            $query->bindParam(':uemail', $uemail, PDO::PARAM_STR);
            try {
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                echo "<!-- Debug: Row count: " . $query->rowCount() . " -->";
                $cnt = 1;
                if ($query->rowCount() > 0) {
                    foreach ($results as $result) {
                        ?>
                        <tr align="center">
                            <td><?php echo htmlentities($cnt);?></td>
                            <td>#TKT-<?php echo htmlentities($result->id);?></td>
                            <td><?php echo htmlentities($result->Issue);?></td>
                            <td><?php echo htmlentities($result->Description);?></td>
                            <td><?php echo htmlentities($result->AdminRemark ?? '');?></td>
                            <td><?php echo htmlentities($result->PostingDate);?></td>
                            <td><?php echo htmlentities($result->AdminremarkDate ?? '');?></td>
                        </tr>
                        <?php 
                        $cnt++;
                    }
                } else {
                    echo "<tr><td colspan='7'>No issues found.</td></tr>";
                }
            } catch (PDOException $e) {
                echo "<tr><td colspan='7'>Error fetching issues: " . $e->getMessage() . "</td></tr>";
            }
            ?>
        </table>
        </p>
    </div>
</div>
<!--- /privacy ---->
<!--- footer-top ---->
<!--- /footer-top ---->
<?php include('includes/footer.php');?>
<!-- signup -->
<?php include('includes/signup.php');?>			
<!-- //signu -->
<!-- signin -->
<?php include('includes/signin.php');?>			
<!-- //signin -->
<!-- write us -->
<?php include('includes/write-us.php');?>
</body>
</html>
<?php  ?>