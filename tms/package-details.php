<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
include_once('includes/functions.php'); // Ensure single inclusion

// Set timezone to Nepal Standard Time (UTC+05:45)
date_default_timezone_set('Asia/Kathmandu');

// Include check-user-status.php with error handling
if (file_exists('check-user-status.php')) {
    include_once('check-user-status.php');
} else {
    error_log("package-details.php: Failed to include check-user-status.php - file not found");
    if (!isset($email)) return false;
    function checkUserStatus($dbh, $email) {
        $sql = "SELECT Status FROM tblusers WHERE EmailId = :email LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_OBJ);
        if (!$user || $user->Status != 1) {
            error_log("package-details.php: User banned or not found for email: $email");
            session_destroy();
            header("Location: /tms/tms/index.php?error=user_banned");
            exit();
        }
        return true;
    }
}

$msg = '';
$error = '';
$debug = '';
$packageDuration = 1; // Default value
$maxSlots = 0; // Default value

// Debug form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("package-details.php: Form submitted with POST data: " . print_r($_POST, true));
}

error_log("package-details.php: Session login: " . (isset($_SESSION['login']) ? $_SESSION['login'] : 'Not set'));

if (isset($_POST['book']) && isset($_SESSION['login'])) {
    $user_email = $_SESSION['login'];
    if (!checkUserStatus($dbh, $user_email)) {
        exit;
    }

    $pid = intval($_GET['pid']);
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $comment = $_POST['comment'];
    $payment_method = $_POST['payment_method'];

    error_log("package-details.php: Booking attempt for pid=$pid, user=$user_email, FromDate=$from_date, ToDate=$to_date");

    $duration_sql = "SELECT PackageDuration, MaxSlots FROM tbltourpackages WHERE PackageId = :pid";
    $duration_query = $dbh->prepare($duration_sql);
    $duration_query->bindParam(':pid', $pid, PDO::PARAM_INT);
    $duration_query->execute();
    $duration_result = $duration_query->fetch(PDO::FETCH_OBJ);
    $packageDuration = $duration_result ? intval($duration_result->PackageDuration) : 1;
    $maxSlots = $duration_result ? intval($duration_result->MaxSlots) : 0;
    $debug .= "<p>Debug: PackageId = $pid, PackageDuration = $packageDuration, MaxSlots = $maxSlots</p>";

    if (empty($from_date) || empty($to_date) || empty($payment_method)) {
        $error = "Please fill in all required fields with valid values.";
        error_log("package-details.php: Missing required fields");
    } elseif (!validateBookingDates($from_date, $to_date, $packageDuration, $error)) {
        error_log("package-details.php: Date validation failed: $error");
        $debug .= "<p>Debug: Validation error - $error</p>";
    } else {
        $bookedSlots = getBookedSlots($dbh, $pid, $from_date, $to_date);
        $debug .= "<p>Debug: Booked Slots = $bookedSlots for dates $from_date to $to_date</p>";
        error_log("package-details.php: BookedSlots=$bookedSlots, MaxSlots=$maxSlots");

        if ($bookedSlots >= $maxSlots) {
            $error = "Sorry, the package is fully booked for the selected dates.";
            error_log("package-details.php: Package fully booked");
        } else {
            try {
                $reservation_status = ($payment_method == 'Online') ? 'Temporary' : 'Confirmed';
                $reservation_timeout = ($payment_method == 'Online') ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;

                $sql = "INSERT INTO tblbooking (PackageId, UserEmail, FromDate, ToDate, Comment, RegDate, PaymentMethod, PaymentStatus, ReservationStatus";
                $params = [
                    ':pid' => [$pid, PDO::PARAM_INT],
                    ':user_email' => [$user_email, PDO::PARAM_STR],
                    ':from_date' => [$from_date, PDO::PARAM_STR],
                    ':to_date' => [$to_date, PDO::PARAM_STR],
                    ':comment' => [$comment, PDO::PARAM_STR],
                    ':payment_method' => [$payment_method, PDO::PARAM_STR],
                    ':reservation_status' => [$reservation_status, PDO::PARAM_STR]
                ];

                if ($payment_method == 'Online') {
                    $sql .= ", ReservationTimeout";
                    $params[':reservation_timeout'] = [$reservation_timeout, PDO::PARAM_STR];
                }

                $sql .= ", status) VALUES (:pid, :user_email, :from_date, :to_date, :comment, CURRENT_TIMESTAMP, :payment_method, 'Pending', :reservation_status";
                if ($payment_method == 'Online') {
                    $sql .= ", :reservation_timeout";
                }
                $sql .= ", 0)";

                $query = $dbh->prepare($sql);
                foreach ($params as $param => $value) {
                    $query->bindParam($param, $value[0], $value[1]);
                }

                error_log("package-details.php: Executing SQL: $sql");
                error_log("package-details.php: Bound params: " . print_r($params, true));
                $query->execute();
                $booking_id = $dbh->lastInsertId();
                $debug .= "<p>Debug: Booking inserted with ID = $booking_id</p>";
                error_log("package-details.php: Booking insert successful, ID=$booking_id");

                if ($payment_method == 'Online') {
                    header("Location: /tms/tms/payment.php?booking_id=$booking_id");
                    exit;
                } else {
                    $msg = "Booking submitted successfully! You have chosen to pay with cash on arrival. Check your bookings in Tour History.";
                }
            } catch (PDOException $e) {
                $error = "Error submitting booking: " . $e->getMessage();
                $debug .= "<p>Debug: Booking error - " . $e->getMessage() . "</p>";
                error_log("package-details.php: Booking insert failed: " . $e->getMessage());
            }
        }
    }
}

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$price = isset($_GET['price']) ? $_GET['price'] : '';
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';

