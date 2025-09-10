<?php
$servername = "localhost";
$username = "root"; // Change to your DB user
$password = ""; // Change to your DB password
$dbname = "phantomwork";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>