<?php

require 'db.php';

// Only act on a real form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    // --- Look up the user by username (prepared statement) ---
    $stmt = $conn->prepare('SELECT password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Compare stored plain-text password with what was typed.
        if ($pass === $row['password']) {
            // Success → go to the explore page.
            header('Location: html/explore.html');
            exit;
        }
    }

    // Any failure (no user, or wrong password) → back to login with an error.
    $stmt->close();
    $conn->close();
    header('Location: html/login.html?error=1');
    exit;
}

// If someone opens login.php directly without posting, send them to the form.
header('Location: html/login.html');
exit;
