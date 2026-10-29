<?php
/* =========================================================
   StudyBuddy — toggle a deck favorite
   POST { id, return, csrf_token }
     - If the deck is NOT yet favorited -> add it, then land on
       the Library (keeps the celebratory "sent to Library" flow).
     - If the deck IS already favorited -> remove it, then return
       to the page the user clicked from (validated allowlist).
   ========================================================= */

require_once __DIR__ . '/auth.php';
require 'db.php';

require_login();

/**
 * Resolve a safe "return" target from user input.
 * Accepts only a known set of relative page scripts, optionally with
 * a query string. Anything else (absolute URLs, protocol-relative,
 * path traversal, unknown scripts) falls back to the Library.
 */
function safe_return_url(?string $raw): string {
    $fallback = 'library.php';
    if ($raw === null || $raw === '') {
        return $fallback;
    }

    // Reject absolute or protocol-relative URLs outright.
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $raw) || str_starts_with($raw, '//')) {
        return $fallback;
    }

    // Split off any query string; validate the script name against an allowlist.
    $path  = $raw;
    $query = '';
    if (($pos = strpos($raw, '?')) !== false) {
        $path  = substr($raw, 0, $pos);
        $query = substr($raw, $pos); // includes leading "?"
    }

    $allowed = ['explore.php', 'collection.php', 'search.php', 'library.php'];
    if (!in_array($path, $allowed, true)) {
        return $fallback;
    }

    // Drop the query string if it contains control chars / newlines
    // (guards against header injection). Normal ?a=b&c=d passes fine.
    if ($query !== '' && preg_match('/[\x00-\x1F\x7F]/', $query)) {
        $query = '';
    }

    return $path . $query;
}

// Only act on POST; a stray GET just goes back to Explore.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}

verify_csrf();

$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$return = safe_return_url($_POST['return'] ?? null);

// Invalid id: nothing to toggle, just land on the Library.
if ($id <= 0) {
    header('Location: library.php');
    exit;
}

try {
    $uid = (int) current_user()['id'];

    // Is this deck already favorited by the user?
    $check = $conn->prepare('SELECT 1 FROM deck_favorite WHERE user_id = ? AND deck_id = ? LIMIT 1');
    $check->bind_param('ii', $uid, $id);
    $check->execute();
    $exists = (bool) $check->get_result()->fetch_row();
    $check->close();

    if ($exists) {
        // ---- REMOVE path ----
        $del = $conn->prepare('DELETE FROM deck_favorite WHERE user_id = ? AND deck_id = ?');
        $del->bind_param('ii', $uid, $id);
        $del->execute();
        $del->close();
        $conn->close();

        // Stay on the page the user was browsing.
        header('Location: ' . $return);
        exit;
    }

    // ---- ADD path ----
    // UNIQUE (user_id, deck_id) makes a racing double-click a harmless no-op.
    $ins = $conn->prepare('INSERT IGNORE INTO deck_favorite (user_id, deck_id) VALUES (?, ?)');
    $ins->bind_param('ii', $uid, $id);
    $ins->execute();
    $ins->close();
    $conn->close();

    // Keep the celebratory "sent to Library" flow on add.
    header('Location: library.php?favorited=1');
    exit;
} catch (mysqli_sql_exception $e) {
    // On error, send the user to the Library with a soft flag.
    header('Location: library.php?error=favorite');
    exit;
}
