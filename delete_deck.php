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

try {
    // Must exist AND belong to the logged-in user.
    $deck = fetch_owned_deck($conn, $id);
    if (!$deck) {
        $conn->close();
        header('Location: create.php?error=forbidden');
        exit;
    }

    // quiz_item / quiz_option rows cascade via their foreign keys.
    $userId = (int) current_user()['id'];
    $stmt = $conn->prepare('DELETE FROM deck WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: create.php?error=delete');
    exit;
}

// Remove the cover image file, but only if it lives in images/covers/.
if (!empty($deck['cover_image'])) {
    $coversDir = realpath(__DIR__ . '/images/covers');
    $file      = realpath(__DIR__ . '/' . $deck['cover_image']);
    if ($coversDir && $file && str_starts_with($file, $coversDir . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

header('Location: create.php?deleted=1');
exit;
