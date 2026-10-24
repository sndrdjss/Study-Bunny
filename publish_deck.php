<?php

require 'db.php';

// Only act on POST; otherwise go back to the create page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: create.php?error=publish');
    exit;
}

// Mark the deck as published so it appears on the Explore page.
try {
    $stmt = $conn->prepare("UPDATE deck SET status = 'published' WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: create.php?error=publish');
    exit;
}

header('Location: create.php?published=1');
exit;
