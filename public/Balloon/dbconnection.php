<?php

$servername = "localhost";
$username = "u873167744_demotiranga";
$password = "u873167744_Demotiranga";
$dbname = "u873167744_demotiranga";

// Create connection  
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

