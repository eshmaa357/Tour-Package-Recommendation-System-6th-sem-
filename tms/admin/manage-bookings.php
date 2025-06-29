<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Keep for debugging, revert to error_reporting(0) in production
include('includes/config.php');
date_default_timezone_set('Asia/Kathmandu');

// Include PHPMailer classes (still needed if you ever switch to real sending)
// Adjust this path based on where you installed PHPMailer
// If you used Composer:
require_once __DIR__ . '/../vendor/autoload.php';

// If you manually downloaded and placed it in includes/PHPMailer:
// require_once __DIR__ . '/../includes/PHPMailer/src/Exception.php';
// require_once __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
// require_once __DIR__ . '/../includes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (strlen($_SESSION['alogin']) == 0) { 
    header('location:index.php');
    exit;
}

// Initialize variables
$error = '';
$msg = '';

/**
 * Robust Email Sending Function using PHPMailer.
 * Requires PHPMailer library and SMTP configuration.
 * MODIFIED: For college project, this now simulates email sending.
 */
function sendEmailNotification($to, $subject, $message, $isHtml = true) {
    // --- START: MODIFIED FOR COLLEGE PROJECT (SIMULATED EMAIL SENDING) ---
    // Instead of trying to connect to a real SMTP server with dummy credentials,
    // we'll just log the email attempt as a success.
    error_log("SIMULATED EMAIL SEND for College Project:");
    error_log("  To: " . $to);
    error_log("  Subject: " . $subject);
    error_log("  Message (plain text preview): " . strip_tags($message));
    error_log("--------------------------------------------------");
    return true; // Always return true to simulate a successful send
    // --- END: MODIFIED FOR COLLEGE PROJECT (SIMULATED EMAIL SENDING) ---


    // Keep the actual PHPMailer code commented out below if you might use it later,
    // otherwise, you can remove it.
    /*
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        // Server settings for Gmail SMTP (recommended for local testing)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        // IMPORTANT: Replace with your actual Gmail address and App Password
        $mail->Username = 'your_gmail_address@gmail.com';   // Your actual Gmail address
        $mail->Password = 'your_generated_app_password';    // The 16-character App Password generated from Google Account Security
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS for port 587
        $mail->Port = 587; // TCP port to connect to

        // Recipients
        // IMPORTANT: Replace with your actual Gmail and a suitable sender name
        $mail->setFrom('your_gmail_address@gmail.com', 'Tour & Travel Admin'); 
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHtml); // Set email format to HTML or plain text
        $mail->Subject = $subject;
        $mail->Body     = $message;
        $mail->AltBody = strip_tags($message); // Plain text for non-HTML mail clients

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("EMAIL SEND ERROR to $to (Subject: $subject): {$mail->ErrorInfo}");
        return false;
    }
    */
}

function getBookedSlots($dbh, $packageId, $fromDate, $toDate, $excludeBookingId = null) {
    $sql = "SELECT COUNT(*) FROM tblbooking 
            WHERE PackageId = :pid 
            AND status = 1 
            AND (
                (FromDate BETWEEN :fromDate AND :toDate)
                OR (ToDate BETWEEN :fromDate AND :toDate)
                OR (:fromDate BETWEEN FromDate AND ToDate)
                OR (:toDate BETWEEN FromDate AND ToDate)
            )";
    if ($excludeBookingId !== null) {
        $sql .= " AND BookingId != :excludeId";
    }

    $query = $dbh->prepare($sql);
    $query->bindParam(':pid', $packageId, PDO::PARAM_INT);
    $query->bindParam(':fromDate', $fromDate, PDO::PARAM_STR);
    $query->bindParam(':toDate', $toDate, PDO::PARAM_STR);
    if ($excludeBookingId !== null) {
        $query->bindParam(':excludeId', $excludeBookingId, PDO::PARAM_INT);
    }

    $query->execute();
    return $query->fetchColumn();
}

