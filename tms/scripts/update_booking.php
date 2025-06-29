<?php
error_reporting(E_ALL);
include('includes/config.php');
date_default_timezone_set('Asia/Kathmandu');

// Update bookings where FromDate has passed without confirmation or payment (Cash)
$sql = "UPDATE tblbooking 
        SET PaymentStatus = 'No Payment', status = 2, CancelledBy = 'system', UpdationDate = CURRENT_TIMESTAMP 
        WHERE status = 0 
          AND PaymentMethod = 'Cash' 
          AND PaymentStatus = 'Pending' 
          AND FromDate < CURDATE()";
$query = $dbh->prepare($sql);
$query->execute();
$affected = $query->rowCount();
error_log("update_bookings.php: Marked $affected bookings as 'No Payment' and cancelled due to passed FromDate");

// Update bookings where ToDate has passed without admin approval
$sql = "UPDATE tblbooking 
        SET status = 2, CancelledBy = 'system', UpdationDate = CURRENT_TIMESTAMP, 
            Comment = CONCAT(IFNULL(Comment, ''), ' [Not Approved by Admin by ToDate]') 
        WHERE status = 0 
          AND ToDate < CURDATE()";
$query = $dbh->prepare($sql);
$query->execute();
$affected = $query->rowCount();
error_log("update_bookings.php: Marked $affected bookings as 'Not Approved' and cancelled due to passed ToDate");

echo "Updated $affected bookings.";
?>