if ($pid <= 0) {
    error_log("package-details.php: Invalid or missing pid");
    header("Location: /tms/tms/package-list.php?error=invalid_package");
    exit();
}

error_log("package-details.php: Accessing with pid=$pid");
$sql = "SELECT * FROM tbltourpackages WHERE PackageId = :pid";
$query = $dbh->prepare($sql);
$query->bindParam(':pid', $pid, PDO::PARAM_INT);
$query->execute();
$result = $query->fetch(PDO::FETCH_OBJ);
error_log("package-details.php: Query returned " . ($result ? "1" : "0") . " rows for pid=$pid");

if ($result) {
    $packageDuration = intval($result->PackageDuration);
    $maxSlots = intval($result->MaxSlots);
} else {
    error_log("package-details.php: No package found for pid=$pid");
    header("Location: /tms/tms/package-list.php?error=package_not_found");
    exit();
}
?>

<!DOCTYPE HTML>
<html>
<head>
<title>T&T - Package Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="/tms/tms/css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="/tms/tms/css/style.css" rel='stylesheet' type='text/css' />
<link href="/tms/tms/css/font-awesome.css" rel="stylesheet">
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>
<link href="/tms/tms/css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="/tms/tms/js/jquery-1.12.0.min.js"></script>
<script src="/tms/tms/js/bootstrap.min.js"></script>
<script src="/tms/tms/js/wow.min.js"></script>
<script>
    new WOW().init();
    $(document).ready(function() {
        $('#bookingModal').on('shown.bs.modal', function() {
            var pid = <?php echo $pid; ?>;
            var packageDuration = <?php echo $packageDuration; ?>;
            var maxSlots = <?php echo $maxSlots; ?>;
            console.log('Modal shown, PID:', pid, 'Duration:', packageDuration, 'Max Slots:', maxSlots);

            var fromDateInput = $('input[name="from_date"]');
            var toDateInput = $('input[name="to_date"]');
            var submitBtn = $('button[name="book"]');

            fromDateInput.on('change', function() {
                var fromDateStr = $(this).val();
                console.log('Selected From Date:', fromDateStr);
                if (!fromDateStr) {
                    toDateInput.val('');
                    submitBtn.prop('disabled', true);
                    $('#slotStatus').text('Please select a valid date.').css('color', 'red');
                    return;
                }

                var fromDate = new Date(fromDateStr + 'T00:00:00+05:45'); // Nepal Standard Time
                if (isNaN(fromDate.getTime())) {
                    toDateInput.val('');
                    submitBtn.prop('disabled', true);
                    $('#slotStatus').text('Please select a valid date.').css('color', 'red');
                    return;
                }

                // Calculate To Date based on PackageDuration
                var toDate = new Date(fromDate);
                toDate.setDate(fromDate.getDate() + (packageDuration - 1));
                var toDateStr = toDate.getFullYear() + '-' + 
                                String(toDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(toDate.getDate()).padStart(2, '0');
                toDateInput.val(toDateStr);
                console.log('Calculated To Date:', toDateStr);

                // Check slot availability
                $.ajax({
                    url: '/tms/tms/check-slots.php',
                    type: 'POST',
                    data: { packageId: pid, fromDate: fromDateStr, toDate: toDateStr },
                    dataType: 'json',
                    success: function(response) {
                        var bookedSlots = parseInt(response.bookedSlots) || 0;
                        var availableSlots = maxSlots - bookedSlots;
                        console.log('AJAX Success - Booked Slots:', bookedSlots, 'Available Slots:', availableSlots);
                        $('#slotStatus').text('Available Slots: ' + Math.max(0, availableSlots) + ' / ' + maxSlots);
                        if (availableSlots <= 0) {
                            submitBtn.prop('disabled', true);
                            $('#slotStatus').css('color', 'red');
                        } else {
                            submitBtn.prop('disabled', false);
                            $('#slotStatus').css('color', 'green');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error (check-slots): ' + error);
                        $('#slotStatus').text('Error checking slots.').css('color', 'red');
                        submitBtn.prop('disabled', true);
                    }
                });
            });

            // Set default From Date to today
            var today = new Date();
            var todayStr = today.getFullYear() + '-' + 
                          String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(today.getDate()).padStart(2, '0');
            fromDateInput.val(todayStr).trigger('change');
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
.succWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.package-details img {
    max-width: 100%;
    height: auto;
    margin-bottom: 15px;
    border-radius: 4px;
}
#slotStatus {
    margin-top: 10px;
    font-weight: bold;
}
.debugWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #ffeb3b;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.recommended-package {
    background: #f9f9f9;
    border: 2px solid #5cb85c;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.recommended-package h4 {
    color: #5cb85c;
    margin-bottom: 10px;
}
.recommended-label {
    font-weight: bold;
    color: #2e7d32;
    margin-bottom: 5px;
}
</style>
</head>
<body>
<?php include('includes/header.php'); ?>
<div class="banner-1">
    <div class="container">
        <h1 class="wow zoomIn animated" data-wow-delay=".3s">Tour and Travels</h1>
    </div>
</div>
<div class="container">
    <h2>Package Details</h2>
    <?php if ($msg) { ?>
        <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
    <?php } ?>
    <?php if ($error) { ?>
        <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
    <?php } ?>
    <?php if ($debug && defined('DEBUG_MODE') && DEBUG_MODE) { ?>
        <div class="debugWrap"><?php echo $debug; ?></div>
    <?php } ?>
    <div class="package-details" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <h3><?php echo htmlentities($result->PackageName); ?></h3>
        <p><strong>Location:</strong> <?php echo htmlentities($result->PackageLocation); ?></p>
        <p><strong>Price:</strong> NPR <?php echo number_format($result->PackagePrice, 0); ?></p>
        <p><strong>Maximum Slots:</strong> <?php echo htmlentities($result->MaxSlots); ?></p>
        <p><strong>Type:</strong> <?php echo htmlentities($result->PackageType); ?></p>
        <p><strong>Location Type:</strong> <?php echo htmlentities($result->locationType); ?></p>
        <p><strong>Features:</strong> <?php echo htmlentities($result->PackageFetures); ?></p>
        <p><strong>Details:</strong> <?php echo htmlentities($result->PackageDetails); ?></p>
        <?php 
        if (empty($result->PackageImage)) {
            echo "<p><strong>Debug:</strong> PackageImage is empty or NULL.</p>";
        } else {
            $imagePath = "/tms/tms/admin/pacakgeimages/" . htmlentities($result->PackageImage);
            if (file_exists("admin/pacakgeimages/" . htmlentities($result->PackageImage))) {
                ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlentities($result->PackageName); ?>" class="package-image">
                <?php
            } else {
                echo "<p><strong>Debug:</strong> Image file not found at: $imagePath</p>";
            }
        }
        ?>
        <div style="margin-top: 10px;">
            <?php if (isset($_SESSION['login'])) { ?>
                <button class="btn btn-success" data-toggle="modal" data-target="#bookingModal">Book Now</button>
            <?php } else {
                $_SESSION['intended_pid'] = $pid;
                $_SESSION['intended_price'] = $price;
                $_SESSION['intended_destination'] = $destination;
                ?>
                <a href="#" data-toggle="modal" data-target="#myModal4" class="btn btn-success">Book Now</a>
            <?php } ?>
        </div>
    </div>
    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="bookingModalLabel">Book Package: <?php echo htmlentities($result->PackageName); ?></h4>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="form-group">
                            <label>From Date <span style="color: red;">*</span></label>
                            <input type="date" name="from_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>To Date <span style="color: red;">*</span></label>
                            <input type="date" name="to_date" class="form-control" required readonly>
                        </div>
                        <div class="form-group">
                            <label>Payment Method <span style="color: red;">*</span></label>
                            <select name="payment_method" class="form-control" required>
                                <option value="" selected>Select Payment Method</option>
                                <option value="Online">Pay Online</option>
                                <option value="Cash">Pay Cash on Arrival</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Comment (Optional)</label>
                            <textarea name="comment" class="form-control" rows="4"></textarea>
                        </div>
                        <div id="slotStatus">Please select a date to check availability.</div>
                        <button type="submit" name="book" class="btn btn-primary" disabled>Submit Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    $reviews_sql = "SELECT tblreviews.*, tblusers.FullName 
                    FROM tblreviews 
                    JOIN tblusers ON tblreviews.UserEmail = tblusers.EmailId 
                    WHERE tblreviews.PackageId = :pid AND tblreviews.Status = 1 
                    ORDER BY tblreviews.ReviewDate DESC LIMIT 5";
    $reviews_query = $dbh->prepare($reviews_sql);
    $reviews_query->bindParam(':pid', $pid, PDO::PARAM_INT);
    $reviews_query->execute();
    $reviews_results = $reviews_query->fetchAll(PDO::FETCH_OBJ);

    if ($reviews_results) {
        echo "<h3>User Reviews</h3>";
        foreach ($reviews_results as $review) {
            ?>
            <div class="review" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                <p><strong><?php echo htmlentities($review->FullName); ?></strong> (Rating: <?php echo htmlentities($review->Rating); ?>/5)</p>
                <p><?php echo htmlentities($review->Comment); ?></p>
                <p><small>Posted on: <?php echo htmlentities($review->ReviewDate); ?></small></p>
            </div>
            <?php
        }
    } else {
        echo "<p>No reviews yet. Be the first to leave a review!</p>";
    }

    echo "<h3>Recommended Packages</h3>";
    if (isset($_SESSION['login'])) {
        // Check if user has booked any packages
        $booking_sql = "SELECT COUNT(*) FROM tblbooking WHERE UserEmail = :email AND status = 1";
        $booking_query = $dbh->prepare($booking_sql);
        $booking_query->bindParam(':email', $_SESSION['login'], PDO::PARAM_STR);
        $booking_query->execute();
        $hasBookings = $booking_query->fetchColumn() > 0;

        if ($hasBookings && $pid) {
            // Show cosine similarity recommendations
            error_log("package-details.php: Setting pkgid=$pid for recommendations");
            $pkgid = $pid; // Set pkgid in scope
            try {
                include_once('recommend.php'); // Use include_once to prevent multiple inclusions
            } catch (Exception $e) {
                error_log("package-details.php: Error including recommend.php: " . $e->getMessage());
                echo '<p>Unable to generate recommendations at this time.</p>';
            }
        } else {
            // Show similar and recommended packages
            $similar_sql = "SELECT * FROM tbltourpackages WHERE PackageId != :pid";
            if ($price && $destination) {
                $similar_sql .= " AND PackagePrice <= :price AND PackageLocation LIKE :destination";
            }
            $similar_sql .= " LIMIT 3";
            $similar_query = $dbh->prepare($similar_sql);
            $similar_query->bindParam(':pid', $pid, PDO::PARAM_INT);
            if ($price) {
                $similar_query->bindParam(':price', $price, PDO::PARAM_INT);
            }
            if ($destination) {
                $similar_query->bindValue(':destination', '%' . $destination . '%', PDO::PARAM_STR);
            }
            $similar_query->execute();
            $similar_results = $similar_query->fetchAll(PDO::FETCH_OBJ);
            if ($similar_results) {
                echo "<h4>Similar Packages</h4>";
                foreach ($similar_results as $similar) {
                    ?>
                    <div class="package" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                        <h4><?php echo htmlentities($similar->PackageName); ?></h4>
                        <p>Price: NPR <?php echo number_format($similar->PackagePrice, 0); ?></p>
                        <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($similar->PackageId); ?>&price=<?php echo urlencode($price); ?>&destination=<?php echo urlencode($destination); ?>" class="btn btn-primary">View Details</a>
                    </div>
                    <?php
                }
            }

            $most_booked_sql = "SELECT tb.PackageId, tp.PackageName, tp.PackagePrice, COUNT(*) as booking_count 
                                FROM tblbooking tb 
                                JOIN tbltourpackages tp ON tb.PackageId = tp.PackageId 
                                WHERE tb.PackageId != :pid AND tb.status = 1 
                                GROUP BY tb.PackageId, tp.PackageName, tp.PackagePrice 
                                ORDER BY booking_count DESC 
                                LIMIT 1";
            $most_booked_query = $dbh->prepare($most_booked_sql);
            $most_booked_query->bindParam(':pid', $pid, PDO::PARAM_INT);
            $most_booked_query->execute();
            $most_booked = $most_booked_query->fetch(PDO::FETCH_OBJ);

            $highest_rated_sql = "SELECT tp.PackageId, tp.PackageName, tp.PackagePrice, AVG(tr.Rating) as avg_rating 
                                  FROM tbltourpackages tp 
                                  LEFT JOIN tblreviews tr ON tp.PackageId = tr.PackageId AND tr.Status = 1 
                                  WHERE tp.PackageId != :pid 
                                  GROUP BY tp.PackageId, tp.PackageName, tp.PackagePrice 
                                  ORDER BY avg_rating DESC 
                                  LIMIT 1";
            $highest_rated_query = $dbh->prepare($highest_rated_sql);
            $highest_rated_query->bindParam(':pid', $pid, PDO::PARAM_INT);
            $highest_rated_query->execute();
            $highest_rated = $highest_rated_query->fetch(PDO::FETCH_OBJ);

            $recommended_packages = [];
            if ($most_booked) {
                $recommended_packages[] = ['label' => 'Most Popular', 'package' => $most_booked];
            }
            if ($highest_rated && $highest_rated->avg_rating > 0) {
                $recommended_packages[] = ['label' => 'Top Rated', 'package' => $highest_rated];
            }

            if (!empty($recommended_packages)) {
                echo "<h4>Recommended Packages</h4>";
                foreach ($recommended_packages as $rec) {
                    ?>
                    <div class="recommended-package">
                        <div class="recommended-label"><?php echo htmlentities($rec['label']); ?></div>
                        <h4><?php echo htmlentities($rec['package']->PackageName); ?></h4>
                        <p>Price: NPR <?php echo number_format($rec['package']->PackagePrice, 0); ?></p>
                        <?php if (isset($rec['package']->avg_rating)) { ?>
                            <p>Average Rating: <?php echo number_format($rec['package']->avg_rating, 1); ?>/5</p>
                        <?php } ?>
                        <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($rec['package']->PackageId); ?>&price=<?php echo urlencode($price); ?>&destination=<?php echo urlencode($destination); ?>" class="btn btn-primary">View Details</a>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No recommended packages available.</p>";
            }
        }
    } else {
        // Recommendations for non-logged-in users
        $similar_sql = "SELECT * FROM tbltourpackages WHERE PackageId != :pid";
        if ($price && $destination) {
            $similar_sql .= " AND PackagePrice <= :price AND PackageLocation LIKE :destination";
        }
        $similar_sql .= " LIMIT 3";
        $similar_query = $dbh->prepare($similar_sql);
        $similar_query->bindParam(':pid', $pid, PDO::PARAM_INT);
        if ($price) {
            $similar_query->bindParam(':price', $price, PDO::PARAM_INT);
        }
        if ($destination) {
            $similar_query->bindValue(':destination', '%' . $destination . '%', PDO::PARAM_STR);
        }
        $similar_query->execute();
        $similar_results = $similar_query->fetchAll(PDO::FETCH_OBJ);
        if ($similar_results) {
            echo "<h4>Similar Packages</h4>";
            foreach ($similar_results as $similar) {
                ?>
                <div class="package" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                    <h4><?php echo htmlentities($similar->PackageName); ?></h4>
                    <p>Price: NPR <?php echo number_format($similar->PackagePrice, 0); ?></p>
                    <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($similar->PackageId); ?>&price=<?php echo urlencode($price); ?>&destination=<?php echo urlencode($destination); ?>" class="btn btn-primary">View Details</a>
                </div>
                <?php
            }
        }

        $most_booked_sql = "SELECT tb.PackageId, tp.PackageName, tp.PackagePrice, COUNT(*) as booking_count 
                            FROM tblbooking tb 
                            JOIN tbltourpackages tp ON tb.PackageId = tp.PackageId 
                            WHERE tb.PackageId != :pid AND tb.status = 1 
                            GROUP BY tb.PackageId, tp.PackageName, tp.PackagePrice 
                            ORDER BY booking_count DESC 
                            LIMIT 1";
        $most_booked_query = $dbh->prepare($most_booked_sql);
        $most_booked_query->bindParam(':pid', $pid, PDO::PARAM_INT);
        $most_booked_query->execute();
        $most_booked = $most_booked_query->fetch(PDO::FETCH_OBJ);

        $highest_rated_sql = "SELECT tp.PackageId, tp.PackageName, tp.PackagePrice, AVG(tr.Rating) as avg_rating 
                              FROM tbltourpackages tp 
                              LEFT JOIN tblreviews tr ON tp.PackageId = tr.PackageId AND tr.Status = 1 
                              WHERE tp.PackageId != :pid 
                              GROUP BY tp.PackageId, tp.PackageName, tp.PackagePrice 
                              ORDER BY avg_rating DESC 
                              LIMIT 1";
        $highest_rated_query = $dbh->prepare($highest_rated_sql);
        $highest_rated_query->bindParam(':pid', $pid, PDO::PARAM_INT);
        $highest_rated_query->execute();
        $highest_rated = $highest_rated_query->fetch(PDO::FETCH_OBJ);

        $recommended_packages = [];
        if ($most_booked) {
            $recommended_packages[] = ['label' => 'Most Popular', 'package' => $most_booked];
        }
        if ($highest_rated && $highest_rated->avg_rating > 0) {
            $recommended_packages[] = ['label' => 'Top Rated', 'package' => $highest_rated];
        }

        if (!empty($recommended_packages)) {
            echo "<h4>Recommended Packages</h4>";
            foreach ($recommended_packages as $rec) {
                ?>
                <div class="recommended-package">
                    <div class="recommended-label"><?php echo htmlentities($rec['label']); ?></div>
                    <h4><?php echo htmlentities($rec['package']->PackageName); ?></h4>
                    <p>Price: NPR <?php echo number_format($rec['package']->PackagePrice, 0); ?></p>
                    <?php if (isset($rec['package']->avg_rating)) { ?>
                        <p>Average Rating: <?php echo number_format($rec['package']->avg_rating, 1); ?>/5</p>
                    <?php } ?>
                    <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($rec['package']->PackageId); ?>&price=<?php echo urlencode($price); ?>&destination=<?php echo urlencode($destination); ?>" class="btn btn-primary">View Details</a>
                </div>
                <?php
            }
        } else {
            echo "<p>No recommended packages available.</p>";
        }
    }

    // Other packages
    $exclude_ids = [];
    if (isset($recommended_packages) && !empty($recommended_packages)) {
        foreach ($recommended_packages as $rec) {
            $exclude_ids[] = $rec['package']->PackageId;
        }
    }
    $other_sql = "SELECT * FROM tbltourpackages WHERE PackageId != :pid";
    if (!empty($exclude_ids)) {
        $other_sql .= " AND PackageId NOT IN (" . implode(',', array_map('intval', $exclude_ids)) . ")";
    }
    $other_sql .= " LIMIT 3";
    $other_query = $dbh->prepare($other_sql);
    $other_query->bindParam(':pid', $pid, PDO::PARAM_INT);
    $other_query->execute();
    $other_results = $other_query->fetchAll(PDO::FETCH_OBJ);
    if ($other_results) {
        echo "<h3>Other Packages</h3>";
        foreach ($other_results as $other) {
            ?>
            <div class="package" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                <h4><?php echo htmlentities($other->PackageName); ?></h4>
                <p>Price: NPR <?php echo number_format($other->PackagePrice, 0); ?></p>
                <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($other->PackageId); ?>" class="btn btn-primary">View Details</a>
            </div>
            <?php
        }
    }
    ?>
</div>
<?php include('includes/footer.php'); ?>
<?php include('includes/signup.php'); ?>
<?php include('includes/signin.php'); ?>
<?php include('includes/write-us.php'); ?>
<?php if (isset($_GET['open_booking']) && $_GET['open_booking'] == 'true' && isset($_SESSION['login'])) { ?>
    <script>
        $(document).ready(function() {
            $('#bookingModal').modal('show');
        });
    </script>
<?php } ?>
</body>
</html>