// Cancel booking
if (isset($_REQUEST['bkid'])) {
    $bid = intval($_GET['bkid']);
    $status = 2; // Status for cancelled booking
    $cancelby = 'admin'; // 'admin' for admin cancellation
    // Get the reason from URL. Use 'No reason provided' if empty or not set.
    $cancelReason = isset($_GET['cancelreason']) && $_GET['cancelreason'] !== '' ? urldecode($_GET['cancelreason']) : 'No reason provided';

    // Fetch booking details to check payment method/status and user email
    $stmtBookingDetails = $dbh->prepare("SELECT PaymentMethod, PaymentStatus, UserEmail, PackageId, FromDate, ToDate FROM tblbooking WHERE BookingId = :bid");
    $stmtBookingDetails->bindParam(':bid', $bid, PDO::PARAM_INT);
    $stmtBookingDetails->execute();
    $booking = $stmtBookingDetails->fetch(PDO::FETCH_OBJ);

    if ($booking) {
        // Update the booking including the cancellation reason
        // Note: Using CancellationReason as per your database column fix
        $sql = "UPDATE tblbooking SET status=:status, CancelledBy=:cancelby, CancellationReason=:cancelreason, UpdationDate=CURRENT_TIMESTAMP WHERE BookingId=:bid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $status, PDO::PARAM_INT);
        $query->bindParam(':cancelby', $cancelby, PDO::PARAM_STR);
        $query->bindParam(':cancelreason', $cancelReason, PDO::PARAM_STR);
        $query->bindParam(':bid', $bid, PDO::PARAM_INT);
        
        if ($query->execute()) {
            $msg = "Booking Cancelled successfully.";
            error_log("Booking ID $bid cancelled by admin. Reason: $cancelReason");

            // Fetch package name for the email
            $packageName = 'N/A';
            if ($booking->PackageId) {
                $sqlPackage = "SELECT PackageName FROM tbltourpackages WHERE PackageId = :pid";
                $queryPackage = $dbh->prepare($sqlPackage);
                $queryPackage->bindParam(':pid', $booking->PackageId, PDO::PARAM_INT);
                $queryPackage->execute();
                $packageName = $queryPackage->fetchColumn() ?: 'N/A';
            }

            // Send Email Notification to User
            if ($booking->UserEmail) {
                $subject = "Your Booking Cancellation - Booking ID: #BK$bid";
                $body = "Dear User,<br><br>";
                $body .= "We regret to inform you that your booking (ID: <strong>#BK$bid</strong>) for the ";
                $body .= "<strong>$packageName</strong> package from <strong>" . htmlentities($booking->FromDate) . "</strong> to <strong>" . htmlentities($booking->ToDate) . "</strong> has been cancelled by the administrator.<br><br>";
                $body .= "<strong>Reason for cancellation:</strong> " . htmlentities($cancelReason) . "<br><br>";
                
                if ($booking->PaymentMethod == 'Online' && $booking->PaymentStatus == 'Paid') {
                    $body .= "Please note that this booking was paid online. A refund will be processed according to our refund policy. Please allow a few business days for the refund to reflect in your account.<br><br>";
                }
                $body .= "If you have any questions, please contact us.<br><br>";
                $body .= "Sincerely,<br>Your Tour & Travel Team";

                if (sendEmailNotification($booking->UserEmail, $subject, $body, true)) { // true for HTML email
                    error_log("Cancellation email notification triggered for {$booking->UserEmail} for Booking ID $bid (simulated)");
                    $msg .= " Email notification sent to user (simulated).";
                } else {
                    $error .= " Failed to send email notification (simulated)."; // This line will rarely be hit with simulation
                    error_log("Failed to send cancellation email to {$booking->UserEmail} for Booking ID $bid (simulated)");
                }
            } else {
                error_log("User email not found for Booking ID $bid. Cannot send cancellation email.");
            }
        } else {
            $error = "Failed to cancel booking.";
            error_log("Failed to update status for Booking ID $bid.");
        }
    } else {
        $error = "Booking not found for cancellation.";
        error_log("Booking ID $bid not found for cancellation.");
    }
}

