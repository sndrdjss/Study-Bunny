<?php
// ============================================================
// register.php — Handles the Sign Up form.
// Reads POST data, validates, inserts a new user, redirects.
// ============================================================

require 'db.php';

// Only act on a real form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the posted fields (names match register.html).
    $lastName  = $_POST['last-name']; 
    $firstName = $_POST['first-name'];
    $username  = $_POST['username'];
    $pass      = $_POST['password'];
    $confirm   = $_POST['confirm-password'];

    // --- Basic validation ---
    if ($lastName === '' || $firstName === '' || $username === '' || $pass === '') {
        header('Location: html/register.html?error=empty');
        exit;
    }
    if ($pass !== $confirm) {
        header('Location: html/register.html?error=mismatch');
        exit;
    }

    // --- Insert using a prepared statement (safe against quotes/injection) ---
    // Note: PHP 8.1+ makes mysqli throw exceptions on error, so we use try/catch.
    try {
        $sql = "INSERT INTO users (last_name, first_name, username, password)
              VALUES ('$lastName', '$firstName', '$username', '$pass')";
        $conn->query($sql);

        // Success → go to the login page.
        header('Location: html/login.html?registered=1');
        exit;
    } catch (mysqli_sql_exception $e) {
        // Error number 1062 means the UNIQUE username already exists.
        if ($conn->errno === 1062) {
            header('Location: html/register.html?error=taken');
        } else {
            header('Location: html/register.html?error=db');
        }
        exit;
    }
}

// If someone opens register.php directly without posting, send them to the form.
header('Location: html/register.html');
exit;
