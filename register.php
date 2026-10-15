<?php

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the posted fields (names match register.html).
    $lastName  = $_POST['last-name']; 
    $firstName = $_POST['first-name'];
    $username  = $_POST['username'];
    $pass      = $_POST['password'];
    $confirm   = $_POST['confirm-password'];

    // Basic validation lang 'tooo

    if ($lastName === '' || $firstName === '' || $username === '' || $pass === '') {
        header('Location: html/register.html?error=empty');
        exit;
    }
    if ($pass !== $confirm) {
        header('Location: html/register.html?error=mismatch');
        exit;
    }

    try {
        $sql = "INSERT INTO study_buddy_auth (last_name, first_name, username, password)
              VALUES ('$lastName', '$firstName', '$username', '$pass')";
        $conn->query($sql);

        // Success → go to the login page.
        header('Location: html/login.html?registered=1');
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($conn->errno === 1062) {
            header('Location: html/register.html?error=taken');
        } else {
            header('Location: html/register.html?error=db');
        }
        exit;
    }
}

header('Location: html/register.html');
exit;