// Confirm booking
if (isset($_REQUEST['bckid'])) {
    $bcid = intval($_GET['bckid']);

    $sqlBooking = "SELECT PackageId, FromDate, ToDate, UserEmail FROM tblbooking WHERE BookingId = :bcid"; // Added UserEmail
    $queryBooking = $dbh->prepare($sqlBooking);
    $queryBooking->bindParam(':bcid', $bcid, PDO::PARAM_INT);
    $queryBooking->execute();
    $booking = $queryBooking->fetch(PDO::FETCH_OBJ);

    if ($booking) {
        $packageId = $booking->PackageId;
        $fromDate = $booking->FromDate;
        $toDate = $booking->ToDate;
        $userEmail = $booking->UserEmail; // Get user email

        $sqlSlots = "SELECT PackageName, MaxSlots FROM tbltourpackages WHERE PackageId = :pid"; // Added PackageName
        $querySlots = $dbh->prepare($sqlSlots);
        $querySlots->bindParam(':pid', $packageId, PDO::PARAM_INT);
        $querySlots->execute();
        $packageDetails = $querySlots->fetch(PDO::FETCH_OBJ);

        $maxSlots = $packageDetails->MaxSlots ?: 0;
        $packageName = $packageDetails->PackageName ?: 'N/A';

        $bookedSlots = getBookedSlots($dbh, $packageId, $fromDate, $toDate, $bcid); // Exclude current booking when checking slots
        
        // If the current booking itself is counted as a "new" slot being confirmed
        // The check should be if (current booked slots + 1 (for this booking)) <= maxslots
        if (($bookedSlots + 1) <= $maxSlots) {
            $status = 1; // Status for confirmed booking
            $sql = "UPDATE tblbooking SET status=:status, UpdationDate=CURRENT_TIMESTAMP WHERE BookingId=:bcid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':status', $status, PDO::PARAM_INT);
            $query->bindParam(':bcid', $bcid, PDO::PARAM_INT);
            
            if ($query->execute()) {
                error_log("Booking ID $bcid confirmed by admin");
                $msg = "Booking Confirmed successfully.";

                // Send Confirmation Email
                if ($userEmail) {
                    $subject = "Your Tour Booking for $packageName is Confirmed!";
                    $messageBody = "Dear User,<br><br>Good news! Your booking (ID: <strong>#BK$bcid</strong>) for the tour package <strong>$packageName</strong> from <strong>" . htmlentities($fromDate) . "</strong> to <strong>" . htmlentities($toDate) . "</strong> has been Confirmed by our administration.<br><br>Thank you for choosing us!<br><br>Best Regards,<br>Tour & Travel Team";
                    
                    if (sendEmailNotification($userEmail, $subject, $messageBody, true)) {
                        $msg .= " Email notification sent (simulated).";
                        error_log("Confirmation email notification triggered for {$userEmail} for Booking ID $bcid (simulated)");
                    } else {
                        $error .= " Failed to send email notification (simulated).";
                        error_log("Failed to send confirmation email to {$userEmail} for Booking ID $bcid (simulated)");
                    }
                }
            } else {
                $error = "Failed to confirm booking.";
                error_log("Failed to update status for Booking ID $bcid.");
            }
        } else {
            $error = "Cannot confirm booking: Package slots are full for the selected dates. (Booked: {$bookedSlots}, Max: {$maxSlots})";
            error_log("Cannot confirm Booking ID $bcid: Slots full ($bookedSlots/$maxSlots)");
        }
    } else {
        $error = "Booking not found.";
        error_log("Booking ID $bcid not found for confirmation");
    }
}

