<?php
$user = "u650542527_plamal_site";           
$pass = "@4pQnfJ85";               
$db   = "u650542527_plamalDB";   

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
