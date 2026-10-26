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

$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$deckId = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;

if ($deckId <= 0) {
    header('Location: create.php?error=notfound');
    exit;
}

if ($id <= 0) {
    header('Location: deck_items.php?id=' . $deckId . '&error=delete');
    exit;
}

// Delete the quiz_item; its quiz_option rows cascade via the FK.
try {
    // Only the deck owner may remove items.
    if (!fetch_owned_deck($conn, $deckId)) {
        $conn->close();
        header('Location: create.php?error=forbidden');
        exit;
    }
    $stmt = $conn->prepare('DELETE FROM quiz_item WHERE id = ? AND deck_id = ?');
    $stmt->bind_param('ii', $id, $deckId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: deck_items.php?id=' . $deckId . '&error=delete');
    exit;
}

header('Location: deck_items.php?id=' . $deckId . '&removed=1');
exit;