// Mark payment as paid
if (isset($_REQUEST['payid'])) {
    $payid = intval($_GET['payid']);
    $payment_status = 'Paid';

    // Check payment method and booking details
    $sqlCheck = "SELECT PaymentMethod, PaymentStatus, UserEmail, PackageId, FromDate, ToDate FROM tblbooking WHERE BookingId = :payid"; // Added UserEmail, PackageId, FromDate, ToDate
    $queryCheck = $dbh->prepare($sqlCheck);
    $queryCheck->bindParam(':payid', $payid, PDO::PARAM_INT);
    $queryCheck->execute();
    $booking = $queryCheck->fetch(PDO::FETCH_OBJ);

    if ($booking) {
        if ($booking->PaymentMethod == 'Online' && $booking->PaymentStatus == 'Paid') {
            $error = "Cannot mark as paid: Online payments are typically updated automatically via payment gateway. This booking is already marked Paid.";
            error_log("Attempt to manually mark online payment as Paid for Booking ID $payid blocked, already Paid.");
        } else if ($booking->PaymentMethod == 'Online' && $booking->PaymentStatus != 'Paid') {
             $error = "Cannot manually mark as paid: Online payments must be processed through the payment gateway.";
             error_log("Attempt to manually mark online payment as Paid for Booking ID $payid blocked.");
        }
        else {
            // For Cash/Bank Transfer payments, update payment status
            $sql = "UPDATE tblbooking SET PaymentStatus=:payment_status, PaymentDate=CURRENT_TIMESTAMP, UpdationDate=CURRENT_TIMESTAMP WHERE BookingId=:payid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':payment_status', $payment_status, PDO::PARAM_STR);
            $query->bindParam(':payid', $payid, PDO::PARAM_INT);
            
            if ($query->execute()) {
                error_log("Booking ID $payid marked as Paid, PaymentMethod={$booking->PaymentMethod}");
                $msg = "Payment status updated to Paid.";

                // Fetch package name for email
                $packageName = 'N/A';
                if ($booking->PackageId) {
                    $sqlPackage = "SELECT PackageName FROM tbltourpackages WHERE PackageId = :pid";
                    $queryPackage = $dbh->prepare($sqlPackage);
                    $queryPackage->bindParam(':pid', $booking->PackageId, PDO::PARAM_INT);
                    $queryPackage->execute();
                    $packageName = $queryPackage->fetchColumn() ?: 'N/A';
                }

                // Send Payment Confirmation Email
                if ($booking->UserEmail) {
                    $subject = "Payment Confirmed for Your Booking - ID: #BK$payid";
                    $messageBody = "Dear User,<br><br>";
                    $messageBody .= "This is to confirm that we have received your payment for booking (ID: <strong>#BK$payid</strong>) for the <strong>$packageName</strong> package from <strong>" . htmlentities($booking->FromDate) . "</strong> to <strong>" . htmlentities($booking->ToDate) . "</strong>.<br><br>";
                    $messageBody .= "Your payment method was <strong>" . htmlentities($booking->PaymentMethod) . "</strong> and is now marked as <strong>Paid</strong>.<br><br>";
                    $messageBody .= "We look forward to your tour! You can track your booking status in your account dashboard.<br><br>";
                    $messageBody .= "Best Regards,<br>Tour & Travel Team";
                    
                    if (sendEmailNotification($booking->UserEmail, $subject, $messageBody, true)) {
                        $msg .= " Email notification sent (simulated).";
                        error_log("Payment confirmation email notification triggered for {$booking->UserEmail} for Booking ID $payid (simulated)");
                    } else {
                        $error .= " Failed to send email notification (simulated).";
                        error_log("Failed to send payment confirmation email to {$booking->UserEmail} for Booking ID $payid (simulated)");
                    }
                }
            } else {
                $error = "Failed to update payment status.";
                error_log("Failed to update payment status for Booking ID $payid.");
            }
        }
    } else {
        $error = "Booking not found.";
        error_log("Booking ID $payid not found for payment update");
    }
}

// Initialize filter dates for the main query
$filterFromDate = '';
$filterToDate = '';
$queryParams = [];

if (isset($_GET['fromDateFilter']) && $_GET['fromDateFilter'] != '') {
    $filterFromDate = $_GET['fromDateFilter'];
}
if (isset($_GET['toDateFilter']) && $_GET['toDateFilter'] != '') {
    $filterToDate = $_GET['toDateFilter'];
}

?>
<!DOCTYPE HTML>
<html>
<head>
<title>TTA | Admin Manage Bookings</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="css/bootstrap.min.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link rel="stylesheet" href="css/morris.css" type="text/css"/>
<link href="css/font-awesome.css" rel="stylesheet"> 
<script src="js/jquery-2.1.4.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/table-style.css" />
<link rel="stylesheet" type="text/css" href="css/basictable.css" />
<script src="js/jquery.basictable.min.js"></script>

