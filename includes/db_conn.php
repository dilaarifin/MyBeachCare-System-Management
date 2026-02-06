<?php
$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "mybeachcare";

try {
    $conn = new PDO("mysql:host=$sname;dbname=$db_name", $uname, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
