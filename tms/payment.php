<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('check-login.php');
include('includes/config.php');
include_once('includes/functions.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}

$msg = '';
$error = '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Clear any previous payment messages to prevent interference
unset($_SESSION['payment_msg']);
unset($_SESSION['payment_error']);

error_log("payment.php: Session login={$_SESSION['login']}, booking_id=$booking_id");

// Fetch booking and package details
$sql = "SELECT tblbooking.*, tbltourpackages.PackageName, tbltourpackages.PackagePrice 
        FROM tblbooking 
        JOIN tbltourpackages ON tbltourpackages.PackageId = tblbooking.PackageId 
        WHERE tblbooking.BookingId = :booking_id AND tblbooking.UserEmail = :user_email";
$query = $dbh->prepare($sql);
$query->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
$query->bindParam(':user_email', $_SESSION['login'], PDO::PARAM_STR);
$query->execute();
$booking = $query->fetch(PDO::FETCH_OBJ);

error_log("payment.php: Booking fetch result=" . ($booking ? json_encode([
    'BookingId' => $booking->BookingId,
    'PaymentMethod' => $booking->PaymentMethod,
    'PaymentStatus' => $booking->PaymentStatus,
    'ReservationStatus' => $booking->ReservationStatus,
    'ReservationTimeout' => $booking->ReservationTimeout,
    'status' => $booking->status
]) : 'null'));

// Validate booking
if ($booking) {
    if ($booking->PaymentStatus == 'Paid' || $booking->PaymentStatus == 'Completed') {
        $error = "Payment already processed for this booking.";
        error_log("payment.php: Payment already processed, PaymentStatus='{$booking->PaymentStatus}' for booking_id=$booking_id");
    } elseif ($booking->PaymentStatus != 'Pending') {
        $error = "Invalid booking: PaymentStatus is '" . ($booking->PaymentStatus ?: 'NULL') . "' instead of 'Pending'.";
        error_log("payment.php: Invalid PaymentStatus '{$booking->PaymentStatus}' for booking_id=$booking_id");
    } elseif ($booking->PaymentMethod == 'Online' && $booking->ReservationStatus != 'Temporary') {
        $error = "Invalid booking: ReservationStatus is '" . ($booking->ReservationStatus ?: 'NULL') . "' instead of 'Temporary' for Online payment.";
        error_log("payment.php: Invalid ReservationStatus '{$booking->ReservationStatus}' for booking_id=$booking_id");
    } elseif ($booking->ReservationStatus == 'Temporary' && $booking->ReservationTimeout && strtotime($booking->ReservationTimeout) < time()) {
        $sql = "UPDATE tblbooking SET ReservationStatus = 'Expired', PaymentStatus = 'Cancelled', status = 2 
                WHERE BookingId = :booking_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
        $query->execute();
        $error = "Reservation has expired.";
        error_log("payment.php: Reservation expired for booking_id=$booking_id, timeout={$booking->ReservationTimeout}");
    }
} else {
    $error = "Invalid booking: Booking not found.";
    error_log("payment.php: Booking not found for booking_id=$booking_id, user_email={$_SESSION['login']}");
}

// Handle change payment method
if (isset($_POST['change_method']) && $booking && !$error) {
    $new_method = $_POST['new_method'] === 'Online' ? 'Online' : 'Cash';
    $timeout = ($new_method == 'Online') ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
    $reservation_status = ($new_method == 'Online') ? 'Temporary' : 'Confirmed';
    $status = ($new_method == 'Online') ? 0 : 0; // Keep status=0 for pending admin confirmation

    $update_sql = "UPDATE tblbooking SET PaymentMethod = :new_method, ReservationStatus = :reservationstatus, ReservationTimeout = :timeout, status = :status WHERE BookingId = :booking_id";
    $update_query = $dbh->prepare($update_sql);
    $update_query->bindParam(':new_method', $new_method, PDO::PARAM_STR);
    $update_query->bindParam(':reservationstatus', $reservation_status, PDO::PARAM_STR);
    $update_query->bindParam(':timeout', $timeout, PDO::PARAM_STR);
    $update_query->bindParam(':status', $status, PDO::PARAM_INT);
    $update_query->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $update_query->execute();
    $msg = "Payment method changed to $new_method.";
    error_log("payment.php: Payment method changed to $new_method for booking_id=$booking_id");
    header("Location: payment.php?booking_id=$booking_id");
    exit;
}

// Handle payment submission
if (isset($_POST['submit_payment']) && $booking && !$error && $booking->PaymentMethod == 'Online') {
    $card_number = preg_replace('/\D/', '', $_POST['card_number']);
    $expiry_date = trim($_POST['expiry_date']);
    $cvv = trim($_POST['cvv']);

    error_log("payment.php: Payment submission for booking_id=$booking_id, card_ending=" . substr($card_number, -4));

    // Validation
    $errors = [];

    if (!preg_match('/^\d{16}$/', $card_number)) {
        $errors[] = "Card number must be exactly 16 digits.";
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date)) {
        $errors[] = "Expiry date must be in MM/YY format.";
    } else {
        $exp_month = substr($expiry_date, 0, 2);
        $exp_year = '20' . substr($expiry_date, 3, 2);
        $last_day = cal_days_in_month(CAL_GREGORIAN, intval($exp_month), intval($exp_year));
        $exp_date = DateTime::createFromFormat('Y-m-d', "$exp_year-$exp_month-$last_day");
        $now = new DateTime('now', new DateTimeZone('Asia/Kathmandu'));

        if ($exp_date === false || $exp_date < $now) {
            $errors[] = "Expiry date must be valid and not in the past.";
        }
    }

    if (!preg_match('/^\d{3}$/', $cvv)) {
        $errors[] = "CVV must be exactly 3 digits.";
    }

    if (empty($errors)) {
        // Check slots again
        $bookedSlots = getBookedSlots($dbh, $booking->PackageId, $booking->FromDate, $booking->ToDate, $booking_id);
        $maxSlotsQuery = $dbh->prepare("SELECT MaxSlots FROM tbltourpackages WHERE PackageId = :pid");
        $maxSlotsQuery->bindParam(':pid', $booking->PackageId, PDO::PARAM_INT);
        $maxSlotsQuery->execute();
        $maxSlots = $maxSlotsQuery->fetchColumn() ?: 0;

        error_log("payment.php: Slot check for booking_id=$booking_id, booked=$bookedSlots, max=$maxSlots");

        if ($bookedSlots >= $maxSlots) {
            $error = "Cannot complete payment: Package slots are full.";
            error_log("payment.php: Slots full for booking_id=$booking_id");
        } else {
            // Masked card storage
            $last4 = substr($card_number, -4);
            $masked = "XXXX-XXXX-XXXX-$last4";

            // Update to confirm payment but keep booking status as pending
            $update_sql = "UPDATE tblbooking 
                           SET PaymentStatus = 'Paid', PaymentDate = CURRENT_TIMESTAMP, ReservationStatus = 'Confirmed', status = 0 
                           WHERE BookingId = :booking_id AND PaymentStatus = 'Pending'";
            $update_query = $dbh->prepare($update_sql);
            $update_query->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $update_query->execute();
            if ($update_query->rowCount() > 0) {
                // Recheck slot count after update
                $newBookedSlots = getBookedSlots($dbh, $booking->PackageId, $booking->FromDate, $booking->ToDate);
                $msg = "Payment successful using card ending in $last4. Awaiting admin approval. Slots used: $newBookedSlots/$maxSlots.";
                error_log("payment.php: Payment completed for booking_id=$booking_id, PaymentStatus='Paid', status=0, slots_used=$newBookedSlots");
                header("Location: tour-history.php");
                exit;
            } else {
                $error = "Payment failed: Booking already processed or invalid.";
                error_log("payment.php: Payment update failed for booking_id=$booking_id");
            }
        }
    } else {
        $error = implode(" ", $errors);
        error_log("payment.php: Payment validation errors: $error");
    }
}