<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="//code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
    $(document).ready(function() {
        $('#table').basictable();

        // Initialize datepickers
        $("#fromDateFilter").datepicker({
            dateFormat: "yy-mm-dd", // Set date format to YYYY-MM-DD
            onSelect: function(selectedDate) {
                // When 'From' date is selected, set the minimum date for 'To' date
                $("#toDateFilter").datepicker("option", "minDate", selectedDate);
            }
        });

        $("#toDateFilter").datepicker({
            dateFormat: "yy-mm-dd", // Set date format to YYYY-MM-DD
            onSelect: function(selectedDate) {
                // When 'To' date is selected, set the maximum date for 'From' date
                $("#fromDateFilter").datepicker("option", "maxDate", selectedDate);
            }
        });

        // Handle Cancel Booking Click - Modified for reason and online payment handling
        $(document).on('click', '.cancel-booking-btn', function(e) {
            e.preventDefault(); // Prevent default link behavior
            var bookId = $(this).data('bookid');
            var paymentMethod = $(this).data('paymentmethod');
            var paymentStatus = $(this).data('paymentstatus');

            // Predefined cancellation reasons
            var reasons = [
                "User requested cancellation",
                "Payment not received",
                "Booking error/duplicate",
                "Package slots overbooked",
                "Unforeseen circumstances",
                "Other (please specify)"
            ];

            var reasonText = '';
            
            // Construct the prompt message with numbered options
            let promptMessage = "Select a cancellation reason (type number or text):\n\n";
            reasons.forEach((reason, index) => {
                promptMessage += (index + 1) + ". " + reason + "\n";
            });
            promptMessage += "\nOr type your own reason.";

            var selectedInput = prompt(promptMessage, reasons[0]); // Pre-fill with the first reason

            if (selectedInput === null) { // User clicked Cancel on the prompt
                return false;
            }

            selectedInput = selectedInput.trim(); // Trim whitespace

            // Try to match by number or exact text
            let matched = false;
            for (let i = 0; i < reasons.length; i++) {
                if (selectedInput === (i + 1).toString() || selectedInput.toLowerCase() === reasons[i].toLowerCase()) {
                    if (reasons[i] === "Other (please specify)") {
                        reasonText = prompt("Please specify the 'Other' reason for cancellation:");
                        if (reasonText === null || reasonText.trim() === '') {
                            alert("Cancellation aborted: 'Other' reason not specified.");
                            return false;
                        }
                    } else {
                        reasonText = reasons[i];
                    }
                    matched = true;
                    break;
                }
            }

            if (!matched) {
                // If no match, use the typed input as the reason
                reasonText = selectedInput;
            }
            
            if (reasonText === null || reasonText.trim() === '') {
                alert("Cancellation aborted: A reason must be provided.");
                return false;
            }

            let confirmationMessage = 'Are you sure you want to cancel booking ID ' + bookId + ' for the following reason?\n\nReason: ' + reasonText;
            
            // Add warning for online paid bookings if cancelling
            if (paymentMethod === 'Online' && paymentStatus === 'Paid') {
                confirmationMessage += '\n\nWARNING: This is an online PAID booking. Ensure manual refund processing is handled.';
            }

            if (confirm(confirmationMessage)) {
                // Redirect to the cancellation URL with the reason
                window.location.href = 'manage-bookings.php?bkid=' + bookId + '&cancelreason=' + encodeURIComponent(reasonText);
            }
        });
    });
</script>

