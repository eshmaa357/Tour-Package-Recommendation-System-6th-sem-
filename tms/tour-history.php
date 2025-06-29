<?php
session_start();
error_reporting(E_ALL);
include('includes/config.php');
include_once('includes/functions.php');

date_default_timezone_set('Asia/Kathmandu');

if (empty($_SESSION['login'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("location:index.php?show_login=true&redirect=$redirect_url");
    exit;
}

$msg = '';
$error = '';
$uemail = $_SESSION['login'];

if (!function_exists('validateBookingDates')) {
    function validateBookingDates($from_date, $to_date, $package_duration, &$error) {
        $from_date_obj = DateTime::createFromFormat('Y-m-d', $from_date);
        $to_date_obj = DateTime::createFromFormat('Y-m-d', $to_date);
        $today = new DateTime('today');
        $today->setTime(0, 0, 0);

        if (!$from_date_obj || !$to_date_obj) {
            $error = "Invalid date format.";
            return false;
        }
        $from_date_obj->setTime(0, 0, 0);
        if ($from_date_obj < $today) {
            $error = "From Date must be today or in the future.";
            return false;
        }
        if ($to_date_obj < $from_date_obj) {
            $error = "To Date must be after From Date.";
            return false;
        }
        $diff_days = $from_date_obj->diff($to_date_obj)->days + 1;
        if ($diff_days != $package_duration) {
            $error = "Selected dates do not match package duration of $package_duration day(s). Current: $diff_days day(s).";
            return false;
        }
        return true;
    }
}

if (isset($_REQUEST['bkid'])) {
    $bid = intval($_GET['bkid']);
    $sql = "SELECT FromDate, PaymentStatus, status FROM tblbooking WHERE UserEmail=:email AND BookingId=:bid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $uemail, PDO::PARAM_STR);
    $query->bindParam(':bid', $bid, PDO::PARAM_INT);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    if ($query->rowCount() > 0) {
        foreach ($results as $result) {
            $fdate = $result->FromDate;
            $payment_status = $result->PaymentStatus;
            $status = $result->status;
            
            error_log("tour-history.php: Cancellation attempt for Booking ID $bid, status=$status, PaymentStatus=$payment_status, FromDate=$fdate");

            if (($status == 0 || $status == 1) && ($payment_status == 'Paid' || $payment_status == 'Pending')) {
                $from_date_only_obj = new DateTime($fdate);
                $today_only_obj = new DateTime('today');

                if ($from_date_only_obj >= $today_only_obj) {
                    $new_status = 2;
                    $cancelby = 'user';
                    $sql = "UPDATE tblbooking SET status=:status, CancelledBy=:cancelby, UpdationDate=CURRENT_TIMESTAMP
                            WHERE UserEmail=:email AND BookingId=:bid";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':status', $new_status, PDO::PARAM_INT);
                    $query->bindParam(':cancelby', $cancelby, PDO::PARAM_STR);
                    $query->bindParam(':email', $uemail, PDO::PARAM_STR);
                    $query->bindParam(':bid', $bid, PDO::PARAM_INT);
                    if ($query->execute()) {
                        error_log("tour-history.php: Booking ID $bid cancelled by user, original status=$status");
                        $msg = "Booking cancelled successfully. No refunds will be issued.";
                    } else {
                        $error = "Failed to cancel booking.";
                        error_log("tour-history.php: Failed to cancel Booking ID $bid");
                    }
                } else {
                    $error = "You can't cancel a booking that has already started.";
                    error_log("tour-history.php: Cancellation blocked for Booking ID $bid: Started or past start date");
                }
            } else {
                $error = "Cannot cancel booking: Invalid current status ($status) or payment status ($payment_status).";
                error_log("tour-history.php: Invalid status=$status, PaymentStatus=$payment_status for Booking ID $bid");
            }
        }
    } else {
        $error = "Booking not found or you don't have permission to cancel it.";
        error_log("tour-history.php: Booking ID $bid not found or no permission");
    }
}

if (isset($_POST['edit']) && isset($_POST['booking_id'])) {
    $bid = intval($_POST['booking_id']);
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
    $new_payment_method = $_POST['payment_method'];

    error_log("tour-history.php: Form submitted with booking_id=$bid, from_date=$from_date, to_date=$to_date, PaymentMethod=$new_payment_method");

    $sql = "SELECT tblbooking.PackageId, tblbooking.FromDate, tblbooking.ToDate, tblbooking.Comment, tblbooking.PaymentMethod, tblbooking.PaymentStatus, tblbooking.ReservationStatus, tblbooking.ReservationTimeout, tblbooking.status, tbltourpackages.PackageDuration, tbltourpackages.MaxSlots
            FROM tblbooking
            JOIN tbltourpackages ON tbltourpackages.PackageId = tblbooking.PackageId
            WHERE UserEmail=:email AND BookingId=:bid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $uemail, PDO::PARAM_STR);
    $query->bindParam(':bid', $bid, PDO::PARAM_INT);
    $query->execute();
    $booking = $query->fetch(PDO::FETCH_OBJ);

    if (!$booking) {
        $error = "Booking not found.";
        error_log("tour-history.php: Booking not found for booking_id=$bid");
    } else {
        if ($booking->status == 1) {
            $error = "Cannot update now: Booking is already confirmed by admin.";
            error_log("tour-history.php: Server-side update blocked for booking_id=$bid: Confirmed by Admin");
        } elseif ($booking->status == 2) {
            $error = "Cannot update now: Booking has been cancelled.";
            error_log("tour-history.php: Server-side update blocked for booking_id=$bid: Cancelled");
        } else {
            $no_changes = (
                $from_date === $booking->FromDate &&
                $to_date === $booking->ToDate &&
                $comment === $booking->Comment &&
                $new_payment_method === $booking->PaymentMethod
            );

            if ($no_changes) {
                $error = "No changes were made.";
                error_log("tour-history.php: No changes detected for booking_id=$bid");
            } else {
                if ($booking->PaymentMethod == 'Online' && $booking->PaymentStatus == 'Paid' && $new_payment_method != 'Online') {
                    $error = "Cannot change payment method: Online payment already completed.";
                    error_log("tour-history.php: Blocked payment method change for completed online payment, booking_id=$bid");
                } else {
                    if (!validateBookingDates($from_date, $to_date, $booking->PackageDuration, $error)) {
                        error_log("tour-history.php: Date validation failed for booking_id=$bid: $error");
                    } elseif (!in_array($new_payment_method, ['Cash', 'Online'])) {
                        $error = "Invalid payment method selected.";
                        error_log("tour-history.php: Invalid payment method for booking_id=$bid");
                    } else {
                        $sql_slots = "SELECT COUNT(*) FROM tblbooking
                                      WHERE PackageId = :pid
                                      AND status IN (0, 1)
                                      AND BookingId != :bid
                                      AND NOT (ToDate < :fromDate OR FromDate > :toDate)";
                        $query_slots = $dbh->prepare($sql_slots);
                        $query_slots->bindParam(':pid', $booking->PackageId, PDO::PARAM_INT);
                        $query_slots->bindParam(':bid', $bid, PDO::PARAM_INT);
                        $query_slots->bindParam(':fromDate', $from_date, PDO::PARAM_STR);
                        $query_slots->bindParam(':toDate', $to_date, PDO::PARAM_STR);
                        $query_slots->execute();
                        $bookedSlots = $query_slots->fetchColumn();

                        error_log("tour-history.php: Booked slots=$bookedSlots, max_slots={$booking->MaxSlots} for booking_id=$bid");

                        if ($bookedSlots >= $booking->MaxSlots) {
                            $error = "Cannot update booking: Package slots are full for the selected dates.";
                            error_log("tour-history.php: Slots full for booking_id=$bid");
                        } else {
                            if ($new_payment_method == 'Online' && $booking->PaymentMethod == 'Cash' && $booking->PaymentStatus == 'Pending') {
                                $timeout = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                                $reservation_status = 'Temporary';
                                $sql = "UPDATE tblbooking SET FromDate=:from_date, ToDate=:to_date, Comment=:comment, PaymentMethod=:PaymentMethod, ReservationStatus=:reservation_status, ReservationTimeout=:timeout, UpdationDate=CURRENT_TIMESTAMP
                                        WHERE UserEmail=:email AND BookingId=:bid AND status = 0 AND PaymentStatus='Pending'";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':from_date', $from_date, PDO::PARAM_STR);
                                $query->bindParam(':to_date', $to_date, PDO::PARAM_STR);
                                $query->bindParam(':comment', $comment, PDO::PARAM_STR);
                                $query->bindParam(':PaymentMethod', $new_payment_method, PDO::PARAM_STR);
                                $query->bindParam(':reservation_status', $reservation_status, PDO::PARAM_STR);
                                $query->bindParam(':timeout', $timeout, PDO::PARAM_STR);
                                $query->bindParam(':email', $uemail, PDO::PARAM_STR);
                                $query->bindParam(':bid', $bid, PDO::PARAM_INT);
                                if ($query->execute()) {
                                    error_log("tour-history.php: Redirecting to payment.php for booking_id=$bid");
                                    header("Location: payment.php?booking_id=$bid");
                                    exit;
                                } else {
                                    $error = "Failed to update payment method.";
                                    error_log("tour-history.php: Payment method update failed for booking_id=$bid");
                                }
                            } else {
                                $sql = "UPDATE tblbooking SET FromDate=:from_date, ToDate=:to_date, Comment=:comment, PaymentMethod=:PaymentMethod, UpdationDate=CURRENT_TIMESTAMP
                                        WHERE UserEmail=:email AND BookingId=:bid AND status = 0 AND PaymentStatus IN ('Pending', 'Paid')";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':from_date', $from_date, PDO::PARAM_STR);
                                $query->bindParam(':to_date', $to_date, PDO::PARAM_STR);
                                $query->bindParam(':comment', $comment, PDO::PARAM_STR);
                                $query->bindParam(':PaymentMethod', $new_payment_method, PDO::PARAM_STR);
                                $query->bindParam(':email', $uemail, PDO::PARAM_STR);
                                $query->bindParam(':bid', $bid, PDO::PARAM_INT);
                                if ($query->execute()) {
                                    error_log("tour-history.php: Booking updated successfully for booking_id=$bid");
                                    $msg = "Booking updated successfully";
                                } else {
                                    $error = "Booking not updated. It may be cancelled or invalid.";
                                    error_log("tour-history.php: Booking update failed for booking_id=$bid");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

if (isset($_POST['submit_review']) && isset($_POST['booking_id'])) {
    $bid = intval($_POST['booking_id']);
    $pkgid = intval($_POST['package_id']);
    $rating = intval($_POST['rating']);
    $review_comment = htmlspecialchars($_POST['review_comment'], ENT_QUOTES, 'UTF-8');
    $sql = "INSERT INTO tblreviews (PackageId, UserEmail, Rating, Comment, ReviewDate, Status)
            VALUES (:pkgid, :email, :rating, :comment, CURRENT_TIMESTAMP, 0)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':pkgid', $pkgid, PDO::PARAM_INT);
    $query->bindParam(':email', $uemail, PDO::PARAM_STR);
    $query->bindParam(':rating', $rating, PDO::PARAM_INT);
    $query->bindParam(':comment', $review_comment, PDO::PARAM_STR);
    $query->execute();
    $msg = "Review submitted successfully! It will be visible after admin approval.";
    error_log("tour-history.php: Review submitted for booking_id=$bid, package_id=$pkgid");
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>T&T - Tour History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Tourism Management System In PHP" />
    <script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); } </script>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
    <link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
    <link href='//fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.12.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
    <style>
        .errorWrap { padding: 10px; margin: 0 0 20px 0; background: #fff; border-left: 4px solid #dd3d36; -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); }
        .succWrap { padding: 10px; margin: 0 0 20px 0; background: #fff; border-left: 4px solid #5cb85c; -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); }
        table th, table td { padding: 8px; text-align: center; vertical-align: middle; }
        .form-group { margin-bottom: 10px; }
        .duration-error { color: red; font-size: 12px; display: block; }
        .validation-error { color: red; font-size: 12px; margin-top: 5px; display: block; }
        .completed { color: green; font-weight: bold; }
        .info-message { color: blue; font-size: 12px; margin-top: 5px; }
        .disabled-action-text { color: gray; font-style: italic; }
        .booking-update-message {
            color: orange;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="top-header">
    <?php include('includes/header.php'); ?>
    <div class="banner-1">
        <div class="container">
            <h1 class="wow zoomIn animated" data-wow-delay=".3s">Tour and Travels</h1>
        </div>
    </div>
    <div class="privacy">
        <div class="container">
            <h3 class="wow fadeInDown animated" data-wow-delay=".3s">My Tour History</h3>
            <?php if ($error) { ?>
                <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
            <?php } elseif ($msg) { ?>
                <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
            <?php } ?>

            <table border="1" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Booking ID</th>
                        <th>Package Name</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Comment</th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                        <th>Booking Date</th>
                        <th>Action</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT tblbooking.BookingId as bookid, tblbooking.PackageId as pkgid, tbltourpackages.PackageName as packagename, tbltourpackages.PackagePrice, tbltourpackages.PackageLocation, tbltourpackages.PackageDuration, tblbooking.FromDate as fromdate, tblbooking.ToDate as todate, tblbooking.Comment as comment, tblbooking.status as status, tblbooking.RegDate as regdate, tblbooking.CancelledBy as cancelby, tblbooking.UpdationDate as upddate, tblbooking.PaymentMethod as paymentmethod, tblbooking.PaymentStatus as paymentstatus, tblbooking.CancellationReason as cancelreason
                        FROM tblbooking
                        JOIN tbltourpackages ON tbltourpackages.PackageId = tblbooking.PackageId
                        WHERE UserEmail=:uemail ORDER BY tblbooking.RegDate DESC";
                $query = $dbh->prepare($sql);
                $query->bindParam(':uemail', $uemail, PDO::PARAM_STR);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                $cnt = 1;
                $currentDate = date('Y-m-d');
                $today_obj = new DateTime('today');

                if ($query->rowCount() > 0) {
                    foreach ($results as $result) {
                        $status = $result->status ?? 0;
                        $payment_status = $result->paymentstatus ?? 'Pending';
                        $payment_method = $result->paymentmethod ?? 'Cash';
                        $package_duration = intval($result->PackageDuration);
                        $is_online_paid = ($payment_method == 'Online' && $payment_status == 'Paid');
                        $is_cash_confirmed = ($payment_method == 'Cash' && $payment_status == 'Paid' && $status == 1);
                        $display_payment_method = $is_cash_confirmed ? 'Cash (Confirmed)' : ($is_online_paid ? 'Online (Paid)' : $payment_method);
                        $is_completed = ($status == 1 && $result->todate < $currentDate);
                        $is_expired = ($status == 0 && $result->todate < $currentDate);

                        $update_message_id = "update-message-" . $result->bookid;

                        error_log("tour-history.php: Booking ID {$result->bookid}, Status=$status, PaymentMethod=$payment_method, PaymentStatus=$payment_status, FromDate={$result->fromdate}, ToDate={$result->todate}, CurrentDate=$currentDate, PackageDuration=$package_duration");
                        ?>
                        <tr>
                            <td><?php echo htmlentities($cnt); ?></td>
                            <td>#BK<?php echo htmlentities($result->bookid); ?></td>
                            <td><a href="package-details.php?pid=<?php echo htmlentities($result->pkgid); ?>&price=<?php echo urlencode($result->PackagePrice); ?>&destination=<?php echo urlencode($result->PackageLocation); ?>"><?php echo htmlentities($result->packagename); ?></a></td>
                            <td>
                                <form method="post" style="display: inline;" id="booking-form-<?php echo $result->bookid; ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo $result->bookid; ?>">
                                    <div class="form-group">
                                        <input type="date" name="from_date" id="from-date-<?php echo $result->bookid; ?>" value="<?php echo htmlentities($result->fromdate); ?>" required style="width: 120px;" data-duration="<?php echo $package_duration; ?>" min="<?php echo date('Y-m-d'); ?>">
                                        <span class="validation-error" id="from-date-error-<?php echo $result->bookid; ?>"></span>
                                    </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <input type="date" name="to_date" id="to-date-<?php echo $result->bookid; ?>" value="<?php echo htmlentities($result->todate); ?>" required style="width: 120px;" readonly>
                                    <span class="duration-error" id="duration-error-<?php echo $result->bookid; ?>"></span>
                                    <span class="validation-error" id="to-date-error-<?php echo $result->bookid; ?>"></span>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <textarea name="comment" style="width: 150px;"><?php echo htmlentities($result->comment); ?></textarea>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <?php if ($is_online_paid || $is_cash_confirmed) { ?>
                                        <span><?php echo htmlentities($display_payment_method); ?></span>
                                        <input type="hidden" name="payment_method" value="<?php echo $payment_method; ?>">
                                    <?php } else { ?>
                                        <select name="payment_method" required>
                                            <option value="Cash" <?php echo $payment_method == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                            <option value="Online" <?php echo $payment_method == 'Online' ? 'selected' : ''; ?>>Online</option>
                                        </select>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                if ($status == 2 && $result->cancelby == 'admin') {
                                    echo "Cancelled by Admin";
                                } else {
                                    echo htmlentities($payment_status);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($is_completed) { ?>
                                    <span class="completed">Tour Completed</span>
                                <?php } elseif ($is_expired) { ?>
                                    Expired
                                <?php } elseif ($status == 0) { ?>
                                    Pending
                                <?php } elseif ($status == 1) { ?>
                                    Confirmed
                                <?php } elseif ($status == 2 && $result->cancelby == 'user') { ?>
                                    Cancelled by you at <?php echo htmlentities($result->upddate); ?>
                                <?php } elseif ($status == 2 && $result->cancelby == 'admin') { ?>
                                    Cancelled by admin at <?php echo htmlentities($result->upddate); ?>
                                    <?php if (!empty($result->cancelreason)) { ?>
                                        <br><small>Reason: <?php echo htmlentities($result->cancelreason); ?></small>
                                    <?php } ?>
                                <?php } elseif ($status == 2 && $result->cancelby == 'system' && $result->paymentstatus == 'No Payment') { ?>
                                    Cancelled: No Payment by From Date
                                <?php } elseif ($status == 2 && $result->cancelby == 'system') { ?>
                                    Cancelled: Not Approved by To Date
                                <?php } else { ?>
                                    Unknown
                                <?php } ?>
                            </td>
                            <td><?php echo htmlentities($result->regdate); ?></td>
                            <td>
                                <?php if ($status == 0 || $status == 1) { ?>
                                    <button type="submit" name="edit" class="btn btn-primary btn-sm" id="update-btn-<?php echo $result->bookid; ?>">Update</button>
                                <?php } else {
                                    echo $is_completed ? 'Completed' : ($is_expired ? 'Expired' : 'N/A');
                                } ?>
                                <span class="booking-update-message" id="<?php echo $update_message_id; ?>"></span>
                                </form>

                                <?php
                                $fdate_obj = new DateTime($result->fromdate);
                                $can_cancel_php = ($fdate_obj >= $today_obj);

                                if (($status == 0 || $status == 1) && !$is_completed && !$is_expired && $can_cancel_php) { ?>
                                    <a href="tour-history.php?bkid=<?php echo htmlentities($result->bookid); ?>" onclick="return confirm('If you cancel your booking, you will not receive a refund. This action cannot be undone. Are you sure?')" class="btn btn-danger btn-sm">Cancel</a>
                                <?php } else if (($status == 0 || $status == 1) && !$is_completed && !$is_expired && !$can_cancel_php) { ?>
                                    <span class="disabled-action-text">Cannot Cancel (Tour Started/Passed)</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($status == 1 && $result->todate < $currentDate) {
                                    $review_sql = "SELECT * FROM tblreviews WHERE UserEmail=:email AND PackageId=:pkgid";
                                    $review_query = $dbh->prepare($review_sql);
                                    $review_query->bindParam(':email', $uemail, PDO::PARAM_STR);
                                    $review_query->bindParam(':pkgid', $result->pkgid, PDO::PARAM_INT);
                                    $review_query->execute();
                                    if ($review_query->rowCount() == 0) { ?>
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#reviewModal<?php echo $result->bookid; ?>">
                                            Leave a Review
                                        </button>
                                        <div class="modal fade" id="reviewModal<?php echo $result->bookid; ?>" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                                        <h4 class="modal-title" id="reviewModalLabel">Review: <?php echo htmlentities($result->packagename); ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="post">
                                                            <input type="hidden" name="booking_id" value="<?php echo $result->bookid; ?>">
                                                            <input type="hidden" name="package_id" value="<?php echo $result->pkgid; ?>">
                                                            <div class="form-group">
                                                                <label>Rating (1-5) <span style="color: red;">*</span></label>
                                                                <select name="rating" class="form-control" required>
                                                                    <option value="1">1</option>
                                                                    <option value="2">2</option>
                                                                    <option value="3">3</option>
                                                                    <option value="4">4</option>
                                                                    <option value="5">5</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Comment</label>
                                                                <textarea name="review_comment" class="form-control" rows="4"></textarea>
                                                            </div>
                                                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        Reviewed
                                    <?php } ?>
                                <?php } elseif ($status == 1 && $result->todate >= $currentDate) { ?>
                                    Review available after <?php echo htmlentities($result->todate); ?>
                                <?php } else { ?>
                                    N/A
                                <?php } ?>
                            </td>
                        </tr>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const bookingId = '<?php echo $result->bookid; ?>';
                                const status = '<?php echo $status; ?>';
                                const fromDateInput = document.getElementById('from-date-' + bookingId);
                                const toDateInput = document.getElementById('to-date-' + bookingId);
                                const updateBtn = document.getElementById('update-btn-' + bookingId);
                                const fromDateError = document.getElementById('from-date-error-' + bookingId);
                                const toDateError = document.getElementById('to-date-error-' + bookingId);
                                const durationError = document.getElementById('duration-error-' + bookingId);
                                const updateMessageSpan = document.getElementById('update-message-' + bookingId);
                                const durationDays = parseInt(fromDateInput?.dataset.duration) || 0;

                                console.log(`Booking ${bookingId}: Status=${status}, FromDate=${fromDateInput.value}, ToDate=${toDateInput.value}, PackageDuration=${durationDays}`);

                                if (fromDateInput && toDateInput && updateBtn && durationDays) {
                                    function updateToDate() {
                                        const fromDateValue = fromDateInput.value;
                                        if (fromDateValue) {
                                            const fromDate = new Date(fromDateValue);
                                            const today = new Date('<?php echo date('Y-m-d'); ?>');
                                            today.setHours(0, 0, 0, 0);
                                            fromDate.setHours(0, 0, 0, 0);

                                            if (fromDate < today) {
                                                fromDateError.textContent = 'From Date must be today or in the future.';
                                                toDateInput.value = '';
                                                updateBtn.disabled = true;
                                            } else {
                                                fromDateError.textContent = '';
                                                const newToDate = new Date(fromDate);
                                                newToDate.setDate(newToDate.getDate() + durationDays);
                                                toDateInput.value = newToDate.toISOString().split('T')[0];
                                                validateDuration();
                                            }
                                        } else {
                                            toDateInput.value = '';
                                            fromDateError.textContent = 'From Date cannot be empty.';
                                            updateBtn.disabled = true;
                                        }
                                    }

                                    function validateDuration() {
                                        const fromDateValue = fromDateInput.value;
                                        const toDateValue = toDateInput.value;
                                        let currentErrors = false;

                                        fromDateError.textContent = '';
                                        toDateError.textContent = '';
                                        durationError.textContent = '';
                                        updateMessageSpan.textContent = '';

                                        if (!fromDateValue || !toDateValue) {
                                            fromDateError.textContent = 'Please select both From and To dates.';
                                            currentErrors = true;
                                        } else {
                                            const fromDate = new Date(fromDateValue);
                                            const toDate = new Date(toDateValue);
                                            fromDate.setHours(0, 0, 0, 0);
                                            toDate.setHours(0, 0, 0, 0);

                                            if (toDate < fromDate) {
                                                toDateError.textContent = 'To Date must be after From Date.';
                                                currentErrors = true;
                                            } else {
                                                const diffTime = toDate.getTime() - fromDate.getTime();
                                                const calculatedDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
                                                console.log(`Booking ${bookingId}: CalculatedDays=${calculatedDays}, ExpectedDuration=${durationDays}`);

                                                if (calculatedDays !== durationDays) {
                                                    durationError.textContent = `Duration must be ${durationDays} day(s). Current: ${calculatedDays} day(s).`;
                                                    currentErrors = true;
                                                }
                                            }

                                            const today = new Date('<?php echo date('Y-m-d'); ?>');
                                            today.setHours(0, 0, 0, 0);
                                            if (fromDate < today) {
                                                fromDateError.textContent = 'From Date must be today or in the future.';
                                                currentErrors = true;
                                            }
                                        }

                                        updateBtn.disabled = currentErrors || status === '1';
                                    }

                                    fromDateInput.addEventListener('change', updateToDate);
                                    toDateInput.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        toDateError.textContent = 'To Date is set automatically based on From Date.';
                                    });
                                    toDateInput.addEventListener('keydown', function(e) {
                                        e.preventDefault();
                                        toDateError.textContent = 'To Date is set automatically based on From Date.';
                                    });

                                    document.getElementById('booking-form-' + bookingId).addEventListener('submit', function(e) {
                                        if (status === '1') {
                                            e.preventDefault();
                                            updateMessageSpan.textContent = 'Cannot update now: Booking is already confirmed by admin.';
                                            updateMessageSpan.style.display = 'block';
                                        } else {
                                            validateDuration();
                                            if (updateBtn.disabled) {
                                                e.preventDefault();
                                            }
                                        }
                                    });

                                    updateBtn.addEventListener('click', function(e) {
                                        if (status === '1') {
                                            e.preventDefault();
                                            updateMessageSpan.textContent = 'Cannot update now: Booking is already confirmed by admin.';
                                            updateMessageSpan.style.display = 'block';
                                        }
                                    });

                                    fromDateInput.addEventListener('focus', function() { updateMessageSpan.textContent = ''; });
                                    toDateInput.addEventListener('focus', function() { updateMessageSpan.textContent = ''; });
                                    const commentTextarea = document.querySelector(`#booking-form-${bookingId} textarea[name="comment"]`);
                                    if (commentTextarea) commentTextarea.addEventListener('focus', function() { updateMessageSpan.textContent = ''; });
                                    const paymentMethodSelect = document.querySelector(`#booking-form-${bookingId} select[name="payment_method"]`);
                                    if (paymentMethodSelect) paymentMethodSelect.addEventListener('focus', function() { updateMessageSpan.textContent = ''; });

                                    updateToDate();
                                }
                            });
                        </script>
                        <?php
                        $cnt++;
                    }
                } else {
                    echo "<tr><td colspan='12'>No bookings found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
    <?php include('includes/signup.php'); ?>
    <?php include('includes/signin.php'); ?>
    <?php include('includes/write-us.php'); ?>
</body>
</html>