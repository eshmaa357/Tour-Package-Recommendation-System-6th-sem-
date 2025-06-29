<?php
include('includes/config.php');
header('Content-Type: application/json');

if (isset($_POST['pid'])) {
    $pid = intval($_POST['pid']);
    $sql = "SELECT PackageDuration FROM tbltourpackages WHERE PackageId = :pid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':pid', $pid, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if ($result && isset($result['PackageDuration']) && $result['PackageDuration'] > 0) {
        echo json_encode(['duration' => intval($result['PackageDuration'])]);
    } else {
        echo json_encode(['duration' => 1, 'error' => 'Package not found or invalid duration']);
    }
} else {
    echo json_encode(['duration' => 1, 'error' => 'Invalid package ID']);
}
?>