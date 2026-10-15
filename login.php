<?php

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT password FROM study_buddy_auth WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($pass === $row['password']) {
            // Success → go to the explore page.
            header('Location: html/explore.html');
            exit;
        }
    }

    // Pag wrong password back ka sa login.html
    $stmt->close();
    $conn->close();
    header('Location: html/login.html?error=1');
    exit;
}

header('Location: html/login.html');
exit;
