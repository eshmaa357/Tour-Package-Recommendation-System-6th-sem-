<?php
include('includes/config.php');
include_once('includes/functions.php'); // Use include_once for safety
header('Content-Type: application/json');

if (isset($_POST['packageId']) && isset($_POST['fromDate']) && isset($_POST['toDate'])) {
    $packageId = intval($_POST['packageId']);
    $fromDate = $_POST['fromDate'];
    $toDate = $_POST['toDate'];

    error_log("check-slots.php: Called with packageId=$packageId, fromDate=$fromDate, toDate=$toDate");

    if ($packageId <= 0 || empty($fromDate) || empty($toDate)) {
        error_log("check-slots.php: Invalid input parameters");
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $bookedSlots = getBookedSlots($dbh, $packageId, $fromDate, $toDate);
    error_log("check-slots.php: Booked slots = $bookedSlots");
    echo json_encode(['bookedSlots' => $bookedSlots]);
} else {
    error_log("check-slots.php: Missing required POST parameters");
    echo json_encode(['error' => 'Invalid input']);
}
?>