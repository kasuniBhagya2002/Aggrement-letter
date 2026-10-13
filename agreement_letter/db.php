<?php
$host = "localhost";
$user = "root";
$pass = "";     
$db   = "agreement_letter";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");