// Handle cash payment
if (isset($_POST['confirm_cash']) && $booking && !$error && $booking->PaymentMethod == 'Cash') {
    $update_sql = "UPDATE tblbooking 
                   SET PaymentStatus = 'Pending', ReservationStatus = 'Confirmed', status = 0, ReservationTimeout = NULL 
                   WHERE BookingId = :booking_id";
    $update_query = $dbh->prepare($update_sql);
    $update_query->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $update_query->execute();
    $msg = "Cash payment confirmed. Pay on arrival. Awaiting admin confirmation.";
    error_log("payment.php: Cash payment confirmed for booking_id=$booking_id");
    header("Location: tour-history.php");
    exit;
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Payment - T&T</title>
    <link href="/tms/tms/css/bootstrap.css" rel='stylesheet' />
    <link href="/tms/tms/css/style.css" rel='stylesheet' />
    <link href="/tms/tms/css/font-awesome.css" rel="stylesheet">
    <script src="/tms/tms/js/jquery-1.12.0.min.js"></script>
    <script src="/tms/tms/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function () {
        // Format card number input
        $('input[name="card_number"]').on('input', function () {
            var val = $(this).val().replace(/\D/g, '').substring(0,16);
            var formatted = val.match(/.{1,4}/g);
            $(this).val(formatted ? formatted.join(' ') : '');
        });

        // Prevent auto-submission and validate form
        $('#paymentForm').on('submit', function (e) {
            let card = $('input[name="card_number"]').val().replace(/\s/g, '');
            let exp = $('input[name="expiry_date"]').val();
            let cvv = $('input[name="cvv"]').val();
            let errors = [];

            if (!/^\d{16}$/.test(card)) errors.push("Card must be 16 digits.");
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(exp)) errors.push("Expiry format invalid.");
            if (!/^\d{3}$/.test(cvv)) errors.push("CVV must be 3 digits.");

            if (errors.length) {
                e.preventDefault();
                alert(errors.join("\n"));
                return false;
            }
        });
    });
    </script>
    <style>
        .form-group input { max-width: 300px; }
        .succWrap, .errorWrap {
            padding: 10px; margin: 20px 0;
            border-left: 4px solid;
            background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.1);
        }
        .succWrap { border-color: #5cb85c; }
        .errorWrap { border-color: #dd3d36; }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="banner-1">
    <div class="container"><h1>Tour & Travel - Payment</h1></div>
</div>

<div class="container">
    <?php if ($msg) { ?><div class="succWrap"><?= htmlentities($msg); ?></div><?php } ?>
    <?php if ($error) { ?><div class="errorWrap"><?= htmlentities($error); ?></div><?php } ?>

    <?php if ($booking && !$error) { ?>
        <div style="border:1px solid #ddd; padding:15px; margin-bottom:15px;">
            <h4>Booking Details</h4>
            <p><strong>Package:</strong> <?= htmlentities($booking->PackageName); ?></p>
            <p><strong>Amount:</strong> NPR <?= number_format($booking->PackagePrice, 0); ?></p>
            <p><strong>Payment Method:</strong> <?= htmlentities($booking->PaymentMethod); ?></p>
            <p><strong>Reservation Timeout:</strong> <?= htmlentities($booking->ReservationTimeout ?: 'N/A'); ?></p>

            <!-- Change method -->
            <form method="post" style="margin-top:10px;">
                <label>Change Payment Method:</label>
                <select name="new_method" required>
                    <option value="Online" <?= $booking->PaymentMethod == 'Online' ? 'selected' : ''; ?>>Online</option>
                    <option value="Cash" <?= $booking->PaymentMethod == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                </select>
                <button name="change_method" class="btn btn-warning btn-sm">Change</button>
            </form>
        </div>

        <!-- Online Payment Form -->
        <?php if ($booking->PaymentMethod == 'Online' && $booking->PaymentStatus == 'Pending') { ?>
            <form method="post" id="paymentForm">
            <h4>Pay Online</h4>
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card_number" class="form-control" required maxlength="19" placeholder="1234 5678 9012 3456" value="">
            </div>
            <div class="form-group">
                <label>Expiry Date (MM/YY)</label>
                <input type="text" name="expiry_date" class="form-control" required maxlength="5" placeholder="MM/YY" value="">
            </div>
            <div class="form-group">
                <label>CVV</label>
                <input type="text" name="cvv" class="form-control" required maxlength="3" placeholder="123" value="">
            </div>
            <button name="submit_payment" class="btn btn-primary">Pay Now</button>
        </form>
        <?php } ?>

        <!-- Cash Payment Form -->
        <?php if ($booking->PaymentMethod == 'Cash' && $booking->PaymentStatus == 'Pending') { ?>
            <form method="post">
                <h4>Cash Payment</h4>
                <p>Pay on arrival. Please confirm below.</p>
                <button name="confirm_cash" class="btn btn-primary">Confirm Cash Payment</button>
            </form>
        <?php } ?>
    <?php } ?>
    <br>
    <a href="/tms/tms/tour-history.php" class="btn btn-secondary">Back to Tour History</a>
</div>

<?php include('includes/footer.php'); ?>
<?php include('includes/signup.php'); ?>
<?php include('includes/signin.php'); ?>
<?php include('includes/write-us.php'); ?>
</body>
</html>