<?php

$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'hcieinsproject';


$conn = new mysqli($host, $user, $password, $database);


if ($conn->connect_error) {
    echo 'connection error';
    die('Database connection failed: ' . $conn->connect_error);
} else {
    echo 'connected';
}
