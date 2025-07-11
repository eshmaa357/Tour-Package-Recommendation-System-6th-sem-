<?php 
// DB credentials.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tms');

// Establish database connection.
try {
    $dbh = new PDO("mysql:host=" . DB_HOST . ";port=3308;dbname=" . DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Optional: Enable error reporting
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}
?>