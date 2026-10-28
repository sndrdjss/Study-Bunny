<?php
/* =========================================================
   StudyBuddy — favorite a deck
   POST { id, csrf_token } -> adds the deck to the logged-in
   user's favorites (idempotent) and returns to the Library.
   ========================================================= */

require_once __DIR__ . '/auth.php';
require 'db.php';

require_login();

// Only act on POST; a stray GET just goes back to Explore.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}

verify_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

// Invalid id: nothing to favorite, just land on the Library.
if ($id <= 0) {
    header('Location: library.php');
    exit;
}

// Insert the favorite. The UNIQUE (user_id, deck_id) key makes a repeat
// click a harmless no-op, so INSERT IGNORE keeps this idempotent.
try {
    $uid  = (int) current_user()['id'];
    $stmt = $conn->prepare('INSERT IGNORE INTO deck_favorite (user_id, deck_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $uid, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    // Even on error, send the user to the Library (with a soft flag).
    header('Location: library.php?error=favorite');
    exit;
}

header('Location: library.php?favorited=1');
exit;