<link href='//fonts.googleapis.com/css?family=Roboto:700,500,300,100italic,100,400' rel='stylesheet' type='text/css'/>
<link href='//fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="css/icon-font.min.css" type='text/css' />
<style>
.errorWrap { padding: 10px; background: #fff; border-left: 4px solid #dd3d36; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); }
.succWrap { padding: 10px; background: #fff; border-left: 4px solid #5cb85c; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); }
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
    <li class="breadcrumb-item"><a href="dashboard.php">Home</a><i class="fa fa-angle-right"></i>Manage Bookings</li>
</ol>
<div class="agile-grids">      
<?php if ($error) { ?>
    <div class="errorWrap"><strong>ERROR</strong>: <?php echo htmlentities($error); ?></div>
<?php } elseif ($msg) { ?>
    <div class="succWrap"><strong>SUCCESS</strong>: <?php echo htmlentities($msg); ?></div>
<?php } ?>

    <div class="date-filter-form" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; background: #f9f9f9;">
        <h3>Filter Bookings by Date</h3>
        <form method="GET" action="manage-bookings.php">
            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group">
                    <label for="fromDateFilter">From Date:</label>
                    <input type="text" class="form-control" id="fromDateFilter" name="fromDateFilter" value="<?php echo isset($_GET['fromDateFilter']) ? htmlentities($_GET['fromDateFilter']) : ''; ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="toDateFilter">To Date:</label>
                    <input type="text" class="form-control" id="toDateFilter" name="toDateFilter" value="<?php echo isset($_GET['toDateFilter']) ? htmlentities($_GET['toDateFilter']) : ''; ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="manage-bookings.php" class="btn btn-default">Clear Filter</a>
            </div>
        </form>
    </div>
    <div class="agile-tables">
    <div class="w3l-table-info">
        <h2>Manage Bookings</h2>
        <table id="table">
        <thead>
<tr>
    <th>Booking ID</th>
    <th>Name</th>
    <th>Mobile No.</th>
    <th>Email ID</th>
    <th>Package</th>
    <th>From / To</th>
    <th>Comment</th>
    <th>Status</th>
    <th>Payment Method</th>
    <th>Payment Status</th>
    <th>Slots Used / Max</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php
// Main SQL query for fetching bookings
$sql = "SELECT tblbooking.BookingId as bookid, 
tblbooking.PaymentStatus as paymentstatus, 
tblbooking.PaymentMethod as paymentmethod, 
tblbooking.CancellationReason as cancelreason_db,
tblusers.FullName as fname, 
tblusers.MobileNumber as mnumber, 
tblusers.EmailId as email, 
tbltourpackages.PackageName as pckname, 
tbltourpackages.MaxSlots as maxslots,
tblbooking.PackageId as pid, 
tblbooking.FromDate as fdate, 
tblbooking.ToDate as tdate, 
tblbooking.Comment as comment, 
tblbooking.status as status, 
tblbooking.CancelledBy as cancelby, 
tblbooking.UpdationDate as upddate 
FROM tblbooking 
LEFT JOIN tblusers ON tblbooking.UserEmail = tblusers.EmailId 
LEFT JOIN tbltourpackages ON tblbooking.PackageId = tbltourpackages.PackageId
WHERE 1"; // Start with WHERE 1 for easy appending of conditions

if ($filterFromDate != '') {
    $sql .= " AND tblbooking.FromDate >= :fromDateFilter";
    $queryParams[':fromDateFilter'] = $filterFromDate;
}
if ($filterToDate != '') {
    $sql .= " AND tblbooking.ToDate <= :toDateFilter";
    $queryParams[':toDateFilter'] = $filterToDate;
}

// Add ordering for better presentation (optional)
$sql .= " ORDER BY tblbooking.BookingId DESC";


$query = $dbh->prepare($sql);

// Bind parameters dynamically
foreach ($queryParams as $param => $value) {
    $query->bindValue($param, $value);
}

$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

if ($query->rowCount() > 0) {
    foreach ($results as $result) {
        // Correctly calculate booked slots: exclude current if already cancelled or pending confirmation.
        // It should count only *confirmed* bookings for the date range, excluding the one being checked
        // if it's not yet confirmed.
        $currentBookingStatusForSlotCheck = ($result->status == 1) ? $result->bookid : null; 
        $bookedSlots = getBookedSlots($dbh, $result->pid, $result->fdate, $result->tdate, $currentBookingStatusForSlotCheck);
        
        // If the current booking is pending (0) and not yet confirmed, it *might* take a slot.
        // If it's already confirmed (1), it's already counted in getBookedSlots.
        // If it's cancelled (2), it doesn't take a slot.
        // The display logic should reflect the actual count if this booking gets confirmed.
        $displaySlotsCurrentBookingPotential = ($result->status == 0) ? ($bookedSlots + 1) : $bookedSlots;

        $maxSlots = $result->maxslots ?: 0;
?>
<tr<?php if ($result->status == 0 && $result->fdate <= date('Y-m-d', strtotime('+1 day'))) { echo ' style="background-color: #ffe4e1;"'; } ?>>
    <td>#BK<?php echo htmlentities($result->bookid); ?></td>
    <td><?php echo htmlentities($result->fname ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->mnumber ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->email ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->pckname ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->fdate ?? 'N/A'); ?> / <?php echo htmlentities($result->tdate ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->comment ?? 'N/A'); ?></td>
    <td>
        <?php 
        if ($result->status == 0) {
            echo "Pending";
        } elseif ($result->status == 1) {
            echo "Confirmed";
        } elseif ($result->status == 2) {
            echo "Cancelled by " . htmlentities($result->cancelby ?? 'Unknown');
            if (!empty($result->cancelreason_db)) {
                echo "<br>Reason: " . htmlentities($result->cancelreason_db);
            }
        }
        ?>
    </td>
    <td><?php echo htmlentities($result->paymentmethod ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($result->paymentstatus ?? 'N/A'); ?></td>
    <td><?php echo htmlentities($bookedSlots) . " / " . htmlentities($maxSlots); ?></td>
    <td>
        <?php
        if ($result->status == 0) { // If booking is pending (not confirmed, not cancelled)
            // Option to Mark as Paid for Cash, unpaid bookings
            if ($result->paymentmethod == 'Cash' && $result->paymentstatus != 'Paid') {
            ?>
                <a href="manage-bookings.php?payid=<?php echo htmlentities($result->bookid); ?>" onclick="return confirm('Mark payment as paid?')">Mark Paid</a> |
            <?php
            }

            // Confirm option for all pending bookings (Cash paid, or Online paid)
            // Allow confirm if cash (paid/unpaid) or online paid, AND slots are available
            if (($result->paymentstatus == 'Paid' || $result->paymentmethod == 'Cash') && ($bookedSlots + 1) <= $maxSlots) {
            ?>
                <a href="manage-bookings.php?bckid=<?php echo htmlentities($result->bookid); ?>" onclick="return confirm('Do you really want to confirm this booking?')">Confirm</a> |
            <?php
            } else if (($result->paymentstatus == 'Paid' || $result->paymentmethod == 'Cash') && ($bookedSlots + 1) > $maxSlots) {
                // Display 'Slots Full' if it would exceed max slots
                echo "<span style='color:red;'>Slots Full</span> | ";
            }
            
            // Cancel option for all pending bookings (Cash paid/unpaid, or Online paid/unpaid)
            // The JS handles the prompt for reason and online payment warning.
            ?>
            <a href="javascript:void(0);" class="cancel-booking-btn" 
                data-bookid="<?php echo htmlentities($result->bookid); ?>" 
                data-paymentmethod="<?php echo htmlentities($result->paymentmethod); ?>" 
                data-paymentstatus="<?php echo htmlentities($result->paymentstatus); ?>">Cancel</a>
            <?php
        } elseif ($result->status == 1) { // If booking is Confirmed
            // If Confirmed, only allow cancellation by admin if necessary
            ?>
            <a href="javascript:void(0);" class="cancel-booking-btn" 
                data-bookid="<?php echo htmlentities($result->bookid); ?>" 
                data-paymentmethod="<?php echo htmlentities($result->paymentmethod); ?>" 
                data-paymentstatus="<?php echo htmlentities($result->paymentstatus); ?>">Cancel</a>
            <?php
        } else { // If booking is Cancelled (status == 2)
            echo "Closed";
        }
        ?>
    </td>
</tr>
<?php } } else { ?>
<tr>
    <td colspan="12">No Bookings Found</td>
</tr>
<?php } ?>
</tbody>
</table>
    </div>
</div>
</div>
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
<?php include('includes/footer.php');?>
<script src="js/bootstrap.min.js"></script>
</body>
</html>