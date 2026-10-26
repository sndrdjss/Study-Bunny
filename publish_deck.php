<?php

require_once __DIR__ . '/auth.php';
require 'db.php';

require_login();

// Only act on POST; otherwise go back to the create page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

verify_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: create.php?error=publish');
    exit;
}

// Mark the deck as published so it appears on the Explore page.
// Only the owner's own decks match the WHERE clause.
try {
    $userId = (int) current_user()['id'];
    $stmt = $conn->prepare("UPDATE deck SET status = 'published' WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();

    // 0 rows: not your deck (or already published). Confirm which.
    if ($changed < 1 && !fetch_owned_deck($conn, $id)) {
        $conn->close();
        header('Location: create.php?error=forbidden');
        exit;
    }
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: create.php?error=publish');
    exit;
}

header('Location: create.php?published=1');
exit;
