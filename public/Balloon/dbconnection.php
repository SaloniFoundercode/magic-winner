<?php

$servername = "localhost";
$username = "u848595465_magicwinner";
$password = "u848595465_magicWinner";
$dbname = "u848595465_magicwinner";

// Create connection  
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

