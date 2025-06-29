<?php
if (defined('FUNCTIONS_PHP_INCLUDED')) {
    return;
}
define('FUNCTIONS_PHP_INCLUDED', true);

if (!function_exists('getBookedSlots')) {
    function getBookedSlots($dbh, $packageId, $fromDate, $toDate, $excludeBookingId = null) {
        // Get current date in Nepal Standard Time (Asia/Kathmandu)
        $currentDate = date('Y-m-d');

        $sql = "SELECT COUNT(*) FROM tblbooking 
                WHERE PackageId = :pid 
                  AND status = 1 
                  AND ToDate >= :currentDate
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
        $query->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
        $query->bindParam(':fromDate', $fromDate, PDO::PARAM_STR);
        $query->bindParam(':toDate', $toDate, PDO::PARAM_STR);
        if ($excludeBookingId !== null) {
            $query->bindParam(':excludeId', $excludeBookingId, PDO::PARAM_INT);
        }

        $query->execute();
        $count = $query->fetchColumn();
        error_log("getBookedSlots: PackageId=$packageId, FromDate=$fromDate, ToDate=$toDate, ExcludeBookingId=" . ($excludeBookingId ?? 'null') . ", CurrentDate=$currentDate, Slots=$count");
        return $count;
    }
}

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
            $error = "Selected dates do not match package duration of $package_duration day(s).";
            return false;
        }
        return true;
    }
}
?>