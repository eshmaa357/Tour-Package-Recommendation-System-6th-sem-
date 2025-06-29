<?php
session_start();
include('includes/config.php');
?>
<!DOCTYPE HTML>
<html>
<head>
<title>T&T | Search Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link href="css/font-awesome.css" rel="stylesheet">
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>
<link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="js/jquery-1.12.0.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/wow.min.js"></script>
<script>
    new WOW().init();
    $(document).ready(function() {
        $('[data-toggle="modal"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('data-target');
            if ($(target).length) {
                $(target).modal('show');
            } else {
                console.log('Modal ' + target + ' not found');
            }
        });
    });
</script>
</head>
<body>
<?php include('includes/header.php'); ?>
<div class="container">
    <h2>Search Results</h2>
    <p>
        <?php
        $has_search_term = !empty($_GET['destination']);
        $has_price = !empty($_GET['price']) && $_GET['price'] !== '';

        if ($has_search_term || $has_price) {
            $search_criteria = [];
            if ($has_search_term) {
                $search_criteria[] = "Packages, Destinations, Package Types, or Location Types matching: " . htmlentities($_GET['destination']);
            }
            if ($has_price) {
                $search_criteria[] = "Packages up to NPR " . number_format($_GET['price'], 0);
            }
            echo "Searching for: " . implode(" | ", $search_criteria);
        } else {
            echo "Please enter a package name, destination, package type, location type, or budget to search.";
        }
        ?>
    </p>
    <?php
    if ($has_search_term || $has_price) {
        $sql = "SELECT * FROM tbltourpackages";
        $params = [];
        $conditions = [];

        if ($has_search_term) {
            $search_term = '%' . $_GET['destination'] . '%';
            $conditions[] = "(PackageName LIKE :search_term OR PackageLocation LIKE :search_term OR PackageType LIKE :search_term OR locationType LIKE :search_term)";
            $params[':search_term'] = $search_term;
        }

        if ($has_price) {
            $price = (float)$_GET['price'];
            $conditions[] = "PackagePrice <= :input_price";
            $params[':input_price'] = $price;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY PackagePrice DESC LIMIT 3";

        $query = $dbh->prepare($sql);
        $query->execute($params);
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {
            echo "<p>Found " . $query->rowCount() . " package(s) matching your criteria:</p>";
            foreach ($results as $result) {
                ?>
                <div class="package" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
                    <h3><?php echo htmlentities($result->PackageName); ?></h3>
                    <p>Location: <?php echo htmlentities($result->PackageLocation); ?></p>
                    <p>Price: NPR <?php echo number_format($result->PackagePrice, 0); ?></p>
                    <p>Package Type: <?php echo htmlentities($result->PackageType); ?></p>
                    <p>Location Type: <?php echo htmlentities($result->locationType); ?></p>
                    <a href="package-details.php?pid=<?php echo $result->PackageId; ?>&price=<?php echo urlencode($has_price ? $_GET['price'] : ''); ?>&destination=<?php echo urlencode($has_search_term ? $_GET['destination'] : ''); ?>" class="btn btn-primary">View Details</a>
                </div>
                <?php
            }
        } else {
            echo "<p>No packages found";
            $no_results_criteria = [];
            if ($has_search_term) {
                $no_results_criteria[] = "matching '" . htmlentities($_GET['destination']) . "' in package name, destination, package type, or location type";
            }
            if ($has_price) {
                $no_results_criteria[] = "up to NPR " . number_format($_GET['price'], 0);
            }
            if (!empty($no_results_criteria)) {
                echo " " . implode(" and ", $no_results_criteria);
            }
            echo ". Please try a different search term or higher budget.</p>";
        }
    }
    ?>
</div>
<?php include('includes/footer.php'); ?>
<?php include('includes/signup.php'); ?>
<?php include('includes/signin.php'); ?>
</body>
</html>