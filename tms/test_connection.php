<?php
// DB credentials.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tms');

try {
    $dbh = new PDO("mysql:host=".DB_HOST.";port=3308;dbname=".DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    echo "Connected successfully to MySQL!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
